#!/usr/bin/env python3
"""Focused Result/extraction/take proofs; PHP compiler and generated/runtime C++."""
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
    suites = ['s2s', 'results', 'take', 'tokenizer', 'result_model', 'type_catalog', 'constructed_type',
              'conversions', 'nullable', 'incremental_cpp', 'preparation_recovery']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 's2s':
                command.append(programs)
            runner.run(name, command)

        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        fixtures = [item for item in json.loads((programs / 'executions.json').read_text())
                    if Path(item['path']).stem.startswith('result_')]
        if not fixtures:
            raise RuntimeError('No Result fixtures exported')
        compiler = CppFixtureCompiler(runner, APP.parents[1], output,
                                      pch=not args.no_pch, return_type_errors=True)
        compiler.flags.extend(['-I', APP.parents[1]])

        def execute(item):
            source = Path(item['path'])
            executable = source.with_suffix('.program')
            compiler.compile(source.stem + '-compile', source, executable)
            runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])

        def runtime_proof(name):
            source = APP.parents[1] / 'tests/runtime/wrappers/level_01' / (name + '.cpp')
            executable = output / name
            compiler.compile(name + '-compile', source, executable)
            runner.run(name + '-execute', [executable])

        tasks = [Task(Path(item['path']).stem, lambda item=item: execute(item)) for item in fixtures]
        runtime_names = ['runtime_wrappers_001_result_wrapper_states', 'runtime_wrappers_003_take_helper']
        tasks.extend(Task(name, lambda name=name: runtime_proof(name)) for name in runtime_names)
        run_tasks(tasks, jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, cpp_pch=not args.no_pch, php_suites=len(suites),
        generated_programs=len(fixtures), runtime_programs=len(runtime_names)), indent=2) + '\n')


if __name__ == '__main__':
    main()
