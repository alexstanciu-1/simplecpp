"""Exercise Point and hash<Point,string> across the outlined native task boundary."""
import json
from pathlib import Path
import re
import statistics
import subprocess
import sys

from run import (FRAME, METHODS, ROOT, execute, isolation, materialize, measure,
                 object_name, prototype, write_build)


def payload_headers(files):
    """This fixture uses one record layout; the concrete hash requires that layout first."""
    if not any(FRAME in text for text in files.values()):
        return []
    point, = [path for path, text in files.items()
              if text.startswith('// Generated inline record layout.') and 'struct Point {' in text]
    hashes = [path for path, text in files.items() if text.startswith('// Concrete application collection;')]
    assert len(hashes) <= 1
    return [point, *hashes]


def build_snapshot(work, files, fixture):
    build = work / 'build'
    build.mkdir(parents=True, exist_ok=True)
    units = materialize(work / 'src', files)
    cmd = write_build(build, units)
    subprocess.run(cmd, cwd=build, check=True, stdout=subprocess.DEVNULL)
    execute(build, fixture)
    isolation(build, units)
    return build


def transitions(work, fixtures, flavor):
    initial = None
    for step, count in [(74, 2), (75, 2), (77, 2), (78, 1), (79, 1), (80, 0), (81, 2)]:
        fixture = fixtures[f'{flavor}params_{step}']
        headers = payload_headers(fixture['files'])
        files, actual = prototype(fixture['files'], headers)
        fresh = fixture['fresh_files']
        fresh_files, fresh_count = prototype(fresh, payload_headers(fresh))
        assert files == fresh_files and actual == fresh_count == count
        build = build_snapshot(work, files, fixture)
        build_snapshot(work / f'fresh-{step}', fresh_files, fixture)
        symbols = subprocess.check_output(['nm', '-C', build / 'program'], text=True)
        expected = {m.group(1) for text in files.values()
                    if (m := re.search(r'class (scpp_async_task_\w+) final', text))}
        linked = re.findall(r' [tT] .*?(scpp_async_task_\w+)::await_resume\(\)', symbols)
        assert len(expected) == count and sorted(expected) == sorted(linked), (step, expected, linked)
        if not count:
            assert not (work / 'src' / METHODS).exists() and 'scpp_async_task_' not in symbols
            continue
        obj = object_name(work / 'src' / METHODS) + '.o'
        stamp = (build / obj).stat().st_mtime_ns
        deps = subprocess.check_output(['ninja', '-t', 'deps', obj], cwd=build, text=True)
        assert all(str(work / 'src' / h) in deps for h in headers)
        if step == 74:
            initial = files
            initial_stamp = stamp
        if step == 75:
            assert initial[METHODS] == files[METHODS] and initial[headers[0]] != files[headers[0]]
            assert stamp > initial_stamp
        if step in (77, 81):
            assert files == initial
    print(flavor, 'layout edit, retirement, recovery and fresh executables passed', flush=True)


def main(output):
    output.mkdir(parents=True)
    snapshots = output / 'fixtures.json'
    subprocess.run(['php', ROOT / 'compiler/tests/async.php', snapshots], check=True)
    fixtures = json.loads(snapshots.read_text())
    flavors = ['php', 'cpp', 'js']
    for flavor in flavors:
        transitions(output / flavor, fixtures, flavor)
    samples = {}
    for i in range(6):
        for flavor in (flavors if i % 2 == 0 else reversed(flavors)):
            for label, step in [('point', 79), ('hash', 78)]:
                fixture = fixtures[f'{flavor}params_{step}']
                for variant in (['inline', 'outline'] if i % 2 == 0 else ['outline', 'inline']):
                    key = f'{flavor}-{label}-{variant}'
                    result, status = measure(output / f'{i}-{key}', fixture, variant, output / 'pch',
                                             1, payload_headers(fixture['files']))
                    if i:
                        assert status == 'reused'
                        samples.setdefault(key, []).append(result)
    (output / 'samples.json').write_text(json.dumps(samples, indent=2))
    for key, rows in samples.items():
        print(key, {k: round(statistics.median(row[k] for row in rows), 1) for k in rows[0]})


if __name__ == '__main__':
    main(Path(sys.argv[1]).resolve())
