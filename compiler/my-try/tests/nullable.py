#!/usr/bin/env python3
"""Focused nullable proofs through the shared task runner and S2S fixture export."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    suites = ['s2s', 'type_catalog', 'constructed_type', 'conversions',
              'incremental_cpp', 'preparation_recovery', 'ast_invariants',
              'specialization_dispatch', 'nullable', 'control_flow', 'value_probes']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 's2s':
                command.append(programs)
            runner.run(name, command)

        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        fixtures = [item for item in json.loads((programs / 'executions.json').read_text())
                    if Path(item['path']).stem.startswith('nullable_')]
        if not fixtures:
            raise RuntimeError('No nullable fixtures exported')

        def execute(item):
            source = Path(item['path'])
            executable = source.with_suffix('.program')
            runner.run(source.stem + '-compile', ['clang++', '-std=c++20', '-Werror=return-type',
                       '-I', APP.parents[1] / 'runtime/include', source, '-o', executable])
            runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])

        run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item)) for item in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(
        dict(passed=True, php_suites=len(suites), generated_programs=len(fixtures)), indent=2) + '\n')


if __name__ == '__main__':
    main()
