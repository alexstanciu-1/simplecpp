#!/usr/bin/env python3
"""Canonical namespace model and C++ collision proof; not source namespace parsing."""
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
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def proof():
            runner.run('identity', ['php', APP / 'tests/namespace_identity.php', output / 'program.json'])
            compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False)
            compiler.compile_artifacts('identity-build', (output / 'program.json').read_bytes(), output / 'program')
            runner.run('identity-execute', [output / 'program'], expected=7)
            runner.run('scope-conversion', ['php', APP.parents[1] / 'tools/php_portability/convert.php',
                       APP / '03_parse/scopes', output / 'converted-scopes'], cli_reason='Portable scope model conversion')
        run_tasks([Task('namespace-identity', proof)], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, source_namespace_syntax=False,
        generated_programs=1, native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
