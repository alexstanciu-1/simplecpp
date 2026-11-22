"""Execute incremental and independently rebuilt cross-file async applications."""
import argparse
import hashlib
import json
import subprocess
import tempfile
import time
from pathlib import Path

from strings_native import ROOT, run, success, write_build
from native_build import qualified_ninja


def build(directory, fixture):
    directory.mkdir(parents=True, exist_ok=True)
    units = []
    for name, text in fixture['files'].items():
        path = directory / name
        path.parent.mkdir(parents=True, exist_ok=True)
        if not path.exists() or path.read_text() != text:
            path.write_text(text)
        if path.suffix == '.cpp':
            units.append(path)
    cmd = write_build(directory, units)
    cmd[2] = '8'
    graph = directory / 'build.ninja'
    graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
    start = time.perf_counter()
    with qualified_ninja(cmd, directory) as invocation:
        success(run(invocation, directory))
    wall = (time.perf_counter() - start) * 1000
    result = subprocess.run([directory / 'program'], capture_output=True)
    assert (result.returncode, result.stdout.hex(), result.stderr) == (fixture['exit'], fixture['stdout_hex'], fixture['stderr'].encode()), result
    totals = {'Frontend': 0, 'Backend': 0}
    compiled = []
    for src in units:
        obj = 'unit-' + hashlib.sha256(str(src.resolve()).encode()).hexdigest()
        trace = directory / (obj + '.json')
        if trace.exists():
            compiled.append(src.name)
            for event in json.loads(trace.read_text())['traceEvents']:
                name = event['name'].removeprefix('Total ')
                if event['name'].startswith('Total ') and name in totals:
                    totals[name] += event['dur'] / 1000
            trace.unlink()
        deps = success(run(['ninja', '-t', 'deps', obj + '.o'], directory)).stdout
        headers = {Path(line.strip()).name for line in deps.splitlines()[1:]}
        assert not headers.intersection({'async_core.hpp', 'thread', 'condition_variable', 'queue', 'mutex'})
        body = src.read_text()
        assert ('async_timer.hpp' in headers) == ('::delay_ms(' in body)
        assert ('async_wait.hpp' in headers) == ('::wait_for(' in body)
        if 'co_return' not in body:
            assert 'coroutine' not in headers and not headers.intersection({'async_void.hpp', 'async_int64.hpp', 'async_bool.hpp'})
    links = [line.split('\t') for line in (directory / '.ninja_log').read_text().splitlines()[1:]
             if line.split('\t')[3] == 'program']
    link = links[-1]
    return dict(compiler_ms=fixture['compiler_ms'], native_ms=wall, objects=len(compiled),
                frontend_ms=totals['Frontend'], llvm_ms=totals['Backend'], link_ms=int(link[1])-int(link[0]))


def prove(work):
    work.mkdir(parents=True, exist_ok=True)
    path = work / 'fixtures.json'
    success(run(['php', ROOT / 'compiler/tests/async.php', path]))
    report = {}
    for key, fixture in json.loads(path.read_text()).items():
        flavor, step = key.split('_')
        report[key] = build(work / flavor, fixture)
        assert report[key]['objects'] == fixture['objects'], report[key]
        build(work / ('fresh-' + key), {**fixture, 'files': fixture['fresh_files']})
    (work / 'timings.json').write_text(json.dumps(report, indent=2))
    print(json.dumps(report, indent=2))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-async-native-') as temporary:
        prove(args.output.resolve() if args.output else Path(temporary))
