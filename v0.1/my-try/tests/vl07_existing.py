"""Reconcile all 129 prior separate-existing-path cells with actual admission."""
import argparse,itertools,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True);args=parser.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
records=[]
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof(p,f):
  name=p+'-'+f;target=out/name;target.mkdir()
  runner.run(name+'-prepare',['php',APP/'tests/vl07_existing.php',target,p,f])
  rows=json.loads((target/'signatures.json').read_text())
  if (target/'existing.cpp').exists():
   cpp.compile(name+'-build',target/'existing.cpp',target/'native');runner.run(name+'-execute',[target/'native'])
  records.extend(rows)
 run_tasks([Task(p+'-'+f,lambda p=p,f=f:proof(p,f)) for p,f in itertools.product(['int','float','bool','string'],['nullable','result','result_or_false','result_or_bool'])])
assert len(records)==129 and sum(r['admitted'] for r in records)==17
(out/'summary.json').write_text(json.dumps({'passed':True,'cells':129,'supported':17,'observed_rejections':112,'full_matrix_run':False,'rows':records},indent=2)+'\n')
