#!/usr/bin/env python3
"""Focused record semantics and generated-program execution; no native compiler rebuild."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
parser.add_argument('--suite', choices=['records', 'property_defaults', 'data_records', 'constructors', 'methods', 'static_members', 'method_parameters', 'method_returns', 'qualified_static_calls', 'qualified_static_members', 'destructors', 'nullable_handles', 'nullable_records', 'collection_probes', 'property_mutations', 'inheritance', 'abstract_dispatch', 'interfaces', 'shared_upcasts', 'interface_handles'], default='records')
parser.add_argument('--no-pch', action='store_true', help='Verify emitted header dependencies without a PCH')
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
programs = output / 'programs'
programs.mkdir()
with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
    runner.run(args.suite, ['php', APP / 'tests' / (args.suite + '.php'), programs])
    fixtures = json.loads((programs / 'executions.json').read_text())
    compiler = CppFixtureCompiler(runner, APP.parents[1], output,
                                  pch=not args.no_pch, return_type_errors=True, runtime=True)
    def execute(item):
        source = Path(item['path'])
        executable = source.with_suffix('.program')
        compiler.compile(source.stem + '-compile', source, executable)
        runner.run(source.stem + '-execute', [executable], expected=item['exit_code'])
    run_tasks([Task(Path(item['path']).stem, lambda item=item: execute(item))
               for item in fixtures], jobs=12)
(output / 'summary.json').write_text(json.dumps(dict(
    passed=True, generated_programs=len(fixtures), cpp_pch=not args.no_pch,
    native_compiler_rebuilt=False), indent=2) + '\n')
