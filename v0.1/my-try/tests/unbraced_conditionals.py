"""Bounded S06 grammar, branch-scope, native and incremental recovery proof."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',required=True,type=Path);p.add_argument('--baseline',action='store_true');a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def prepare():runner.run('prepare',['php',APP/'tests/unbraced_conditionals.php',out]+(['--baseline'] if a.baseline else []))
 run_tasks([Task('prepare',prepare)])
 if not a.baseline:
  cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
  rows=json.loads((out/'executions.json').read_text())
  def execute(row):
   source=Path(row['path']);exe=source.with_suffix('.native');cpp.compile(row['name']+'-build',source,exe);runner.run(row['name']+'-execute',[exe],expected=row['exit_code'])
  run_tasks([Task(row['name'],lambda row=row:execute(row)) for row in rows])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'checks':json.loads((out/('baseline.json' if a.baseline else 'checks.json')).read_text()),'full_matrix_run':False,'runtime_changes':False},indent=2)+'\n')
