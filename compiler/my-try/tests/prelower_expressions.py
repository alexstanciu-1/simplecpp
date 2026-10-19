#!/usr/bin/env python3
"""Ordinary emitted runtime ABI operations: counters and cached fragment replay."""
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
    parser.add_argument('--initializers', action='store_true')
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            results = {}
            unfiltered = {}
            def execute(label, executable, sanitized=False):
                environment = ['env', 'ASAN_OPTIONS=detect_leaks=0'] if sanitized else ['env']
                if args.initializers:
                    unfiltered[label] = runner.run(label + '-unfiltered-run', [*environment, executable]).decode()
                    environment.append('SCPP_PROOF_NO_TRACE=1')
                results[label] = runner.run(label + '-run', [*environment, executable]).decode()
            for mode in ['direct', 'opaque']:
                folder = out / mode
                runner.run(mode + '-emit', ['php', APP / 'tests/fixtures/prelower_expressions.php', out / ('source-' + mode), folder, mode, 'initializers' if args.initializers else 'expressions'])
                for optimization in ['-O0', '-O2']:
                    label = mode + optimization
                    built = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php', folder, out / ('cache-' + mode), '--no-pch', optimization], cli_reason='Native build process'))
                    execute(label, built['executable'])
                    warm = json.loads(runner.run(label + '-warm', ['php', APP / 'tools/build_cpp.php', folder, out / ('cache-' + mode), '--no-pch', optimization], cli_reason='Native build process'))
                    assert warm['compiled'] == 0 and warm['linked'] == 0, warm
                manifest = json.loads((folder / '.scpp-output.manifest').read_text())
                units = [folder / item['path'] for item in manifest['files'] if item['kind'] == 'translation_unit']
                layouts = list((out / ('cache-' + mode) / 'artifacts').glob('*.hpp'))
                flags = ['-include', layouts[0]] if mode == 'opaque' else []
                runner.run(mode + '-sanitized-build', ['clang++', '-std=c++23', '-O1', '-g0',
                    '-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-elide-constructors',
                    '-I' + str(folder), '-I' + str(ROOT / 'runtime/include'), *flags, *units, '-o', out / (mode + '-sanitized')])
                execute(mode + '-sanitized', out / (mode + '-sanitized'), sanitized=True)
            if args.initializers:
                # Native trace depth changes diagnostic allocation totals, never payload
                # lifetime or evaluation. Keep raw evidence and compare everything else.
                def without_diagnostic_allocations(output):
                    lines = []
                    for line in output.splitlines():
                        fields = line.split()
                        if fields[0] in ('value-conversion-failure', 'key-conversion-failure'):
                            assert fields[1] == fields[3], line
                            fields = fields[:1] + fields[4:]
                        lines.append(' '.join(fields))
                    return '\n'.join(lines)
                assert len({without_diagnostic_allocations(value) for value in unfiltered.values()}) == 1, unfiltered
                (out / 'unfiltered-summary.json').write_text(json.dumps(unfiltered, indent=2) + '\n')
            assert len(set(results.values())) == 1, results
            assert int(results['direct-O0'].split()[5]) > 0, results
            (out / 'summary.json').write_text(json.dumps(results, indent=2) + '\n')
        run_tasks([Task('ordinary-expressions', proof)])
if __name__ == '__main__':
    main()
