#!/usr/bin/env python3
"""Focused PHP enum preparation and separate generated-C++ artifact execution."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler


def native_checks(name, bundle):
    """Independent native assertions prove emitted width/value, without source enum operators."""
    checks = {
        'unit': 'static_assert(sizeof(enum_Suit) == 1); static_assert(static_cast<unsigned>(enum_Suit::case_Spades) == 1);',
        'compact': 'static_assert(sizeof(enum_Status) == 2); static_assert(static_cast<unsigned>(enum_Status::case_Missing) == 404); static_assert(static_cast<int>(enum_Signed::case_Low) == -129);',
        'fixed': 'static_assert(sizeof(enum_UU16) == 2); static_assert(sizeof(enum_I64) == 8); static_assert(static_cast<std::int64_t>(enum_I64::case_Min) == (-9223372036854775807LL - 1)); static_assert(static_cast<std::uint64_t>(enum_UU64::case_Max) == 18446744073709551615ULL); static_assert(sizeof(enum_Byte) == 1);',
        'wide_unit': 'static_assert(sizeof(enum_Many) == 2); static_assert(static_cast<unsigned>(enum_Many::case_C256) == 256);',
        'enum_names': 'const int check_names = [] { scpp::int_t<> calls{0LL}; if (function_first().native_value() != "First" || function_last().native_value() != "High" || function_once(calls).native_value() != "second_name" || calls.native_value() != 1) std::abort(); return 0; }();',
        'enum_names_cross_file': 'const int check_names = [] { if (function_first().native_value() != "B" || function_last().native_value() != "A") std::abort(); return 0; }();',
    }
    if name not in checks:
        return bundle
    includes = '#include <type_traits>\n#include <cstdlib>\n' + ''.join('#include "' + a['path'] + '"\n' for a in bundle['artifacts'] if a['id'].startswith(('type:', 'callable:')))
    if name == 'fixed':
        checks[name] += 'static_assert(std::is_same_v<std::underlying_type_t<enum_UU16>, std::uint16_t>); static_assert(std::is_same_v<std::underlying_type_t<enum_I64>, std::int64_t>);'
    bundle['artifacts'].append(dict(id='test-checks', kind='translation_unit', path='test-checks.cpp', text=includes + checks[name] + '\n'))
    return bundle


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    output = parser.parse_args().results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    suites = ['enums', 'enum_model', 'type_catalog', 'parse_collection', 'incremental_preparation',
              'incremental_cpp', 'capabilities', 'ast', 'token_cleanup', 'namespaces', 'imports', 'source_constants',
              'control_flow', 'operators', 'nullable', 'results', 'mixed', 'callables', 'json', 'initialization']
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12, journals=[output / 'commands.json']) as runner:
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'enums':
                command.append(programs)
            if name == 'json':
                json_programs = output / 'json-programs'
                json_programs.mkdir()
                command.append(json_programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        compiler = CppFixtureCompiler(runner, APP.parents[1], output, pch=False)
        def execute(source):
            executable = output / source.stem
            bundle = native_checks(source.stem, json.loads(source.read_bytes()))
            compiler.compile_artifacts(source.stem + '-compile', json.dumps(bundle).encode(), executable)
            runner.run(source.stem + '-execute', [executable], expected=-6 if source.stem == 'enum_unhandled' else 7)
            if source.stem == 'enum_unhandled':
                assert b'Unhandled match expression' in (output / 'logs' / 'enum_unhandled-execute.stderr').read_bytes()
        fixtures = sorted(programs.glob('*.json'))
        run_tasks([Task(p.stem, lambda p=p: execute(p)) for p in fixtures], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, php_suites=len(suites),
        generated_programs=len(fixtures), native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
