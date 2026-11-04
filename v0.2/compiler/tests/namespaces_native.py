"""Build namespace/import proofs for all flavors with Clang; retain separate frontend/LLVM/linker profiles."""
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build


def run(args, cwd=None, expected=0):
    start = time.perf_counter()
    result = subprocess.run(list(map(str, args)), cwd=cwd, capture_output=True, timeout=60,
                            env={**os.environ, 'XDEBUG_MODE': 'off'})
    elapsed = (time.perf_counter() - start) * 1000
    assert result.returncode == expected, result.stdout + result.stderr
    return elapsed, result.stdout, result.stderr


imports = '--imports' in sys.argv
globals_proof = '--globals' in sys.argv
arguments = [arg for arg in sys.argv[1:] if arg not in ('--imports', '--globals')]
work = Path(arguments[0]).resolve() if arguments else Path(tempfile.mkdtemp(prefix='scpp-namespaces-native-'))
proof = 'globals' if globals_proof else ('imports' if imports else 'namespaces')
work.mkdir(parents=True, exist_ok=True)
setup_ms, out, _ = run([os.environ.get('PHP_EXECUTABLE', 'php'), '-n', ROOT / f'compiler/tests/{proof}.php', work / 'generated'])
print(out.decode(), end='')
report = {'settings': 'O2, g0, Ninja -j8, mold, libc++, no PCH/LTO; existing runtime archive',
          'php_test_and_fixture_emission_ms': setup_ms, 'flavors': {}}
for flavor in (('cpp', 'php', 'js') if imports or globals_proof else ('cpp', 'php', 'js', 'mixed')):
    directory = work / flavor
    directory.mkdir(exist_ok=True)
    units = sorted((work / 'generated' / flavor).rglob('*.cpp'))
    assert len(units) == (4 if globals_proof else (3 if imports else 6))
    command = write_build(directory, units, 'O2', False)
    command[command.index('-j') + 1] = '8'
    native_ms, out, err = run(command, directory)
    (directory / 'build.log').write_bytes(out + err)
    _, stdout, _ = run([directory / 'program'], expected=8)
    expected_stdout = b'5\n10\n10\n20\n42\n' if globals_proof else (b'ping\npong\n' if imports else b'app\nlib\nlib\nroot\n')
    assert stdout == expected_stdout
    _, sections, _ = run(['readelf', '-S', directory / 'program'])
    assert b'.debug_' not in sections
    latest = {}
    for line in (directory / '.ninja_log').read_text().splitlines():
        if not line.startswith('#'):
            start, end, _, name, _ = line.split('\t')
            latest[name] = int(end) - int(start)
    graph = directory / 'build.ninja'
    original = graph.read_text()
    graph.write_text(original.replace(' -c $in', ' -ftime-trace -c $in').replace(
        '-g0 -stdlib=libc++ --ld-path=', '-g0 -stdlib=libc++ -Wl,--perf --ld-path='))
    _, out, err = run(command, directory)
    (directory / 'trace-build.log').write_bytes(out + err)
    totals = {}
    for trace in directory.glob('unit-*.json'):
        for event in json.loads(trace.read_text())['traceEvents']:
            if event['name'].startswith('Total '):
                key = event['name'][6:]
                totals[key] = totals.get(key, 0) + event['dur'] / 1000
    mold = re.search(r'^\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+all\s*$', (out + err).decode(), re.M)
    assert mold
    graph.write_text(original)
    report['flavors'][flavor] = {'native_wall_ms': native_ms, 'link_command_ms': latest['program'],
        'trace_cumulative_ms': totals, 'mold_trace_wall_ms': float(mold[1]) * 1000}
    print(f'{flavor}: native={native_ms:.2f} ms, frontend={totals["Frontend"]:.2f} ms, '
          f'LLVM={totals["Backend"]:.2f} ms (cumulative separate trace), link={latest["program"]} ms')
report['notes'] = 'Single-run correctness proof, not a comparative benchmark. PHP timing includes tests and fixture emission. Native wall excludes runtime rebuild. Trace phases are cumulative and separate; do not add them to wall time.'
(work / 'results.json').write_text(json.dumps(report, indent=2) + '\n')
print(work / 'results.json')
