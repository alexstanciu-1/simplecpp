#!/usr/bin/env python3
"""Focused structured-control proofs using the shared PHP and native-program task pool."""
import argparse
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--no-pch', action='store_true', help='Compile without shared precompiled runtime headers')
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    with CommandRunner(output / 'logs', ROOT.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        suites = ['control_flow', 'fallthrough', 'preparation_dispatch', 'preparation_recovery',
                  'ast', 'ast_invariants', 'incremental_cpp', 'token_generations',
                  'conversions', 'specialization_dispatch', 'operators', 'tokenizer']

        def suite(name):
            command = ['php', ROOT / 'tests' / (name + '.php')]
            if name == 'control_flow':
                command.append(programs)
            runner.run(name, command)

        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        fixtures = json.loads((programs / 'executions.json').read_text())

        compiler = CppFixtureCompiler(runner, ROOT.parents[1], output,
                                      pch=not args.no_pch, return_type_errors=True)

        def execute(fixture):
            source = Path(fixture['path'])
            executable = source.with_suffix('.program')
            compiler.compile(source.stem + '-compile', source, executable)
            runner.run(source.stem + '-execute', [executable], expected=fixture['exit_code'])

        run_tasks([Task(Path(fixture['path']).stem, lambda fixture=fixture: execute(fixture))
                   for fixture in fixtures], jobs=12)
        (output / 'summary.json').write_text(json.dumps(
            dict(passed=True, cpp_pch=not args.no_pch, php_suites=len(suites), generated_programs=len(fixtures)), indent=2) + '\n')


if __name__ == '__main__':
    main()
