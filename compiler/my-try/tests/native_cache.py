#!/usr/bin/env python3
"""Persistent native object/PCH/link cache checks using real compiler processes."""
import argparse
import fcntl
import json
import hashlib
import shutil
import shlex
from pathlib import Path
import sys
import time

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12, journals=[output / 'commands.json']) as runner:
        def proof():
            runner.run('dependency-parser', ['php', APP / 'tests/fixtures/native_depfiles.php'])
            source = output / 'source'
            source.mkdir()
            main_path = source / 'main.phs'
            library = source / 'library.phs'
            main_path.write_text('return answer();')
            library.write_text('function answer(): int { return 7; }')
            published = output / 'published'
            cache = output / 'cache'
            observations = []
            def publish(name, expected=0):
                runner.run(name, ['php', APP / 'main.php', '--s2s', source, '--entry', 'main.phs', '--output', published],
                           expected=expected, cli_reason='Publication CLI contract')
            def build(name, expected=0, *options):
                start = time.monotonic()
                payload = runner.run(name, ['php', APP / 'tools/build_cpp.php', published, cache, *options],
                                     expected=expected, timeout=300, cli_reason='Managed native processes require PHP CLI')
                if expected:
                    if payload:
                        raise RuntimeError('Failure exposed a successful native result')
                    return None
                result = json.loads(payload)
                observations.append(dict(name=name, seconds=round(time.monotonic()-start, 3), **result))
                return result
            def execute(name, result, code):
                runner.run(name, [result['executable']], expected=code)
            publish('publish-first')
            first = build('cold')
            if first['compiled'] != 5 or first['pch_built'] != 1 or first['linked'] != 1:
                raise RuntimeError('Cold build did not produce expected units/PCH/link: ' + repr(first))
            execute('cold-execute', first, 7)
            before = {p: (p.stat().st_ino, p.stat().st_mtime_ns) for p in (cache / 'artifacts').iterdir()}
            warm = build('warm')
            if (warm['compiled'], warm['pch_built'], warm['linked']) != (0, 0, 0):
                raise RuntimeError('Warm build repeated native actions: ' + repr(warm))
            if {p: (p.stat().st_ino, p.stat().st_mtime_ns) for p in before} != before:
                raise RuntimeError('Warm cache rewrote artifacts')
            debug = build('native-debug', 0, '--native-debug')
            if (debug['compiled'], debug['pch_built'], debug['linked']) != (5, 1, 1):
                raise RuntimeError('Native debug did not isolate compilation/PCH/link configuration')
            execute('native-debug-execute', debug, 7)
            for label, result, present in [('default', first, False), ('debug', debug, True)]:
                sections = runner.run(label + '-sections', ['readelf', '-SW', result['executable']])
                if (b'.debug_info' in sections) != present:
                    raise RuntimeError('Unexpected native debug sections: ' + label)
            debug_warm = build('native-debug-warm', 0, '--native-debug')
            restored = build('default-after-debug')
            for result in [debug_warm, restored]:
                if (result['compiled'], result['pch_built'], result['linked']) != (0, 0, 0):
                    raise RuntimeError('Switching debug configuration lost cached artifacts')
            if restored['executable'] != first['executable']:
                raise RuntimeError('Default build reused the debug executable')
            library.write_text('function answer(): int { return 9; }')
            publish('publish-body')
            edited = build('body-edit')
            if (edited['compiled'], edited['pch_built'], edited['linked']) != (1, 0, 1):
                raise RuntimeError('Body edit did not isolate owning object: ' + repr(edited))
            execute('body-execute', edited, 9)
            nopch = build('no-pch', 0, '--no-pch')
            execute('no-pch-execute', nopch, 9)
            optimized = build('optimized', 0, '-O2')
            execute('optimized-execute', optimized, 9)
            if optimized['pch_built'] != 1 or optimized['compiled'] != 5:
                raise RuntimeError('Optimization did not invalidate native configuration')
            # Reuse O0 again, then check missing artifacts and executable permission repair.
            stable = build('stable-again')
            Path(stable['executable']).chmod(0o600)
            repaired_mode = build('mode-repair')
            execute('mode-execute', repaired_mode, 9)
            Path(repaired_mode['executable']).unlink()
            rebuilt_link = build('missing-link')
            execute('missing-link-execute', rebuilt_link, 9)
            for obj in (cache / 'artifacts').glob('*.o'):
                obj.unlink()
            rebuilt_objects = build('missing-objects')
            if rebuilt_objects['compiled'] != 5:
                raise RuntimeError('Missing object files were not rebuilt')
            execute('missing-objects-execute', rebuilt_objects, 9)
            # Removing a source removes its object from link membership, not by scanning cache files.
            main_path.write_text('return 11;')
            library.unlink()
            publish('publish-deletion')
            deleted = build('source-deletion')
            execute('deletion-execute', deleted, 11)
            main_path.write_text('return answer();')
            library.write_text('function answer(): int { return 9; }')
            publish('publish-restore')
            build('source-restore')
            # A failed source revision cannot use the last successful native build.
            library.write_text('function answer(): int { return missing(); }')
            publish('publish-failed', 1)
            build('build-pending-source', 1)
            if (cache / '.scpp-native.status').read_text() != 'pending\n':
                raise RuntimeError('Failed build left native readiness')
            library.write_text('function answer(): int { return 9; }')
            publish('publish-repair')
            repaired = build('repair')
            execute('repair-execute', repaired, 9)
            if repaired['compiled'] or repaired['pch_built'] or repaired['linked']:
                raise RuntimeError('Recovery discarded valid cached actions')
            # Shared readers and another builder both exclude a writer of this cache.
            with (cache / '.scpp-native.lock').open('rb') as lock:
                ready = (cache / '.scpp-native.status').read_bytes()
                for mode in (fcntl.LOCK_SH, fcntl.LOCK_EX):
                    fcntl.flock(lock, mode)
                    build('contended-' + str(mode), 1)
                    if (cache / '.scpp-native.status').read_bytes() != ready:
                        raise RuntimeError('Contended builder changed cache state')
                    fcntl.flock(lock, fcntl.LOCK_UN)
            (output / 'observations.json').write_text(json.dumps(observations, indent=2) + '\n')

            # Isolated runtime copy: no repository runtime inputs are modified by the test.
            runtime = output / 'runtime-copy'
            shutil.copytree(APP.parents[1] / 'runtime/include', runtime)
            isolated = output / 'isolated-cache'
            driver = APP / 'tests/fixtures/native_cache_driver.php'
            def isolated_build(name, compiler='/usr/bin/clang++', expected=0):
                payload = runner.run(name, ['php', driver, published, isolated, runtime, compiler],
                    expected=expected, timeout=300, cli_reason='Managed compiler process and isolated runtime fixture')
                return json.loads(payload) if not expected else None
            isolated_first = isolated_build('runtime-cold')
            execute('runtime-cold-execute', isolated_first, 9)
            header = runtime / 'scpp/int_t.hpp'
            header.write_bytes(header.read_bytes() + b'\n// isolated runtime dependency edit\n')
            runtime_edit = isolated_build('runtime-edit')
            if runtime_edit['pch_built'] != 1 or runtime_edit['compiled'] < 2:
                raise RuntimeError('Runtime dependency failed to invalidate PCH/consumers')
            execute('runtime-edit-execute', runtime_edit, 9)

            # Fail a real compile action, then retry with identical compiler identity/arguments.
            flag = output / 'fail-native-action'
            flag.touch()
            wrapper = output / 'clang-wrapper'
            wrapper.write_text('#!/bin/sh\nfor arg in "$@"; do\n'
                + '  if [ "$arg" = "-c" ] && [ -f ' + shlex.quote(str(flag)) + ' ]; then echo injected-native-failure >&2; exit 42; fi\n'
                + 'done\nexec /usr/bin/clang++ "$@"\n')
            wrapper.chmod(0o700)
            isolated_build('native-action-failure', str(wrapper), 1)
            if (isolated / '.scpp-native.status').read_text() != 'pending\n':
                raise RuntimeError('Compile failure published native readiness')
            if list(isolated.glob('.scpp-stage-*')):
                raise RuntimeError('Compile failure leaked owned staging files')
            flag.unlink()
            retry = isolated_build('native-action-retry', str(wrapper))
            execute('native-action-retry-execute', retry, 9)
            if retry['pch_built'] != 0:
                raise RuntimeError('Retry discarded a valid completed PCH')

            # A producer can publish malformed C++: test actual Clang failure, not only source preparation.
            manifest_file = published / '.scpp-output.manifest'
            original_manifest = manifest_file.read_bytes()
            manifest = json.loads(original_manifest)
            row = next(row for row in manifest['files'] if row['kind'] == 'translation_unit')
            broken_path = published / row['path']
            original_bytes = broken_path.read_bytes()
            broken_bytes = original_bytes + b'\n#error deliberate-native-cache-test\n'
            broken_path.write_bytes(broken_bytes)
            row['sha256'] = hashlib.sha256(broken_bytes).hexdigest()
            manifest_bytes = json.dumps(manifest).encode()
            manifest_file.write_bytes(manifest_bytes)
            status_file = published / '.scpp-output.status'
            status_file.write_text('scpp-output-v1:ready:' + hashlib.sha256(manifest_bytes).hexdigest() + '\n')
            build('clang-rejection', 1)
            broken_path.write_bytes(original_bytes)
            manifest_file.write_bytes(original_manifest)
            status_file.write_text('scpp-output-v1:ready:' + hashlib.sha256(original_manifest).hexdigest() + '\n')
            build('clang-recovery')
            runner.run('native-owner-conversion', ['php', APP.parents[1] / 'tools/php_portability/convert.php',
                APP / '06_native', output / 'converted-owner'], cli_reason='Converter CLI writes its explicit output directory')
        run_tasks([Task('native-cache', proof)], jobs=12)
        (output / 'summary.json').write_text(json.dumps(dict(passed=True, native_compiler_rebuilt=False,
            persistent_native_cache=True), indent=2) + '\n')


if __name__ == '__main__':
    main()
