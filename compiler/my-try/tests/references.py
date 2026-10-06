#!/usr/bin/env python3
"""Focused alias/reference-return preparation and generated-C++ execution proof."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--php-only', action='store_true')
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    suites = ['references', 'reference_model', 'container_calls', 'callables',
              'incremental_cpp', 'preparation_recovery', 'mutations', 'indexing']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'references':
                command.append(programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        fixtures = json.loads((programs / 'executions.json').read_text())
        if not args.php_only:
            compiler = CppFixtureCompiler(runner, APP.parents[1], output,
                                          pch=True, return_type_errors=True, runtime=True)
            def execute(item):
                source = Path(item['path'])
                executable = source.with_suffix('.program')
                compiler.compile(source.stem + '-compile', source, executable)
                runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])
            run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item))
                       for item in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, php_suites=len(suites), generated_programs=len(fixtures),
        cpp_executed=not args.php_only, native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
