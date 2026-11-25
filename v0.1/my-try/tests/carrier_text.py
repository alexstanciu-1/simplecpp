"""Bounded V-L11/V-D14 text-domain proofs, excluding the full operator matrix."""
import argparse
import itertools
import json
from pathlib import Path
import sys
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', required=True, type=Path)
parser.add_argument('--smoke', action='store_true')
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
families = ['nullable', 'result', 'result_or_false', 'result_or_bool', 'plain']
types = [(f,p) for f in families for p in ['int','float','bool','string']]
types += [('plain',p) for p in ['int8','int16','int32','int64','uint8','uint16','uint32']]
rows = [('concat',a,b,p,q) for (a,p),(b,q) in itertools.product(types,repeat=2)]
rows += [('append',a,b,'string',q) for a in families for b,q in types]
if args.smoke:
    rows = [('concat','nullable','result','float','bool'),('append','result_or_bool','nullable','string','float'),('concat','plain','plain','int8','uint32'),('concat','result_or_bool','plain','int','string')]
def source_type(f,p):
    return p if f=='plain' else '?'+p if f=='nullable' else f+'<'+p+'>'
results = {}
with CommandRunner(out/'logs', ROOT, jobs=12) as runner:
    cpp = CppFixtureCompiler(runner, ROOT, out, pch=False, runtime=True)
    def proof(row):
        m,a,b,p,q = row
        name = '-'.join(row)
        target = out/name
        target.mkdir()
        phase='prepare'
        try:
            runner.run(name+'-prepare',['php', APP/'tests/vl07_completion.php',target,m,a,b,p,q])
            signatures=json.loads((target/'signatures.json').read_text())
            op={'ordering':'<=>','power':'**=','concat':'.','append':'.='}[m]
            ret=source_type(a,p) if m in ('power','append') else 'int' if m=='ordering' else 'string'
            assert signatures==[[source_type(a,p),op,source_type(b,q),ret]]
            phase='build'
            cpp.compile(name+'-build',target/'completion.cpp',target/'native')
            phase='execute'
            runner.run(name+'-execute',[target/'native'])
            results[name]={'passed':True,'signature':signatures[0]}
        except (RuntimeError,AssertionError) as error:
            results[name]={'passed':False,'phase':phase,'error':str(error)}
    run_tasks([Task('-'.join(row),lambda row=row:proof(row)) for row in rows])
(out/'summary.json').write_text(json.dumps({'passed':all(r['passed'] for r in results.values()),'signatures':len(rows),'carrier_signatures':sum(a!='plain' or b!='plain' for _,a,b,_,_ in rows),'full_matrix_run':False,'results':results},indent=2)+'\n')
if not all(r['passed'] for r in results.values()):
    raise SystemExit(1)
