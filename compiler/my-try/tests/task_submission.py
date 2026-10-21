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
p.add_argument('--case', action='append', default=[])
p.add_argument('--sanitize', action='store_true')
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
sanitizer = out/'sanitizer-clang'
if a.sanitize:
 sanitizer.write_text('#!/bin/sh\nexec /usr/bin/clang++ -fsanitize=address,undefined -fno-sanitize-recover=undefined -fno-omit-frame-pointer "$@"\n')
 sanitizer.chmod(0o755)
metadata = out / 'metadata'
shutil.copytree(ROOT / 'runtime/metadata/modules', metadata)
module = {'version':1, 'module':'task_submission_probe','dependencies':['tasks'],
 'functions':[{'id':'probe.work','name':'probe_work','parameters':[{'name':'callback','type':{'callable':{'parameters':[],'returns':'void'}},'passing':'value'}],'returns':'void'}],
 'cpp':[{'id':'probe.work','symbol':'task_submission_probe::work_context','header':str(APP/'tests/fixtures/task_submission/provider.hpp')}]}
(metadata/'task_submission_probe.json').write_text(json.dumps(module,indent=2)+'\n')
cases = {
 'target-vector': ('$v vector<int> = [40,41];$t vector<int> = [7];$r=task_run($v,2,fn(int $n):int=>$n+1,null,$t);echo count($r),$r[0],$r[1],$r[2],count($t),$t[0];', '37414217', False),
 'target-hash': ('$v hash<int,string> = {"a":41};$t hash<int,string> = {"a":7,"b":8};$r=task_run($v,2,fn(int $n):int=>$n+1,null,$t);echo $r["a"],$r["b"],$t["a"];', '4287', False),
 'target-start': ('$v vector<string> = ["a"];$t vector<string> = ["z"];$b=task_start($v,2,fn(string $n):string=>$n."!",null,$t);$r=task_join($b);echo $r[0],$r[1],$t[0];', 'za!z', False),
 'target-void': ('$v vector<int> = [1];$t=task_run($v,1,function(int $n):void{});$r=task_run($v,1,function(int $n):void{},null,$t);echo count($r),count($t);', '21', False),
 'run-explicit-null': ('$v vector<int> = [42];$r=task_run($v,2,fn(int $n):int=>$n,null,null,null,0);echo $r[0];', '42', False),
 'start-explicit-null': ('$v hash<int,string> = {"a":42};$b=task_start($v,2,fn(int $n):int=>$n,null);$r=task_join($b);echo $r["a"];', '42', False),
 'run-string': ('$v vector<string> = ["a","b"];$r=task_run($v,2,fn(string $s):string=>$s."!");echo $r[0],$r[1];', 'a!b!', False),
 'run-nested': ('$a vector<int> = [40,2];$v vector<vector<int>> = [$a];$r=task_run($v,2,fn(vector<int> $n):vector<int> =>$n);echo $r[0][0],$r[0][1];', '402', False),
 'run-nullable': ('$a ?int=42;$v vector<?int> = [$a,null];$r=task_run($v,2,fn(?int $n):?int=>$n);echo $r[0]??0,$r[1]??7;', '427', False),
 'start-string': ('$v vector<string> = ["a","b"];$b=task_start($v,2,fn(string $s):string=>$s."!");$r=task_join($b);echo $r[0],$r[1];', 'a!b!', False),
 'start-hash-context': ('$v hash<int,string> = {"a":42};$b=task_start($v,2,function(int $n,task_context $c):int{return $n;});$r=task_join($b);echo $r["a"];', '42', False),
 'start-void': ('$v vector<int> = [1,2];$b=task_start($v,2,function(int $n):void{});$r=task_join($b);echo count($r),$r[0]===null;', '21', False),
 'worker-error': ('$v vector<int> = [0];task_run($v,2,fn(int $n):int=>1/$n);', '', "operator '/' rejected a zero divisor"),
 'start-error': ('$v vector<int> = [0];$b=task_start($v,2,fn(int $n):int=>1/$n);task_join($b);', '', "operator '/' rejected a zero divisor"),
 'erased-capture': ('class Box{public int $n=7;}$b=new Box();$f function<int(int)> = function(int $i)use($b):int{return $b->n;};$v vector<int> = [1];task_run($v,2,$f);', '', 'Task callback captures are not transferable'),
 'unordered-empty': ('$v vector<int> = [];echo task_run_publish_unordered($v,2,fn(int $n):int=>$n,function(int $n):void{});', '0', False),
 'unordered-invalid': ('$v vector<int> = [];task_run_publish_unordered($v,0,fn(int $n):int=>$n,function(int $n):void{});', '', 'workers must be positive'),
 'publisher-context-guard': ('$v vector<int> = [1];task_run_publish_unordered($v,2,fn(int $n):int=>$n,function(int $n):void{task_synchronize(function():void{});});', '', True),

 'run-vector': ('$v vector<int> =[20,21];$r=task_run($v,2,fn(int $n):int=>$n*2);echo $r[0],$r[1];', '4042', False),
 'run-hash': ('$v hash<int,string> ={"a":20,"b":21};$r=task_run($v,2,fn(int $n):int=>$n*2);echo $r["a"],$r["b"];', '4042', False),
 'run-void': ('$v vector<int> =[20,21];$r=task_run($v,2,function(int $n):void{});$n ?int=$r[0];echo count($r),$n??7;', '27', False),
 'run-context': ('$v vector<int> =[20,21];$r=task_run($v,2,function(int $n,task_context $ctx):int{task_set_status($ctx,"working");return $n*2;});echo $r[0],$r[1];', '4042', False),
 'start': ('function launch():task_batch{$n=2;$v vector<int> =[20,21];return task_start($v,2,function(int $i)use($n):int{return $i*$n;});}$b=launch();$r=task_join($b);$s=task_join($b);echo $r[0],$s[1],task_done($b);', '40421', False),
 'publish': ('$v vector<int> =[20,21];echo task_run_publish($v,2,fn(int $n):int=>$n*2,function(vector<int> $batch):void{});', '2', False),
 'unordered': ('$v vector<int> =[20,21];echo task_run_publish_unordered($v,2,function(int $n):int{task_synchronize(function():void{});return $n*2;},function(int $n):void{});', '2', False),

 'synchronize': ('probe_work(function():void{task_synchronize(function():void{echo 42;});task_synchronize(function():void{echo 7;});});', '427', False),
 'synchronize-outside': ('task_synchronize(function():void{echo "unexpected";});', '', True),
 'synchronize-recursive': ('probe_work(function():void{task_synchronize(function():void{task_synchronize(function():void{echo "unexpected";});});});', '', True),
}
# Cover every carrier/context/void variant of the prefilled-target contracts.
for carrier in ['vector', 'hash']:
 for context in [False, True]:
  for background in [False, True]:
   for void in [False, True]:
    name='target-contract-'+carrier+('-context' if context else '')+('-start' if background else '-run')+('-void' if void else '-value')
    items='$v vector<int> = [42];' if carrier=='vector' else '$v hash<int,string> = {"a":42};'
    callback='function(int $n'+(',task_context $ctx' if context else '')+'):'+('void{}' if void else 'int{return $n;}')
    target=('$t=task_run($v,1,function(int $n):void{});' if void else ('$t vector<int> = [7];' if carrier=='vector' else '$t hash<int,string> = {"a":7};'))
    call=('task_start' if background else 'task_run')+'($v,2,'+callback+',null,$t)'
    result='$b='+call+';$r=task_join($b);' if background else '$r='+call+';'
    output='echo count($r),count($t);'
    expected='21' if carrier=='vector' else '11'
    if not void:
     output+='echo $r['+('1' if carrier=='vector' else '"a"')+'];'
     expected+='42'
    cases[name]=(items+target+result+output,expected,False)
if a.case:
 unknown=set(a.case)-set(cases)
 if unknown: raise ValueError('Unknown cases: '+str(unknown))
 cases={n:c for n,c in cases.items() if n in a.case}
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
   if fails: assert (fails if isinstance(fails,str) else 'task_synchronize requires an unordered work callback') in (out/'logs'/(run_name+'.stderr')).read_text()
   if temperature=='warm': assert build['compiled']==build['linked']==0
  if a.sanitize:
   build=json.loads(runner.run(name+'-sanitized',['php',APP/'tests/async_sanitized_build.php',folder/'published',folder/'sanitized-cache',sanitizer],timeout=300))
   assert runner.run(name+'-sanitized-run',[build['executable']],expected=1 if fails else 0)==expected.encode()
 tasks=[Task(name,lambda n=name,s=s,e=e,f=f:check(n,s,e,f)) for name,(s,e,f) in cases.items()]
 tasks.append(Task('contracts',lambda:runner.run('contracts',['php',APP/'tests/task_submission_contracts.php',metadata,out/'contract-source'])))
 run_tasks(tasks)
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases)},indent=2)+'\n')
