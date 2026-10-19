#!/usr/bin/env python3
"""Prepared observation -> opaque bindings -> published layout -> native cache."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            published, cache = out / 'published', out / 'cache'
            records = []
            def publish(label, revision, alignment=8, broken=0):
                runner.run(label, ['php', APP / 'tests/fixtures/prelower_layout.php', out / 'source', published, revision, alignment, broken])
            def build(label, expected=0, *flags):
                payload = runner.run(label, ['php', APP / 'tools/build_cpp.php', published, cache, '--no-pch', *flags],
                                     expected=expected, cli_reason='Native builder process contract')
                if expected:
                    assert (cache / '.scpp-native.status').read_text() == 'pending\n'
                    return None
                result = json.loads(payload)
                records.append(dict(label=label, **result))
                return result
            def execute(label, build_result, revision):
                output = runner.run(label, [build_result['executable']], expected=revision).decode().splitlines()
                assert len(output) == 4 and output[0].split()[1:] == output[1].split()[1:], output
                assert output[2].split()[1:] == output[3].split()[1:], output
                assert int(output[2].split()[5]) == 1, output
                assert int(output[0].split()[1]) > 0, output
                return output
            publish('publish-cold', 0)
            cold = build('cold')
            counts = execute('cold-execute', cold, 0)
            assert cold['layouts_built'] == 1
            before = {p: (p.stat().st_ino, p.stat().st_mtime_ns) for p in (cache / 'artifacts').iterdir()}
            warm = build('warm')
            assert warm['layouts_reused'] == 1 and warm['compiled'] == 0 and warm['linked'] == 0, warm
            assert before == {p: (p.stat().st_ino, p.stat().st_mtime_ns) for p in before}
            publish('publish-edit', 3)
            edit = build('body-edit')
            assert edit['layouts_reused'] == 1 and edit['compiled'] == 1, edit
            execute('edited-execute', edit, 3)
            # Repair a modified native-cache header, never a published source artifact.
            headers = list((cache / 'artifacts').glob('*.hpp'))
            assert len(headers) == 1
            headers[0].write_text('corrupt\n')
            repair = build('corrupt-layout-repair')
            assert repair['layouts_built'] == 1
            execute('repair-execute', repair, 3)
            optimized = build('optimized', 0, '-O2')
            assert optimized['layouts_built'] == 1
            execute('optimized-execute', optimized, 3)
            assert build('restore-O0')['layouts_reused'] == 1
            publish('publish-layout-dependency', 3, 64)
            changed = build('layout-dependency')
            assert changed['layouts_built'] == 1, changed
            execute('layout-dependency-execute', changed, 3)
            assert any('_SIZE 64' in h.read_text() for h in (cache / 'artifacts').glob('*.hpp'))
            publish('publish-broken-layout', 3, 64, 1)
            build('broken-layout', 1)
            publish('publish-restored-layout', 3, 64)
            recovered = build('recovered-layout')
            assert recovered['layouts_reused'] == 1 and recovered['compiled'] == 0, recovered
            execute('recovered-execute', recovered, 3)
            # Exercise the actual generated wrappers/bridges under sanitizers too.
            layout_header = next(h for h in (cache / 'artifacts').glob('*.hpp') if '_SIZE 64' in h.read_text())
            manifest = json.loads((published / '.scpp-output.manifest').read_text())
            units = [published / f['path'] for f in manifest['files'] if f['kind'] == 'translation_unit']
            runner.run('sanitized-build', ['clang++', '-std=c++23', '-O1', '-g0', '-fsanitize=address,undefined',
                       '-fno-omit-frame-pointer', '-include', layout_header, '-I' + str(published),
                       '-I' + str(ROOT / 'runtime/include'), *units, '-o', out / 'sanitized'])
            sanitized_output = runner.run('sanitized-execute', ['env', 'ASAN_OPTIONS=detect_leaks=0', out / 'sanitized'], expected=3).decode().splitlines()
            assert sanitized_output == counts, sanitized_output
            (out / 'summary.json').write_text(json.dumps({'builds': records, 'counters': counts}, indent=2) + '\n')
        run_tasks([Task('layout-integration', proof)])


if __name__ == '__main__':
    main()
