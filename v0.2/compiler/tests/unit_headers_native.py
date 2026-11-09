"""Usage-based headers: first/last foreign watcher, private declarations, and header-only dependent rebuilds."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build


def run(command, cwd=None):
    return subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True, timeout=60,
                          env={**os.environ, 'XDEBUG_MODE': 'off'})


def prove(work):
    work.mkdir(parents=True, exist_ok=True)
    reports = {}
    for flavor in ('cpp', 'php', 'js'):
        app, generated, native = (work / flavor / name for name in ('app', 'generated', 'native'))
        for path in (app, generated, native):
            path.mkdir(parents=True, exist_ok=True)
        (app / 'module.json').write_text('{}')
        fn = lambda name, typ, body: (f'{typ} {name}()' if flavor == 'cpp' else f'function {name}(): {typ}') + '{' + body + '}'
        provider = lambda typ='void', value='': fn('hidden', 'void', 'return;') + fn('visible', typ, 'hidden();' + value)
        stamp = int(time.time())
        def write(name, text):
            nonlocal stamp
            path = app / (name + '.' + flavor)
            path.write_text(text); stamp += 1; os.utime(path, (stamp, stamp))
        write('main', 'return 8;')
        write('provider', provider())
        process = subprocess.Popen(['php', str(ROOT / 'compiler/tests/incremental_executable.php'), str(app), str(generated)],
                                   stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
                                   env={**os.environ, 'XDEBUG_MODE': 'off'})
        rows = []
        def step(name, prepared, emitted, objects, exported):
            before = {p.name: p.stat().st_mtime_ns for p in native.glob('unit-*.o')}
            process.stdin.write('"run"\n'); process.stdin.flush()
            response = json.loads(process.stdout.readline())
            assert 'error' not in response, response
            assert response['preparation']['prepared'] == prepared, response
            assert len(response['emitted_sources']) == emitted, response
            manifest = json.loads((generated / 'manifest.json').read_text())
            assert len(manifest['headers']) == int(exported), manifest
            assert {str(p.relative_to(generated)) for p in generated.rglob('*.hpp')} == set(manifest['headers'])
            paths = {key.split('/')[-1]: generated / value for key, value in manifest['sources'].items()}
            main = paths['main.' + flavor]; library = paths['provider.' + flavor]
            visible = 'visible_'
            hidden = 'hidden_'
            if exported:
                header = generated / manifest['headers'][0]
                assert visible in header.read_text() and hidden not in header.read_text(), 'Header exposed private function'
                assert '#include "' + header.name + '"' in main.read_text(), 'Consumer lacks owning header'
                assert '#include "' + header.name + '"' in library.read_text(), 'Provider lacks its declaration header'
                assert f'void {hidden}();' in library.read_text(), 'Private prototype not in .cpp'
                assert not any(line.endswith(');') and visible in line for line in library.read_text().splitlines()), 'Exported prototype duplicated'
            command = write_build(native, [generated / p for p in manifest['translation_units']]); command[2] = '8'
            graph = native / 'build.ninja'; graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
            start = time.perf_counter(); result = run(command, native); elapsed = (time.perf_counter()-start)*1000
            assert result.returncode == 0, result.stdout + result.stderr
            (native / (name + '.log')).write_text(result.stdout + result.stderr)
            changed = [p for p in native.glob('unit-*.o') if before.get(p.name) != p.stat().st_mtime_ns]
            assert len(changed) == objects, (name, result.stdout)
            executed = run([native / 'program'])
            assert executed.returncode == 8 and executed.stdout == '', executed
            totals = {}
            for obj in changed:
                for event in json.loads(obj.with_suffix('.json').read_text())['traceEvents']:
                    if event['name'].startswith('Total '):
                        key = event['name'][6:]; totals[key] = totals.get(key, 0) + event['dur']/1000
            link_ms = 0
            if changed:
                last = [line.split('\t') for line in (native / '.ninja_log').read_text().splitlines()[1:]
                        if line.split('\t')[3] == 'program'][-1]
                link_ms = int(last[1])-int(last[0])
            rows.append(dict(scenario=name, **response, objects=objects, native_ms=elapsed, trace_sum_ms=totals, link_ms=link_ms))
            return main, library, manifest
        try:
            step('local_only', 3, 2, 3, False)
            write('main', 'visible(); return 8;')
            main, library, manifest = step('first_external_use', 1, 2, 2, True)
            original_main = (main.read_bytes(), main.stat().st_mtime_ns)
            write('provider', provider('int', 'return 8;'))
            step('signature_header', 3, 2, 2, True)
            assert original_main == (main.read_bytes(), main.stat().st_mtime_ns), 'Consumer C++ changed for a discarded return value'
            header = generated / manifest['headers'][0]
            header_snapshot = (header.read_bytes(), header.stat().st_mtime_ns)
            write('provider', provider('int', 'return 9;'))
            step('body_only', 2, 1, 1, True)
            assert header_snapshot == (header.read_bytes(), header.stat().st_mtime_ns), 'Body edit rewrote header'
            write('provider', provider('string', 'return "hello";'))
            step('string_signature', 3, 2, 2, True)
            assert '#include <scpp/' in header.read_text(), 'Missing runtime type include'
            # Compile the header alone: it must supply its own signature type requirements.
            check = run(['clang++', '-std=c++23', '-stdlib=libc++', '-g0', '-Werror', '-x', 'c++-header',
                         '-fsyntax-only', '-I', ROOT / 'runtime/generated/public', header])
            assert check.returncode == 0, check.stderr
            write('main', 'return 8;')
            step('last_external_use_removed', 1, 2, 2, False)
            assert not header.exists(), 'Obsolete header survived'
            write('main', 'visible(); return 8;')
            step('external_use_restored', 1, 2, 2, True)
            step('noop', 0, 0, 0, True)
        finally:
            process.stdin.close(); process.wait(timeout=10)
            assert process.returncode == 0, process.stderr.read()
        reports[flavor] = rows
        print(flavor + ': usage-based headers, header depfiles, private declarations and transitions passed', flush=True)
    (work / 'results.json').write_text(json.dumps(reports, indent=2))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-unit-headers-') as temporary:
            prove(Path(temporary))
