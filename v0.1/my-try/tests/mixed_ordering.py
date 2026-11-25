"""O1: eight scalar prerequisites and 192 mixed int/float carrier orderings."""
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
formats = {'': '%s', 'n': '?%s', 'r': 'result<%s>', 'f': 'result_or_false<%s>', 'b': 'result_or_bool<%s>'}
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def proof(left, right):
        name = (left or 'plain') + '-' + (right or 'plain')
        target = out / name
        target.mkdir()
        runner.run(name + '-prepare', ['php', APP / 'tests/mixed_ordering.php', target, left, right])
        data = json.loads((target / 'cross-signatures.json').read_text())
        expected = {(formats[left] % a, op, formats[right] % b, 'bool') for a,b in [('int','float'),('float','int')] for op in ['<','<=','>','>=']}
        assert len(data['signatures']) == 8 and set(map(tuple,data['signatures'])) == expected
        assert data['recovery_sequences'] == 2
        cpp.compile(name + '-native-build', target / 'mixed_ordering.cpp', target / 'native')
        runner.run(name + '-native-execute', [target / 'native'])
    run_tasks([Task('scalar', lambda: proof('', ''))])
    run_tasks([Task((a or 'plain')+'-'+(b or 'plain'), lambda a=a,b=b:proof(a,b)) for a,b in itertools.product(formats,repeat=2) if a or b])
(out / 'summary.json').write_text(json.dumps({'passed':True,'carrier_signatures':192,'scalar_prerequisites':8,'recovery_sequences':50,'runtime_changes':False,'full_matrix_run':False,'native_compiler_rebuilt':False},indent=2)+'\n')
