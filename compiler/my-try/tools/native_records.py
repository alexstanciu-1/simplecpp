#!/usr/bin/env python3
"""CH10 source parity through an already-built native compiler, then fixture execution."""
import argparse
import hashlib
import json
from pathlib import Path
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
SUITES = ['records', 'property_defaults', 'data_records', 'constructors', 'methods',
          'static_members', 'method_parameters', 'method_returns', 'qualified_static_calls',
          'qualified_static_members', 'destructors', 'nullable_handles', 'nullable_records',
          'collection_probes', 'property_mutations']
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--executable', required=True, type=Path)
parser.add_argument('--results', required=True, type=Path)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
executable = args.executable.resolve()
with CommandRunner(output / 'logs', ROOT, jobs=12) as runner:
    def export(suite):
        folder = output / suite
        folder.mkdir()
        manifest = folder / 'sources.jsonl'
        # The environment applies only to this host process, never the native driver.
        runner.run(suite + '-export', ['env', 'SCPP_RECORD_EXPORT=' + str(manifest),
                   'php', APP / 'tests' / (suite + '.php'), folder])
    run_tasks([Task(s, lambda s=s: export(s)) for s in SUITES], jobs=12)
    compiler = CppFixtureCompiler(runner, ROOT, output, pch=True,
                                 return_type_errors=True, runtime=True, cxx='clang++-18')
    parity = []
    executions = []
    for suite in SUITES:
        folder = output / suite
        for index, line in enumerate((folder / 'sources.jsonl').read_text().splitlines()):
            parity.append((suite + '-' + str(index), json.loads(line)))
        executions.extend((suite, row) for row in json.loads((folder / 'executions.json').read_text()))
    def compare(name, item):
        folder = output / 'parity' / name
        folder.mkdir(parents=True)
        for relative, source in item['sources'].items():
            path = folder / relative
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(source)
        (folder / 'request.txt').write_text('s2s:' + str(folder))
        actual = runner.run(name + '-native', [executable], cwd=folder)
        if json.loads(actual) != json.loads(item['expected']):
            raise RuntimeError(name + ': native artifacts differ from host')
    run_tasks([Task(n, lambda n=n, i=i: compare(n, i)) for n, i in parity], jobs=12)
    def execute(suite, item):
        source = Path(item['path'])
        name = suite + '-' + source.stem
        program = source.with_suffix('.program')
        compiler.compile(name + '-compile', source, program)
        runner.run(name + '-run', [program], expected=item['exit_code'])
    run_tasks([Task(s + '-' + Path(i['path']).stem, lambda s=s, i=i: execute(s, i))
               for s, i in executions], jobs=12)
(output / 'summary.json').write_text(json.dumps(dict(passed=True,
    native_executable=str(executable), native_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(),
    native_source_comparisons=len(parity), generated_executions=len(executions),
    host_suites=SUITES, native_rejection_and_incremental_tests=False), indent=2) + '\n')
