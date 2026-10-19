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
    parser.add_argument('--results', required=True, type=Path)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            results = {}
            for mode in ['direct', 'opaque']:
                folder = out / mode
                runner.run(mode + '-emit', ['php', APP / 'tests/fixtures/prelower_expressions.php', out / ('source-' + mode), folder, mode])
                for optimization in ['-O0', '-O2']:
                    label = mode + optimization
                    built = json.loads(runner.run(label + '-build', ['php', APP / 'tools/build_cpp.php', folder, out / ('cache-' + mode), '--no-pch', optimization], cli_reason='Native build process'))
                    results[label] = runner.run(label + '-run', [built['executable']]).decode()
                    warm = json.loads(runner.run(label + '-warm', ['php', APP / 'tools/build_cpp.php', folder, out / ('cache-' + mode), '--no-pch', optimization], cli_reason='Native build process'))
                    assert warm['compiled'] == 0 and warm['linked'] == 0, warm
                manifest = json.loads((folder / '.scpp-output.manifest').read_text())
                units = [folder / item['path'] for item in manifest['files'] if item['kind'] == 'translation_unit']
                layouts = list((out / ('cache-' + mode) / 'artifacts').glob('*.hpp'))
                flags = ['-include', layouts[0]] if mode == 'opaque' else []
                runner.run(mode + '-sanitized-build', ['clang++', '-std=c++23', '-O1', '-g0',
                    '-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-elide-constructors',
                    '-I' + str(folder), '-I' + str(ROOT / 'runtime/include'), *flags, *units, '-o', out / (mode + '-sanitized')])
                results[mode + '-sanitized'] = runner.run(mode + '-sanitized-run',
                    ['env', 'ASAN_OPTIONS=detect_leaks=0', out / (mode + '-sanitized')]).decode()
            assert len(set(results.values())) == 1, results
            assert int(results['direct-O0'].split()[5]) > 0, results
            (out / 'summary.json').write_text(json.dumps(results, indent=2) + '\n')
        run_tasks([Task('ordinary-expressions', proof)])
if __name__ == '__main__':
    main()
