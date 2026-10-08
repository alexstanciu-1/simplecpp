#!/usr/bin/env python3
"""Focused PHP-host model migration checks; no native build, conversion or timing benchmark."""
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
    suites = ['declaration_model', 'member_owners', 'trait_model', 'relationships', 'ast', 'ast_invariants', 'parse_collection',
              'collected_roles', 'preparation_work', 'preparation_recovery',
              'incremental_cpp', 'source_constants', 'enum_model', 'reference_model',
              'token_cleanup', 'project_cpp', 'specialization_dispatch', 's2s']
    programs = output / 'programs'
    programs.mkdir()
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 's2s':
                command.append(programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, php_suites=len(suites), generated_cpp_execution=False,
        native_compiler_rebuilt=False, conversion_run=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
