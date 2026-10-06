#!/usr/bin/env python3
"""Source constant preparation, retained recovery and generated-program execution."""
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
    output = parser.parse_args().results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    (output / 's2s').mkdir()
    suites = ['source_constants', 'imports', 'namespaces', 'observed_lookup',
              'qualified_lookup', 'incremental_preparation', 'lifecycle_handoff', 'token_cleanup', 'ast', 's2s']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'source_constants':
                command.append(programs)
            elif name == 's2s':
                command.append(output / 's2s')
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False)
        def execute(source):
            executable = output / source.stem
            compiler.compile_artifacts(source.stem + '-compile', source.read_bytes(), executable)
            runner.run(source.stem + '-execute', [executable], expected=7)
        fixtures = sorted(programs.glob('*.json'))
        run_tasks([Task(source.stem, lambda source=source: execute(source)) for source in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, php_suites=len(suites),
        generated_programs=len(fixtures), native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
