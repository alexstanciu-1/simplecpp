#!/usr/bin/env python3
"""Focused data-construction preparation and generated-C++ execution proofs."""
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
    parser.add_argument('--no-pch', action='store_true')
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    suites = ['s2s', 'data_preparation', 'initialization', 'callables', 'type_catalog']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 's2s':
                command.append(programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        fixtures = [item for item in json.loads((programs / 'executions.json').read_text())
                    if Path(item['path']).stem.startswith('data_')]
        if not fixtures:
            raise RuntimeError('No data-construction fixtures exported')
        compiler = CppFixtureCompiler(runner, APP.parents[1], output,
                                      pch=not args.no_pch, return_type_errors=True, runtime=True)
        def execute(item):
            source = Path(item['path'])
            executable = source.with_suffix('.program')
            compiler.compile(source.stem + '-compile', source, executable)
            runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])
        def runtime_proof():
            executable = output / 'data-initializer-runtime'
            compiler.compile('runtime-input-compile', APP / 'tests/fixtures/data_initializer_runtime.cpp', executable)
            runner.run('runtime-input-execute', [executable])
        run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item)) for item in fixtures]
                  + [Task('runtime-input-lifetime', runtime_proof)], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, cpp_pch=not args.no_pch, php_suites=len(suites),
        generated_programs=len(fixtures), runtime_input_proofs=1), indent=2) + '\n')


if __name__ == '__main__':
    main()
