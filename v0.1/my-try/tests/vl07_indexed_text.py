"""Observe existing indexed text-compound protocol; no new indexed implementation."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True);args=parser.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
records={}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof(i,t):
  target=out/str(i);target.mkdir();phase='prepare'
  try:
   runner.run(str(i)+'-prepare',['php',APP/'tests/vl07_indexed_text.php',target,t,str(i)])
   phase='build';cpp.compile(str(i)+'-build',target/'indexed.cpp',target/'native')
   phase='execute';runner.run(str(i)+'-execute',[target/'native'])
   records[t]={'supported':True}
  except RuntimeError as e:records[t]={'supported':False,'phase':phase,'error':str(e)}
 run_tasks([Task(t,lambda i=i,t=t:proof(i,t)) for i,t in enumerate(['?string','result<string>','result_or_false<string>','result_or_bool<string>'])])
(out/'summary.json').write_text(json.dumps({'audit_completed':True,'full_matrix_run':False,'rows':records},indent=2)+'\n')
