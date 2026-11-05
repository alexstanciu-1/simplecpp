"""Retained PHP preparation -> selected emission -> retained Ninja objects -> executable, including recovery."""
import argparse
import hashlib
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


def snapshots(directory, pattern):
    return {p.name: p.stat().st_mtime_ns for p in directory.glob(pattern)}


def prove(work):
    work.mkdir(parents=True, exist_ok=True)
    results = {}
    for flavor in ('cpp', 'php', 'js'):
        app = work / flavor / 'app'
        output = work / flavor / 'generated'
        build = work / flavor / 'native'
        for directory in (app, output, build):
            directory.mkdir(parents=True, exist_ok=True)
        (app / 'module.json').write_text('{}')
        fn = lambda name, typ: f'{typ} {name}()' if flavor == 'cpp' else f'function {name}(): {typ}'
        glob = '$stored' if flavor == 'php' else 'stored'
        local = lambda name, expr: {'cpp': f'int {name} = {expr};', 'php': f'${name} int = {expr};',
                                    'js': f'let {name}: int = {expr};'}[flavor]
        var = lambda name: '$' + name if flavor == 'php' else name
        def provider(c=8, gt='int', gv='8', ft='int', value='8'):
            const = f'const LIMIT: int = {c};' if flavor == 'js' else f'const int LIMIT = {c};'
            global_decl = {'cpp': f'global {gt} stored = {gv};', 'php': f'global $stored {gt} = {gv};',
                           'js': f'global let stored: {gt} = {gv};'}[flavor]
            return const + global_decl + fn('value', ft) + '{ return ' + value + '; }'
        def write(name, text):
            path = app / (name + '.' + flavor)
            path.write_text(text)
            # Guarantee same-size changes are observable through the current second-resolution mtime contract.
            write.stamp += 1
            os.utime(path, (write.stamp, write.stamp))
        write.stamp = int(time.time())
        write('main', 'reader(); return 8;')
        write('provider', provider())
        write('reader', fn('reader', 'void') + '{ echo LIMIT; ' + local('g', glob) + ' echo ' + var('g') + '; '
              + local('f', 'value()') + ' echo ' + var('f') + '; }')
        write('quiet', fn('quiet', 'void') + '{ return; }')
        process = subprocess.Popen(['php', str(ROOT / 'compiler/tests/incremental_executable.php'), str(app), str(output)],
                                   stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
                                   env={**os.environ, 'XDEBUG_MODE': 'off'})
        def request(action='run'):
            process.stdin.write(json.dumps(action) + '\n'); process.stdin.flush()
            line = process.stdout.readline()
            assert line, process.stderr.read()
            return json.loads(line)
        rows = []
        try:
            def step(name, expected, prepared, emitted, rebuilt=None):
                before_objects = snapshots(build, 'unit-*.o')
                before_sources = {str(p.relative_to(output)): (p.stat().st_mtime_ns, p.read_bytes()) for p in output.rglob('*.cpp')}
                before_exe = (build / 'program').stat().st_mtime_ns if (build / 'program').exists() else None
                result = request()
                assert 'error' not in result, result
                assert result['preparation']['prepared'] == prepared, (name, result)
                assert len(result['emitted_sources']) == emitted, (name, result)
                manifest = json.loads((output / 'manifest.json').read_text())
                units = [output / p for p in manifest['translation_units']]
                assert {str(p.relative_to(output)) for p in output.rglob('*.cpp')} == set(manifest['translation_units'])
                command = write_build(build, units); command[2] = '8'
                graph = build / 'build.ninja'
                # Trace actual changed native jobs, without forcing unrelated compilation.
                graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
                start = time.perf_counter(); native = run(command, build); wall = (time.perf_counter()-start)*1000
                assert native.returncode == 0, native.stdout + native.stderr
                (build / (name + '.log')).write_text(native.stdout + native.stderr)
                after_objects = snapshots(build, 'unit-*.o')
                changed_objects = [key for key, stamp in after_objects.items() if before_objects.get(key) != stamp]
                changed_sources = [str(p.relative_to(output)) for p in output.rglob('*.cpp')
                                   if before_sources.get(str(p.relative_to(output))) != (p.stat().st_mtime_ns, p.read_bytes())]
                if rebuilt is not None:
                    assert len(changed_objects) == rebuilt, (name, changed_objects, native.stdout)
                # Every rebuilt object must correspond to changed generated text (or an explicitly damaged object).
                if name != 'native_recovery':
                    expected_objects = {'unit-' + hashlib.sha256(str((output / p).resolve()).encode()).hexdigest() + '.o'
                                        for p in changed_sources}
                    assert set(changed_objects) == expected_objects, (name, changed_objects, changed_sources)
                if name == 'removed':
                    assert (build / 'program').stat().st_mtime_ns != before_exe, 'Removal did not relink'
                if rebuilt == 0:
                    assert (build / 'program').stat().st_mtime_ns == before_exe, 'No-op relinked'
                executed = run([build / 'program'])
                assert executed.returncode == 8 and executed.stdout == expected, (name, executed)
                totals = {}
                for obj in changed_objects:
                    for event in json.loads((build / obj.replace('.o', '.json')).read_text())['traceEvents']:
                        if event['name'].startswith('Total '):
                            key = event['name'][6:]; totals[key] = totals.get(key, 0) + event['dur']/1000
                link_ms = 0
                if (build / 'program').stat().st_mtime_ns != before_exe:
                    link = [line.split('\t') for line in (build / '.ninja_log').read_text().splitlines()[1:]
                            if line.split('\t')[3] == 'program'][-1]
                    link_ms = int(link[1])-int(link[0])
                rows.append(dict(scenario=name, **result, native_ms=wall, objects=len(changed_objects),
                                 changed_cpp=changed_sources, trace_sum_ms=totals, link_ms=link_ms, stdout=expected))
                return manifest
            step('full', '888', 4, 4, 5)
            step('noop', '888', 0, 0, 0)
            assert request('check_stale') == {'stale_rejected': True}
            write('provider', provider(value='9'))
            step('body', '889', 1, 1, 1)
            write('provider', provider(c=9, value='9'))
            step('constant', '989', 2, 2, 1)  # Constant has no storage; provider C++ stays identical.
            write('provider', provider(c=9, gv='7', value='9'))
            step('global_initializer', '979', 1, 1, 1)
            write('provider', provider(c=9, gt='bool', gv='false', value='9'))
            step('global_type', '909', 2, 2, 2)
            write('provider', provider(c=9, gt='bool', gv='false', ft='bool', value='true'))
            step('signature', '901', 2, 2, 2)
            # Failed parsing and preparation must not publish any C++ or execute a stale binary as success.
            before = {str(p): p.read_bytes() for p in output.rglob('*') if p.is_file()}
            write('provider', fn('value', 'int') + '{')
            assert 'error' in request(), 'Parse error accepted'
            assert before == {str(p): p.read_bytes() for p in output.rglob('*') if p.is_file()}
            write('provider', provider(c=9, gt='bool', gv='false', ft='bool', value='missing()'))
            assert 'error' in request(), 'Preparation error accepted'
            assert before == {str(p): p.read_bytes() for p in output.rglob('*') if p.is_file()}
            assert 'error' in request(), 'Retry lost failed work'
            write('provider', provider())
            step('recovery', '888', 2, 2, 2)
            write('added', fn('extra', 'void') + '{ return; }')
            step('added', '888', 1, 1, 1)
            (app / ('added.' + flavor)).unlink()
            # Removal changes link membership even though surviving objects are unchanged.
            manifest = step('removed', '888', 0, 0)
            # Declaration-only edits have no body to seed preparation work.
            const = lambda v: f'const UNUSED: int = {v};' if flavor == 'js' else f'const int UNUSED = {v};'
            write('declarations', const(1))
            step('declaration_only', '888', 0, 1, 1)
            write('declarations', const(2))
            step('declaration_edit', '888', 0, 1, 0)
            # Native failure remains separate from semantic readiness. Retry must restore missing/corrupt output.
            damaged = output / manifest['translation_units'][0]
            damaged.write_text('#error intentional native failure\n')
            failed = run(['ninja', '-j', '8', '-f', 'build.ninja'], build)
            assert failed.returncode != 0, 'Native failure not exercised'
            step('native_recovery', '888', 0, 0, 1)
            step('final_noop', '888', 0, 0, 0)
            # A failed bundle write must keep emission work after preparation has already drained its queue.
            manifest = json.loads((output / 'manifest.json').read_text())
            provider_path = next(value for key, value in manifest['sources'].items()
                                 if key.endswith('/provider.' + flavor) or key == 'provider.' + flavor)
            blocked = output / provider_path
            saved = blocked.read_bytes(); blocked.unlink(); blocked.mkdir()
            write('aaa', fn('retry_added', 'void') + '{ return; }')
            write('provider', provider(value='7'))
            assert 'error' in request(), 'Publication error not exercised'
            blocked.rmdir(); blocked.write_bytes(saved)
            step('publication_recovery', '887', 0, 2, 2)
            step('publication_noop', '887', 0, 0, 0)
        finally:
            process.stdin.close(); process.wait(timeout=10)
            assert process.returncode == 0, process.stderr.read()
        results[flavor] = rows
        print(flavor + ': end-to-end edits, exact object selection, failures and recovery passed', flush=True)
    (work / 'results.json').write_text(json.dumps(results, indent=2))
    return results


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-incremental-exe-') as directory:
            prove(Path(directory))
