"""Replay V-L06's original homogeneous carrier equality/identity cells."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',required=True,type=Path);args=parser.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
records=[]
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof(family,ordinary):
  name=family+('-ordinary' if ordinary else '-identity');target=out/name;target.mkdir()
  command=['php',APP/'tests/cross_identity.php',target,family,family,'int','int']
  if ordinary:command.append('--ordinary-cross')
  runner.run(name+'-prepare',command)
  t='?int' if family=='nullable' else family+'<int>'
  signatures=json.loads((target/'signatures.json').read_text())
  assert signatures==[[t,op,t,'bool'] for op in (['==','!='] if ordinary else ['===','!=='])]
  cpp.compile(name+'-build',target/'cross_identity.cpp',target/'native');runner.run(name+'-execute',[target/'native']);records.extend(signatures)
 run_tasks([Task(f+str(o),lambda f=f,o=o:proof(f,o)) for f in ['nullable','result','result_or_false'] for o in [False,True]])
(out/'summary.json').write_text(json.dumps({'passed':True,'original_carrier_cells':12,'signatures':records,'new_carrier_admission':False,'full_matrix_run':False},indent=2)+'\n')
