"""Strict task submission integration, production default prelowering on dev002."""
import argparse
import json
import shutil
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
metadata = out / 'metadata'
shutil.copytree(ROOT / 'runtime/metadata/modules', metadata)
module = {'version':1, 'module':'task_submission_probe','dependencies':['tasks'],
 'functions':[{'id':'probe.work','name':'probe_work','parameters':[{'name':'callback','type':{'callable':{'parameters':[],'returns':'void'}},'passing':'value'}],'returns':'void'}],
 'cpp':[{'id':'probe.work','symbol':'task_submission_probe::work_context','header':str(APP/'tests/fixtures/task_submission/provider.hpp')}]}
(metadata/'task_submission_probe.json').write_text(json.dumps(module,indent=2)+'\n')
cases = {
 'synchronize': ('probe_work(function():void{task_synchronize(function():void{echo 42;});task_synchronize(function():void{echo 7;});});', '427', False),
 'synchronize-outside': ('task_synchronize(function():void{echo "unexpected";});', '', True),
 'synchronize-recursive': ('probe_work(function():void{task_synchronize(function():void{task_synchronize(function():void{echo "unexpected";});});});', '', True),
}
with CommandRunner(out/'logs', ROOT, jobs=12) as runner:
 def check(name, source, expected, fails):
  folder=out/name
  (folder/'source').mkdir(parents=True)
  (folder/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',folder,metadata,'task_submission_probe','concrete'])
  assert json.loads((folder/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for temperature in ['cold','warm']:
   build=json.loads(runner.run(name+'-'+temperature,['php',APP/'tools/build_cpp.php',folder/'published',folder/'cache'],timeout=300))
   run_name=name+'-'+temperature+'-run'
   assert runner.run(run_name,[build['executable']],expected=1 if fails else 0)==expected.encode()
   if fails: assert 'task_synchronize requires an unordered work callback' in (out/'logs'/(run_name+'.stderr')).read_text()
   if temperature=='warm': assert build['compiled']==build['linked']==0
 run_tasks([Task(name,lambda n=name,s=s,e=e,f=f:check(n,s,e,f)) for name,(s,e,f) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases)},indent=2)+'\n')
