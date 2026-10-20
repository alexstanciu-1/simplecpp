"""V-L06 float identity runtime gate and source snapshot/recovery proof."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results',required=True,type=Path)
parser.add_argument('--runtime-only',action='store_true')
args=parser.parse_args();out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def runtime():
  source=out/'runtime.cpp';source.write_text('#include "'+str(APP/'tests/cross_identity_support.hpp')+'"\nint main(){std::cout<<"runtime_pairs="<<identity_probe::runtime_pair<4,4,scpp::float_t,double>()<<"\\n";}\n')
  cpp.compile('runtime-build',source,out/'runtime');runner.run('runtime-execute',[out/'runtime'])
 run_tasks([Task('runtime',runtime)])
 if not args.runtime_only:
  def source():
   target=out/'source';target.mkdir()
   runner.run('source-prepare',['php',APP/'tests/cross_identity.php',target,'plain','plain','float','float'])
   assert json.loads((target/'signatures.json').read_text())==[['float','===','float','bool'],['float','!==','float','bool']]
   cpp.compile('source-build',target/'cross_identity.cpp',target/'native');runner.run('source-execute',[target/'native'])
  run_tasks([Task('source',source)])
(out/'summary.json').write_text(json.dumps({'passed':True,'runtime_pairs':256,'source_forms':0 if args.runtime_only else 2,'runtime_changes':False,'full_matrix_run':False},indent=2)+'\n')
