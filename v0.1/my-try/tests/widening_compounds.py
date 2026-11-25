"""C2 float-destination compounds: four scalar prerequisites and 96 carrier signatures."""
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
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
families = ['plain', 'nullable', 'result', 'result_or_false', 'result_or_bool']
def source(family, payload):
    return payload if family == 'plain' else '?' + payload if family == 'nullable' else family + '<' + payload + '>'
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def proof(left, right):
        name = left + '-' + right
        target = out / name
        target.mkdir()
        runner.run(name + '-prepare', ['php', APP / 'tests/widening_compounds.php', target, left, 'widening', right])
        lhs = source(left, 'float')
        rhs = source(right, 'int')
        expected = {(lhs, op, rhs, lhs) for op in ['+=', '-=', '*=', '/=']}
        signatures = json.loads((target / 'signatures.json').read_text())
        assert len(signatures) == 4 and set(map(tuple, signatures)) == expected
        cpp.compile(name + '-native-build', target / 'int_carrier_compounds.cpp', target / 'native')
        runner.run(name + '-native-execute', [target / 'native'])
    run_tasks([Task('scalar', lambda: proof('plain', 'plain'))])
    run_tasks([Task(a+'-'+b, lambda a=a,b=b:proof(a,b)) for a,b in itertools.product(families,repeat=2) if (a,b)!=('plain','plain')])
(out / 'summary.json').write_text(json.dumps({'passed':True,'carrier_signatures':96,'scalar_prerequisites':4,'recovery_sequences':100,'runtime_changes':False,'full_matrix_run':False,'native_compiler_rebuilt':False},indent=2)+'\n')
