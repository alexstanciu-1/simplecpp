#!/usr/bin/env python3
"""Registry-backed concrete arguments, production lifecycle generation and layered native caching."""
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
    modes = parser.add_mutually_exclusive_group()
    modes.add_argument('--source-fields', action='store_true')
    modes.add_argument('--source-arguments', action='store_true')
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    batches = 4 if args.source_arguments else 3
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            results, builds = {}, []
            emission = 0
            def emit(mode, revision=0, alignment=8):
                nonlocal emission
                emission += 1
                runner.run(f'{mode}-emit-{emission}-{revision}-{alignment}', ['php', APP / 'tests/fixtures/prelower_substitution.php',
                    out / ('source-' + mode), out / mode, mode, revision, alignment, 'arguments' if args.source_arguments else ('fields' if args.source_fields else 'bindings')])
            def build(mode, label, optimization='-O0'):
                result = json.loads(runner.run(label, ['php', APP / 'tools/build_cpp.php', out / mode,
                    out / ('cache-' + mode), '--no-pch', optimization], cli_reason='Native builder process'))
                builds.append(dict(label=label, **result))
                return result
            for mode in ['direct', 'composed']:
                emit(mode)
                for optimization in ['-O0', '-O2']:
                    label = mode + optimization
                    result = build(mode, label, optimization)
                    results[label] = runner.run(label + '-run', [result['executable']]).decode()
                    if mode == 'composed':
                        assert result['layouts_built'] == batches, result
                    warm = build(mode, label + '-warm', optimization)
                    assert warm['compiled'] == 0 and warm['linked'] == 0, warm
                    assert warm['layouts_reused'] == (batches if mode == 'composed' else 0), warm
                folder = out / mode
                manifest = json.loads((folder / '.scpp-output.manifest').read_text())
                units = [folder / row['path'] for row in manifest['files'] if row['kind'] == 'translation_unit']
                # Both optimization configurations emit identical layouts. Include one
                # header for each distinct content (independent macro definitions).
                layouts = list((out / ('cache-' + mode) / 'artifacts').glob('*.hpp'))
                contents = {}
                for path in layouts:
                    contents.setdefault(path.read_text(), path)
                flags = [part for path in contents.values() for part in ['-include', path]]
                runner.run(mode + '-sanitized-build', ['clang++', '-std=c++23', '-g0', '-O1',
                    '-fsanitize=address,undefined', '-fno-elide-constructors', '-fno-omit-frame-pointer',
                    '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder), *flags, *units, '-o', out / (mode + '-sanitized')])
                results[mode + '-sanitized'] = runner.run(mode + '-sanitized-run',
                    ['env', 'ASAN_OPTIONS=detect_leaks=0', out / (mode + '-sanitized')]).decode()
            assert len(set(results.values())) == 1, results
            assert 'references 0 0 0 0 0 0 0' in results['composed-O0'], results
            shared_before = {}
            if args.source_arguments:
                shared_before = {str(path.relative_to(out / 'composed')): path.read_bytes()
                    for directory in ['specializations', 'types']
                    for path in (out / 'composed' / directory).glob('*') if path.is_file()}
            emit('composed', 3)
            for path, contents in shared_before.items():
                assert (out / 'composed' / path).read_bytes() == contents, path
            edited = build('composed', 'body-edit')
            assert edited['layouts_reused'] == batches and edited['compiled'] == 1, edited
            runner.run('body-edit-run', [edited['executable']], expected=0 if args.source_arguments else 3)
            emit('composed', 3, 64)
            changed = build('composed', 'layout-edit')
            assert changed['layouts_built'] >= 1, changed
            runner.run('layout-edit-run', [changed['executable']], expected=0 if args.source_arguments else 3)
            emit('composed')
            restored = build('composed', 'restore')
            assert restored['layouts_reused'] == batches and restored['compiled'] == 0 and restored['linked'] == 0, restored
            runner.run('restore-run', [restored['executable']])
            (out / 'summary.json').write_text(json.dumps({'counters': results, 'builds': builds}, indent=2) + '\n')
        tasks = [Task('physical-substitution', proof)]
        if args.source_arguments:
            tasks.append(Task('source-header-cycle', lambda: runner.run('source-header-cycle',
                ['php', APP / 'tests/fixtures/prelower_source_cycles.php', out / 'source-cycle'])))
        run_tasks(tasks)


if __name__ == '__main__':
    main()
