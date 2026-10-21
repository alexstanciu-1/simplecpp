"""Probe the expressiveness of task error-handler metadata before extending it."""
import argparse
import copy
import json
import shutil
import sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1]
ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--results',type=Path,required=True)
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=out/'metadata';shutil.copytree(ROOT/'runtime/metadata/modules',metadata)
base=next(f for f in json.loads((metadata/'tasks.json').read_text())['functions'] if f['id']=='tasks.run.vector')
module={'version':1,'module':'task_error_probe','dependencies':['tasks'],'functions':[],'cpp':[]}
for omission in [False,True]:
 f=copy.deepcopy(base);f['id']='probe.error.'+('void' if omission else 'replacement');f['name']='probe_error_batch'
 for param in f['parameters'][:6]:param.pop('default',None)
 f['parameters'][5]={'name':'error_handler','type':{'callable':{'parameters':[{'name':'item','type':{'parameter':'T'},'passing':'value'},{'name':'error','type':'task_error','passing':'value'}],'returns':'void' if omission else {'parameter':'R'}}},'passing':'value'}
 module['functions'].append(f);module['cpp'].append({'id':f['id'],'symbol':'scpp::tasks::run','header':'modules/tasks/tasks.hpp','native_payloads':True})
(metadata/'task_error_probe.json').write_text(json.dumps(module,indent=2)+'\n')
cases={
 'value-omission':('$v vector<int> = [1];probe_error_batch($v,1,fn(int $n):int=>$n,null,null,function(int $n,task_error $e):void{});',0),
 'void-handler':('$v vector<int> = [1];probe_error_batch($v,1,function(int $n):void{},null,null,function(int $n,task_error $e):void{});',255),
}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def probe(name,source,expected):
  directory=out/name;(directory/'source').mkdir(parents=True);(directory/'source/main.phs').write_text(source)
  runner.run(name,['php',APP/'tests/async_tasks_publish.php',directory,metadata,'task_error_probe','concrete'],expected=expected)
  if expected:
   diagnostic=(out/'logs'/(name+'.stderr')).read_text()
   assert 'ambiguous matching contracts' in diagnostic,diagnostic
 run_tasks([Task(n,lambda n=n,s=s,e=e:probe(n,s,e)) for n,(s,e) in cases.items()])
(out/'observations.json').write_text(json.dumps({'value_worker_void_handler':'accepted','void_worker_void_handler':'ambiguous matching contracts','conformance':False},indent=2)+'\n')
