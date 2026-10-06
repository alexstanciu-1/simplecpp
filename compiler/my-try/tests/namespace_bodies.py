#!/usr/bin/env python3
"""Source-body inventory, lifecycle and generated entry proof; syntax has its own suite."""
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
    suites = ['namespace_bodies', 'ast', 'structure_access', 'preparation_work',
              'lifecycle_handoff', 'parse_collection', 'token_cleanup', 'incremental_cpp']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'namespace_bodies':
                command.append(output)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False)
        def execute(name, expected):
            compiler.compile_artifacts(name + '-compile', (output / (name + '.json')).read_bytes(), output / name)
            runner.run(name + '-execute', [output / name], expected=expected)
        tasks = [Task(name, lambda name=name, expected=expected: execute(name, expected))
                 for name, expected in [('entry', 7), ('empty', 0)]]
        for name, directory in [('parse', '03_parse'), ('prepare', '04_analyze/prepare')]:
            tasks.append(Task(name + '-conversion', lambda name=name, directory=directory:
                runner.run(name + '-conversion', ['php', APP.parents[1] / 'tools/php_portability/convert.php',
                           APP / directory, output / ('converted-' + name)],
                           cli_reason='Portable namespace-body ownership conversion')))
        run_tasks(tasks, jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, php_suites=len(suites),
        generated_programs=2, source_namespace_syntax=False, native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
