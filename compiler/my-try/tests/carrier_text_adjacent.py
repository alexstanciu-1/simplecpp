"""Verify existing interpolation, explicit casts and scalar text near the changed context."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False);programs=out/'programs';programs.mkdir()
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def prepare(name):
  command=['php',APP/'tests'/ (name+'.php')]
  if name=='s2s':command.append(programs)
  runner.run(name,command)
 run_tasks([Task(n,lambda n=n:prepare(n)) for n in ['s2s','conversions','interpolation']])
 fixtures=[r for r in json.loads((programs/'executions.json').read_text()) if Path(r['path']).stem.startswith(('interp_','cast_','concat_','compound_concat'))]
 assert fixtures
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def execute(row):
  source=Path(row['path']);exe=source.with_suffix('.native');cpp.compile(source.stem+'-build',source,exe);runner.run(source.stem+'-execute',[exe],expected=row['exit_code'])
 run_tasks([Task(Path(r['path']).stem,lambda r=r:execute(r)) for r in fixtures])
(out/'summary.json').write_text(json.dumps({'passed':True,'native_fixtures':[Path(r['path']).stem for r in fixtures],'full_matrix_run':False},indent=2)+'\n')
