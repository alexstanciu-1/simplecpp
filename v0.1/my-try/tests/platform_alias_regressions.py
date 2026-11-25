"""Focused shared-call, type lookup and provider regressions for platform aliases."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 names=['callables','observed_lookup','preparation_recovery','member_owners','methods','static_members','qualified_static_members']
 run_tasks([Task(n,lambda n=n:runner.run(n,['php',APP/'tests'/(n+'.php')])) for n in names])
 # Each provider suite uses the same shared runner and verifies generated native programs.
 for n in ['runtime_environment','runtime_filesystem']:
  runner.run(n,['python3',APP/'tests'/(n+'.py'),'--results',out/n],timeout=300)
(out/'summary.json').write_text(json.dumps({'passed':True,'preparation_suites':names,'native_provider_suites':['runtime_environment','runtime_filesystem'],'full_matrix_run':False},indent=2)+'\n')
