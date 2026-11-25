"""Focused regression export and native execution, without an operator matrix run."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False);programs=out/'programs';programs.mkdir()
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 suites=['mixed','dynamic_boundaries','results','nullable','operators','mutations','compound_assignments','preparation_recovery']
 run_tasks([Task(n,lambda n=n:runner.run(n,['php',APP/'tests'/(n+'.php')])) for n in suites]+[Task('export',lambda:runner.run('export',['php',APP/'tests/boxed_selection_regressions.php',programs]))])
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 rows=json.loads((programs/'executions.json').read_text())
 def execute(row):
  src=Path(row['path']);exe=out/src.stem;cpp.compile(src.stem+'-build',src,exe);runner.run(src.stem+'-execute',[exe],expected=row['exit_code'])
 run_tasks([Task(Path(r['path']).stem,lambda r=r:execute(r)) for r in rows])
(out/'summary.json').write_text(json.dumps({'passed':True,'php_suites':len(suites)+1,'native_programs':len(rows),'full_matrix_run':False},indent=2)+'\n')
