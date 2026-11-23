"""Compare consumer-inline task methods with one application-owned implementation unit.

Run from any directory with python3 run.py OUTPUT. OUTPUT must be a new directory.
Uses the actual compiler snapshots. The out-of-line experiment is limited to tasks
whose result declarations are supplied by runtime headers.
"""
from pathlib import Path
import hashlib
import json
import re
import statistics
import subprocess
import sys
import time

ROOT = Path(__file__).resolve().parents[4]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import async_producers, write_build
from pch_cache import runtime_pch

FRAME = '#include <scpp/async/frame.hpp>'
METHODS = 'async_methods.cpp'


def prototype(files):
    """Move the existing recipe's method fragment verbatim except for inline linkage."""
    output = dict(files)
    methods = []
    for path, text in sorted(files.items()):
        if not path.endswith('.hpp') or FRAME not in text:
            continue
        declaration, body = text.split(FRAME)
        assert 'struct promise_type final' in declaration and 'inline ' in body
        output[path] = declaration
        methods.append(f'#include "{path}"\n' + re.sub(r'^inline ', '', body, flags=re.MULTILINE))
    if methods:
        output[METHODS] = '#include <coroutine>\n' + FRAME + '\n' + '\n'.join(methods)
    return output, len(methods)


def materialize(directory, files):
    directory.mkdir(exist_ok=True, parents=True)
    for path in directory.rglob('*'):
        if path.suffix in {'.cpp', '.hpp'} and path.relative_to(directory).as_posix() not in files:
            path.unlink()
    for name, text in files.items():
        path = directory / name
        path.parent.mkdir(exist_ok=True, parents=True)
        if not path.exists() or path.read_text() != text:
            path.write_text(text)
    return [directory / n for n in files if n.endswith('.cpp')]


def execute(build, fixture):
    run = subprocess.run([build / 'program'], capture_output=True)
    assert (run.returncode, run.stdout.hex(), run.stderr.decode()) == (
        fixture['exit'], fixture['stdout_hex'], fixture['stderr'])


def object_name(unit):
    return 'unit-' + hashlib.sha256(str(unit.resolve()).encode()).hexdigest()


def isolation(build, units):
    for unit in units:
        lines = subprocess.check_output(['ninja', '-t', 'deps', object_name(unit) + '.o'],
                                        cwd=build, text=True).splitlines()[1:]
        headers = {Path(s.strip()).name for s in lines}
        if not ('co_return' in unit.read_text() or unit.name == METHODS):
            assert headers.isdisjoint({'coroutine', 'frame.hpp', 'async_timer.hpp'}), (unit, headers)
        assert headers.isdisjoint({'thread', 'condition_variable'}), (unit, headers)


def transitions(directory, fixtures, flavor):
    src, build = directory / 'src', directory / 'build'
    build.mkdir(parents=True)
    old_method = None
    for step, count in [(70, 1), (71, 1), (72, 0), (73, 1)]:
        fixture = fixtures[f'{flavor}params_{step}']
        files, actual = prototype(fixture['files'])
        assert (files, actual) == prototype(fixture['fresh_files']) and actual == count
        units = materialize(src, files)
        cmd = write_build(build, units)
        subprocess.run(cmd, cwd=build, check=True, stdout=subprocess.DEVNULL)
        execute(build, fixture)
        isolation(build, units)
        if step == 70:
            old_method = (src / METHODS).read_bytes()
            stamp = (build / (object_name(src / METHODS) + '.o')).stat().st_mtime_ns
        if step == 71:
            assert (src / METHODS).read_bytes() == old_method
            assert (build / (object_name(src / METHODS) + '.o')).stat().st_mtime_ns == stamp
        if step == 72:
            assert not (src / METHODS).exists()
            symbols = subprocess.check_output(['nm', '-C', build / 'program'], text=True)
            assert 'scpp_async_task_' not in symbols and 'scpp::async::detail::' not in symbols
        if step == 73:
            assert (src / METHODS).read_bytes() == old_method


def measure(work, fixture, variant, cache):
    files = fixture['files']
    if variant == 'outline':
        files, count = prototype(files)
        assert count == 23
    units = materialize(work / 'src', files)
    prods = async_producers(units)
    assert len(prods) == (3 if variant == 'outline' else 2)
    with runtime_pch(cache, work, 'O2', True) as (build, report):
        cmd = write_build(build, units, pch=True, async_units=prods)
        graph = build / 'build.ninja'
        graph.write_text(graph.read_text().replace('$pchflag -MD', '$pchflag -ftime-trace -MD'))
        start = time.perf_counter()
        subprocess.run(cmd, cwd=build, stdout=subprocess.DEVNULL, check=True)
        sample = {'wall': (time.perf_counter() - start) * 1000}
        execute(build, fixture)
        isolation(build, units)
        for unit in units:
            if unit.name == 'entry.cpp':
                continue
            role = ('methods' if unit.name == METHODS else 'child' if '::delay_ms(' in unit.read_text()
                    else 'parent' if unit in prods else 'caller')
            trace = json.loads((build / (object_name(unit) + '.json')).read_text())['traceEvents']
            for phase in ['Frontend', 'Backend']:
                sample[role + '_' + phase] = next(e['dur'] / 1000 for e in trace if e['name'] == 'Total ' + phase)
        link = [s.split('\t') for s in (build / '.ninja_log').read_text().splitlines() if '\tprogram\t' in s][-1]
        sample['link'] = int(link[1]) - int(link[0])
        report['success'] = True
    return sample, report['status']


def main(output):
    output.mkdir(parents=True)
    snapshots = output / 'fixtures.json'
    subprocess.run(['php', ROOT / 'compiler/tests/async.php', snapshots], check=True)
    fixtures = json.loads(snapshots.read_text())
    for flavor in ['php', 'cpp', 'js']:
        transitions(output / ('edits-' + flavor), fixtures, flavor)
    samples = {}
    for i in range(6):
        for flavor in (['php', 'cpp', 'js'] if i % 2 == 0 else ['js', 'cpp', 'php']):
            for variant in (['inline', 'outline'] if i % 2 == 0 else ['outline', 'inline']):
                result, status = measure(output / f'{i}-{flavor}-{variant}', fixtures[flavor+'params_61'],
                                         variant, output / 'pch')
                if i:
                    assert status == 'reused'
                    samples.setdefault(flavor + '-' + variant, []).append(result)
    (output / 'samples.json').write_text(json.dumps(samples, indent=2))
    for key, rows in samples.items():
        print(key, {k: round(statistics.median(row[k] for row in rows), 1) for k in rows[0]})


if __name__ == '__main__':
    main(Path(sys.argv[1]).resolve())
