#!/usr/bin/env python3
"""Authored namespace bodies, retained publication and generated C++ proof."""
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
    suites = ['namespaces', 'ast', 'incremental_preparation', 'lifecycle_handoff', 'token_cleanup']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'namespaces':
                command.append(programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False)
        def execute(source):
            executable = output / source.stem
            compiler.compile_artifacts(source.stem + '-compile', source.read_bytes(), executable)
            runner.run(source.stem + '-execute', [executable], expected=7)
        fixtures = sorted(programs.glob('*.json'))
        tasks = [Task(source.stem, lambda source=source: execute(source)) for source in fixtures]
        for name, directory in [('tokenize', '02_tokenize'), ('parse', '03_parse')]:
            tasks.append(Task(name + '-conversion', lambda name=name, directory=directory:
                runner.run(name + '-conversion', ['php', APP.parents[1] / 'tools/php_portability/convert.php',
                           APP / directory, output / ('converted-' + name)],
                           cli_reason='Portable namespace parser conversion')))
        run_tasks(tasks, jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, php_suites=len(suites),
        generated_programs=len(fixtures), qualified_reference_syntax=False,
        native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
