#!/usr/bin/env python3
"""Ownership model/admission proofs and existing empty-handle C++ lowering."""
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
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    suites = ['ownership_model', 'ownership', 'type_catalog', 'constructed_type',
              'capabilities', 'initialization', 'result_model', 'cleanup', 'reference_model',
              'value_probes', 'operators', 'nullable']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'ownership':
                command.append(programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        fixtures = json.loads((programs / 'executions.json').read_text())
        compiler = CppFixtureCompiler(runner, APP.parents[1], output,
                                      pch=False, return_type_errors=True, runtime=True)
        def execute(item):
            source = Path(item['path'])
            executable = source.with_suffix('.program')
            compiler.compile(source.stem + '-compile', source, executable)
            runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])
        run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item))
                   for item in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, php_suites=len(suites), generated_programs=len(fixtures),
        native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
