"""Prove PCH preparation, uncached isolation and fail-closed compilation setup."""
import json
from pathlib import Path
import sys
import tempfile
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools'))
from cpp_fixture import CppFixtureCompiler
from test_runner import CommandRunner


class CppFixtureTests(unittest.TestCase):
    def test_cached_and_uncached_execution_and_header_visibility(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            include = root / 'runtime/include'
            for name in ('scpp/int_t.hpp', 'scpp/nullable.hpp', 'scpp/cast.hpp',
                         'lang/php/operators/conditional/condition_truthiness.hpp',
                         'scpp/generated/operators.hpp'):
                header = include / name
                header.parent.mkdir(parents=True, exist_ok=True)
                header.write_text('#pragma once\n')
            (include / 'scpp/int_t.hpp').write_text('#pragma once\nconstexpr int fixture_value = 7;\n')
            source = root / 'main.cpp'
            source.write_text('#include "scpp/int_t.hpp"\nint main() { return fixture_value; }\n')
            with CommandRunner(root / 'logs', root, php_executor='cli') as runner:
                for enabled in (True, False):
                    output = root / str(enabled)
                    output.mkdir()
                    compiler = CppFixtureCompiler(runner, root, output, pch=enabled,
                                                  return_type_errors=True)
                    binary = output / 'program'
                    compiler.compile(str(enabled) + '-compile', source, binary)
                    runner.run(str(enabled) + '-execute', [binary], expected=7)
                    self.assertEqual((output / 'cpp-pch/runtime.pch').exists(), enabled)
                    if not enabled:
                        source.write_text('int main() { return fixture_value; }\n')
                        with self.assertRaises(RuntimeError):
                            compiler.compile('missing-include', source, binary)
            records = json.loads((root / 'logs/commands.json').read_text())
            self.assertEqual(sum(r['name'] == 'cpp-pch-build' for r in records), 1)
            for record in records:
                if record['name'].endswith('compile') or record['name'] == 'cpp-pch-build':
                    self.assertIn('-Werror=return-type', record['command'])

    def test_pch_failure_stops_setup(self):
        class BrokenRunner:
            def run(self, name, command):
                raise RuntimeError('PCH build failed')
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            with self.assertRaisesRegex(RuntimeError, 'PCH build failed'):
                CppFixtureCompiler(BrokenRunner(), root, root)

    def test_runtime_is_built_once_and_pch_flags_match(self):
        class RecordingRunner:
            def __init__(self):
                self.commands = []

            def run(self, name, command, **options):
                self.commands.append((name, command))

        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            runner = RecordingRunner()
            compiler = CppFixtureCompiler(runner, root, root, runtime=True)
            compiler.compile('first', root / 'first.cpp', root / 'first')
            compiler.compile('second', root / 'second.cpp', root / 'second')
            self.assertEqual([name for name, _ in runner.commands],
                             ['cpp-runtime-build', 'cpp-pch-build', 'first', 'second'])
            for _, command in runner.commands:
                self.assertIn('-pthread', command)
                self.assertIn('-DSCPP_LANGUAGE_TARGET_PHP=1', command)
            for _, command in runner.commands[2:]:
                self.assertIn(root / 'fixture-runtime.o', command)

    def test_runtime_failure_stops_setup(self):
        class BrokenRunner:
            def run(self, name, command, **options):
                raise RuntimeError('Runtime build failed')

        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            with self.assertRaisesRegex(RuntimeError, 'Runtime build failed'):
                CppFixtureCompiler(BrokenRunner(), root, root, runtime=True)


if __name__ == '__main__':
    unittest.main()
