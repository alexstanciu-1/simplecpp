#!/usr/bin/env python3
"""Registered JSON source API proofs against freshly built runtime/JSON objects."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
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
    suites = ['callables', 'observed_lookup', 'initialization', 'data_preparation',
              'results', 'take', 'incremental_cpp', 'preparation_recovery']
    with CommandRunner(output / 'logs', ROOT, jobs=12) as runner:
        run_tasks([Task(name, lambda name=name: runner.run(name, ['php', APP / 'tests' / (name + '.php')]))
                   for name in suites] + [Task('json', lambda: runner.run('json', ['php', APP / 'tests/json.php', programs]))], jobs=12)
        compiler = CppFixtureCompiler(runner, ROOT, output, pch=not args.no_pch,
                                      return_type_errors=True, runtime=True)
        json_object = output / 'json.o'
        runner.run('json-runtime-build', ['clang++', '-std=c++23', '-DSCPP_LANGUAGE_TARGET_PHP=1',
                   '-I', ROOT / 'runtime/include', '-pthread', '-c',
                   ROOT / 'runtime/include/modules/json/json.cpp', '-o', json_object], timeout=240)
        compiler.link_flags.append(json_object)
        fixtures = json.loads((programs / 'executions.json').read_text())

        def execute(item):
            source = Path(item['path'])
            executable = source.with_suffix('.program')
            compiler.compile(source.stem + '-compile', source, executable)
            runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])

        run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item)) for item in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, php_suites=len(suites) + 1,
        generated_programs=len(fixtures), cpp_pch=not args.no_pch, native_compiler_rebuild=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
