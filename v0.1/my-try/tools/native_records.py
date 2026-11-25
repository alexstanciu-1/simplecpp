#!/usr/bin/env python3
"""Record chapter source parity through a native compiler, then fixture execution."""
import argparse
import hashlib
import json
from pathlib import Path
from test_runner import CommandRunner, Task, positive_jobs, run_tasks
from cpp_fixture import CppFixtureCompiler

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
CH10_SUITES = ['records', 'property_defaults', 'data_records', 'constructors', 'methods',
          'static_members', 'method_parameters', 'method_returns', 'qualified_static_calls',
          'qualified_static_members', 'destructors', 'nullable_handles', 'nullable_records',
          'collection_probes', 'property_mutations']
CH11_SUITES = ['inheritance', 'abstract_dispatch', 'interfaces', 'traits',
              'static_redeclarations', 'late_static', 'derived_data', 'constructors',
              'shared_upcasts', 'shared_joins', 'object_types', 'interface_handles',
              'destructors', 'callable_variance']
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--executable', required=True, type=Path)
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--chapter', choices=['10', '11'], default='10')
parser.add_argument('--repeat-parity', type=positive_jobs, default=1,
                    help='Repeat native comparisons with independent multi-file parse schedules')
args = parser.parse_args()
SUITES = CH11_SUITES if args.chapter == '11' else CH10_SUITES
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
    comparisons = [(n + '-r' + str(repeat), i)
                   for repeat in range(args.repeat_parity) for n, i in parity]
    run_tasks([Task(n, lambda n=n, i=i: compare(n, i)) for n, i in comparisons], jobs=12)
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
    source_snapshots=len(parity), parity_repetitions=args.repeat_parity,
    native_source_comparisons=len(comparisons), generated_executions=len(executions),
    chapter=args.chapter, host_suites=SUITES, native_rejection_and_incremental_tests=False), indent=2) + '\n')
