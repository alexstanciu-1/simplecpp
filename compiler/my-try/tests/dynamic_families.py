#!/usr/bin/env python3
"""Shared boxed-family boundaries, operators, selections and Result payload proofs."""
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
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        suites = ['dynamic_model', 'initialization', 'data_preparation', 'dynamic_boundaries', 'indexing',
                  'iteration', 'container_calls', 'operators', 'mutations', 'compound_assignments',
                  'results', 'take', 'value_probes']
        run_tasks([Task(name, lambda name=name: runner.run(name, ['php', APP / 'tests' / (name + '.php')]))
                   for name in suites] + [Task('family-preparation', lambda: runner.run(
                       'family-preparation', ['php', APP / 'tests/dynamic_families.php', programs]))], jobs=12)
        compiler = CppFixtureCompiler(runner, APP.parents[1], output,
                                      pch=not args.no_pch, return_type_errors=True, runtime=True)
        fixtures = json.loads((programs / 'executions.json').read_text())

        def execute(item):
            source = Path(item['path'])
            executable = source.with_suffix('.program')
            compiler.compile(source.stem + '-compile', source, executable)
            runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])

        run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item))
                   for item in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(
        passed=True, cpp_pch=not args.no_pch, php_suites=len(suites) + 1, generated_programs=len(fixtures),
        native_compiler_rebuild=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
