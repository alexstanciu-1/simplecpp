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
p.add_argument('--relation',action='store_true')
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=out/'metadata';shutil.copytree(ROOT/'runtime/metadata/modules',metadata)
base=next(f for f in json.loads((metadata/'tasks.json').read_text())['functions'] if f['id']=='tasks.run.vector')
module={'version':1,'module':'task_error_probe','dependencies':['tasks'],'functions':[],'cpp':[]}
for omission in [False,True]:
 f=copy.deepcopy(base);f['id']='probe.error.'+('void' if omission else 'replacement');f['name']='probe_error_batch'
 for param in f['parameters'][:6]:param.pop('default',None)
 f['parameters'][5]={'name':'error_handler','type':{'callable':{'parameters':[{'name':'item','type':{'parameter':'T'},'passing':'value'},{'name':'error','type':'task_error','passing':'value'}],'returns':'void' if omission else {'parameter':'R'}}},'passing':'value'}
 module['functions'].append(f);module['cpp'].append({'id':f['id'],'symbol':'scpp::tasks::run','header':'modules/tasks/tasks.hpp','native_payloads':True})
if a.relation:
 f=module['functions'][0]
 f['type_parameters'].append('E')
 f['parameters'][5]['type']['callable']['returns']={'parameter':'E'}
 f['replacement_results']=[{'result':'R','replacement':'E'}]
 module['functions']=[f];module['cpp']=module['cpp'][:1]
(metadata/'task_error_probe.json').write_text(json.dumps(module,indent=2)+'\n')
cases={
 'value-omission':('$v vector<int> = [1];probe_error_batch($v,1,fn(int $n):int=>$n,null,null,function(int $n,task_error $e):void{});',0),
 'void-handler':('$v vector<int> = [1];probe_error_batch($v,1,function(int $n):void{},null,null,function(int $n,task_error $e):void{});',255),
}
if a.relation:
 cases['void-handler']=(cases['void-handler'][0],0)
 cases['replacement']=('$v vector<int> = [1];probe_error_batch($v,1,fn(int $n):int=>$n,null,null,fn(int $n,task_error $e):int=>42);',0)
 cases['wrong-replacement']=('$v vector<int> = [1];probe_error_batch($v,1,fn(int $n):int=>$n,null,null,fn(int $n,task_error $e):string=>"bad");',255)
 cases['void-with-value']=('$v vector<int> = [1];probe_error_batch($v,1,function(int $n):void{},null,null,fn(int $n,task_error $e):int=>42);',255)
 cases['nullable-replacement']=('$v vector<int> = [1];probe_error_batch($v,1,function(int $n):?int{return $n;},null,null,function(int $n,task_error $e):?int{return null;});',0)
 cases['nullable-mismatch']=('$v vector<int> = [1];probe_error_batch($v,1,function(int $n):?int{return $n;},null,null,fn(int $n,task_error $e):int=>42);',255)
runtime_outputs={}
if a.relation:
 cases['runtime-replacement']=('$v vector<int> = [0];$r=probe_error_batch($v,1,fn(int $n):int=>1/$n,null,null,fn(int $n,task_error $e):int=>42);echo $r[0];',0)
 runtime_outputs['runtime-replacement']=b'42'
 cases['runtime-omission']=('$v vector<int> = [0,1];$r=probe_error_batch($v,1,fn(int $n):int=>1/$n,null,null,function(int $n,task_error $e):void{});echo count($r),$r[0];',0)
 runtime_outputs['runtime-omission']=b'11'
 cases['runtime-void']=('$v vector<int> = [0];probe_error_batch($v,1,function(int $n):void{1/$n;},null,null,function(int $n,task_error $e):void{echo "handled";});',0)
 runtime_outputs['runtime-void']=b'handled'
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def probe(name,source,expected):
  directory=out/name;(directory/'source').mkdir(parents=True);(directory/'source/main.phs').write_text(source)
  runner.run(name,['php',APP/'tests/async_tasks_publish.php',directory,metadata,'task_error_probe','concrete'],expected=expected)
  if expected:
   diagnostic=(out/'logs'/(name+'.stderr')).read_text()
   assert ('replacement result must be void or exactly match' if a.relation else 'ambiguous matching contracts') in diagnostic,diagnostic
  if name in runtime_outputs:
   for temperature in ['cold','warm']:
    build=json.loads(runner.run(name+'-'+temperature,['php',APP/'tools/build_cpp.php',directory/'published',directory/'cache'],timeout=300))
    assert runner.run(name+'-'+temperature+'-run',[build['executable']])==runtime_outputs[name]
    if temperature=='warm':assert build['compiled']==build['linked']==0
 tasks=[Task(n,lambda n=n,s=s,e=e:probe(n,s,e)) for n,(s,e) in cases.items()]
 if a.relation:tasks.append(Task('retained-contracts',lambda:runner.run('retained-contracts',['php',APP/'tests/task_error_relation_contracts.php',metadata,out/'retained-source'])))
 run_tasks(tasks)
(out/'observations.json').write_text(json.dumps({'value_worker_void_handler':'accepted','void_worker_void_handler':'accepted' if a.relation else 'ambiguous matching contracts','conformance':a.relation},indent=2)+'\n')
