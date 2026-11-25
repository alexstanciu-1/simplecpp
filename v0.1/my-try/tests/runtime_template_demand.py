#!/usr/bin/env python3
"""Automatic native-template demand from runtime bindings, without source-template syntax."""
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
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--alternate-storage', action='store_true')
    parser.add_argument('--output', action='store_true')
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            results, builds = {}, []
            for mode in ['direct', 'composed']:
                folder = out / mode
                cache = out / ('cache-' + mode)
                runner.run(mode + '-emit', ['php', APP / 'tests/runtime_template_demand.php',
                    out / ('source-' + mode), folder, mode, 'alternate' if args.alternate_storage else 'default', 'output' if args.output else 'default'])
                if args.alternate_storage:
                    assert (folder / 'binding_shared.hpp').is_file(), 'Alternate binding was not enabled'
                for optimization in ['-O0', '-O2']:
                    label = mode + optimization
                    command = ['php', APP / 'tools/build_cpp.php', folder, cache, '--no-pch', optimization]
                    cold = json.loads(runner.run(label + '-build', command, cli_reason='Native builder process'))
                    builds.append(dict(label=label, **cold))
                    results[label] = runner.run(label + '-run', [cold['executable']]).decode()
                    warm = json.loads(runner.run(label + '-warm', command, cli_reason='Native builder process'))
                    assert warm['compiled'] == 0 and warm['linked'] == 0, warm
                manifest = json.loads((folder / '.scpp-output.manifest').read_text())
                units = [folder / row['path'] for row in manifest['files'] if row['kind'] == 'translation_unit']
                layouts = {path.read_text(): path for path in (cache / 'artifacts').glob('*.hpp')}
                flags = [part for path in layouts.values() for part in ['-include', path]]
                binary = out / (mode + '-sanitized')
                runner.run(mode + '-sanitized-build', ['clang++', '-std=c++23', '-g0', '-O1',
                    '-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-elide-constructors',
                    '-I' + str(ROOT / 'runtime/include'), '-I' + str(folder), *flags, *units, '-o', binary])
                results[mode + '-sanitized'] = runner.run(mode + '-sanitized-run',
                    ['env', 'ASAN_OPTIONS=detect_leaks=0', binary]).decode()
            if args.output:
                assert all(text.startswith('output ') for text in results.values()), results
                for variant in ['-O0', '-O2', '-sanitized']:
                    assert results['direct' + variant] == results['composed' + variant], results
                assert all(text.endswith('recipe-boundary 0 0 0 0 0 0 0\n') for text in results.values()), results
            else:
                assert set(results.values()) == {'recipe-boundary 0 0 0 0 0 0 0\n'}, results
            (out / 'summary.json').write_text(json.dumps({'counters': results, 'builds': builds}, indent=2) + '\n')
        run_tasks([Task('template-demand', proof)])


if __name__ == '__main__':
    main()
