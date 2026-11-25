"""V-H10 negative boundary and typed callable-container positive control."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true')
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/closure_container_repair.php',out]+(['--baseline'] if a.baseline else [])))])
 if not a.baseline:
  cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
  def execute():
   cpp.compile('positive-build',out/'positive.cpp',out/'positive');actual=runner.run('positive-execute',[out/'positive']);assert actual==b'7:9\n',actual
  run_tasks([Task('positive',execute)])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'checks':json.loads((out/('baseline.json' if a.baseline else 'checks.json')).read_text())},indent=2)+'\n')
