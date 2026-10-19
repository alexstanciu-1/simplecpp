#!/usr/bin/env python3
"""O0 regression for borrowed output/key adapters, reusing existing semantic proofs."""
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
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof(group):
            results = {}
            for mode in ['direct', 'composed']:
                name = group + '-' + mode
                folder = out / name
                source = out / (name + '-source')
                if group == 'output':
                    emit = [APP / 'tests/runtime_template_demand.php', source, folder,
                            mode, 'alternate', 'output']
                else:
                    emit = [APP / 'tests/fixtures/prelower_substitution.php', source,
                            folder, mode, 0, 8, group]
                runner.run(name + '-emit', ['php', *emit])
                if mode == 'composed' and group != 'output':
                    for unit in (folder / 'units').rglob('*.cpp'):
                        assert '"scpp/keyed_path.hpp"' not in unit.read_text(), unit
                command = ['php', APP / 'tools/build_cpp.php', folder,
                           out / (name + '-cache'), '--no-pch', '-O0']
                build = json.loads(runner.run(name + '-build', command, cli_reason='Native builder process'))
                results[mode] = runner.run(name + '-run', [build['executable']]).decode()
                warm = json.loads(runner.run(name + '-warm', command, cli_reason='Native builder process'))
                assert warm['compiled'] == 0 and warm['linked'] == 0, warm
            assert results['direct'] == results['composed'], results
            if group == 'output':
                assert results['composed'].startswith('output '), results
                assert results['composed'].endswith('recipe-boundary 0 0 0 0 0 0 0\n'), results
            else:
                assert 'references 0 0 0 0 0 0 0' in results['composed'], results
                if group == 'indexing':
                    assert 'indexed-references 0 0 0 0 0 0 0' in results['composed'], results
            (out / (group + '.json')).write_text(json.dumps(results, indent=2) + '\n')
        run_tasks([Task(group, lambda group=group: proof(group))
                   for group in ['output', 'indexing', 'compound']])


if __name__ == '__main__':
    main()
