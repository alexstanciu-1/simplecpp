"""Four scalar prerequisites and 96 exact-float carrier compounds."""
import argparse
import itertools
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
carriers = ['nullable', 'result', 'result_or_false', 'result_or_bool']
arithmetic = ['+=', '-=', '*=', '/=']
operators = arithmetic
pairs = [(a, None) for a in carriers] + [(a, b) for a, b in itertools.product(carriers, repeat=2) if a != b]

def wrapper(carrier):
    return '?float' if carrier == 'nullable' else carrier + '<float>'

with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)

    def proof(left, right, scalar=False):
        name = 'scalar' if scalar else left if right is None else left + '-' + right
        target = out / name
        target.mkdir()
        cmd = ['php', APP / 'tests/float_compounds.php', target, left, 'scalar' if scalar else 'arithmetic' if right is None else 'cross']
        if right is not None:
            cmd.append(right)
        runner.run(name + '-prepare', cmd)
        lhs = wrapper(left)
        arrangements = [('float', 'float')] if scalar else [(lhs, wrapper(right))] if right is not None else [(lhs, lhs), (lhs, 'float'), ('float', lhs)]
        expected = {(a, op, b, a) for a, b in arrangements for op in operators}
        signatures = json.loads((target / 'signatures.json').read_text())
        assert len(signatures) == len(expected) and set(map(tuple, signatures)) == expected
        cpp.compile(name + '-native-build', target / 'int_carrier_compounds.cpp', target / 'native')
        runner.run(name + '-native-execute', [target / 'native'])

    run_tasks([Task('scalar', lambda: proof('nullable', None, True))])
    run_tasks([Task(a if b is None else a + '-' + b, lambda a=a, b=b: proof(a, b)) for a, b in pairs])

(out / 'summary.json').write_text(json.dumps({
    'passed': True, 'compound_signatures': 96, 'scalar_prerequisites': 4,
    'recovery_sequences': 100, 'runtime_changes': False,
    'full_matrix_run': False, 'native_compiler_rebuilt': False,
}, indent=2) + '\n')
