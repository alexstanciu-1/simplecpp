"""Scalar admission decisions, deferred boundaries, and generated C++ behavior."""
import argparse
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--php', default='php')
parser.add_argument('--jobs', default=32, type=int)
parser.add_argument('--ieee-zero', action='store_true', help='Probe the proposed IEEE zero-division contract; current runtime rejects zero')
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=args.jobs) as runner:
    def check(name):
        command = [args.php, APP / 'tests' / (name + '.php')]
        if name in ('scalar_admission', 'eager_binary', 'integer_widths'):
            command.append(out)
        if name == 'scalar_admission' and args.ieee_zero:
            command.append('--ieee-zero')
        runner.run(name, command)
    run_tasks([Task(n, lambda n=n: check(n)) for n in
               ['scalar_admission', 'eager_binary', 'conversions', 'integer_widths', 'power', 'mutations', 'compound_assignments', 'logical', 'mixed', 'control_flow']], jobs=args.jobs)
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def execute(source):
        exe = source.with_suffix('')
        cpp.compile(source.stem + '-build', source, exe)
        result = runner.run(source.stem + '-execute', [exe])
        if source.stem == 'families' and result != b'12':
            raise AssertionError('Ordered concatenation changed')
    run_tasks([Task(p.stem, lambda p=p: execute(p)) for p in out.glob('*.cpp')], jobs=args.jobs)
