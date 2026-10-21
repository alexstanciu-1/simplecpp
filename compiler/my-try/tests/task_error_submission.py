"""Production task handler signatures and recovery, default prelowering on dev002."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--case',action='append',default=[])
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=ROOT/'runtime/metadata/modules'
rows=[r for r in json.loads((metadata/'tasks.json').read_text())['functions'] if r['id'].endswith('.error')]
assert len(rows)==68, len(rows)
cases={}
for row in rows:
 publish=row['name']=='task_run_publish';background=row['name']=='task_start'
 params=row['parameters'];carrier=params[0]['type']['name']
 concrete_void=params[2]['type']['callable']['returns']=='void'
 has_target=not publish and params[4]['type']!='null'
 worker_void_options=[True] if concrete_void else ([False,True] if not publish and not has_target else [False])
 for worker_void in worker_void_options:
  for omit in ([True] if worker_void else [False,True]):
   name=row['id'].replace('.','-')+('-void-worker' if worker_void else '')+('-omit' if omit else '-replace')
   source='$v vector<int> = [0];' if carrier=='vector' else '$v hash<int,string> = {"a":0};'
   context=len(params[2]['type']['callable']['parameters'])==2
   worker='function(int $n'+(',task_context $ctx' if context else '')+'):'+('void{1/$n;}' if worker_void else 'int{return 1/$n;}')
   args=['$v','1',worker]
   if publish:
    context=len(params[3]['type']['callable']['parameters'])==2
    args+=['function(vector<int> $values'+(',task_context $ctx' if context else '')+'):void{}']
   else:
    index=params[3]['type']
    if index=='null':args+=['null']
    else:
     key=index['callable']['returns']
     if isinstance(key,dict) and key.get('name')=='nullable':key_type='?int';value='null'
     elif key=='int':key_type='int';value='0'
     else:key_type='string';value='"a"'
     args+=['function(int $n):'+key_type+'{return '+value+';}']
    if has_target:
     target=params[4]['type']['name']
     if worker_void:source+='$target=task_run($v,1,function(int $n):void{});'
     else:source+=('$target vector<int> = [7];' if target=='vector' else '$target hash<int,string> = {"a":7};')
     args+=['$target']
    else:args+=['null']
   args+=['function(int $n,task_error $e):'+('void{echo "h";}' if omit else 'int{echo "h";return 42;}')]
   call=row['name']+'('+','.join(args)+')'
   source+=('$b='+call+';task_join($b);' if background else call+';')+'echo "ok";'
   cases[name]=(source,b'hok',False)
# Independent semantic oracles: concrete values, original keys and omission identity.
cases['replacement-values']=('$v vector<int> = [0,1];$r=task_run($v,2,fn(int $n):int=>1/$n,null,null,fn(int $n,task_error $e):int=>42);echo $r[0],$r[1];',b'421',False)
cases['omission-keys']=('$v hash<int,string> = {"a":0,"b":1};$r=task_run($v,2,fn(int $n):int=>1/$n,null,null,function(int $n,task_error $e):void{});echo count($r),$r["b"];',b'11',False)
cases['keyed-omission']=('$v vector<int> = [0,1];$r=task_run($v,1,fn(int $n):int=>1/$n,fn(int $n):int=>$n,null,function(int $n,task_error $e):void{});echo count($r),$r[1];',b'11',False)
cases['error-event']=('$v vector<int> = [0];task_run($v,1,fn(int $n):int=>1/$n,null,null,function(int $n,task_error $e):int{echo $e->key,$e->worker_id,$e->timeout;return 42;});',b'00',False)
cases['publish-omission']=('$v vector<int> = [0,1];echo task_run_publish($v,1,fn(int $n):int=>1/$n,function(vector<int> $b):void{echo $b[0];},function(int $n,task_error $e):void{},0,1);',b'11',False)
cases['handler-throws']=('$v vector<int> = [0];task_run($v,1,fn(int $n):int=>1/$n,null,null,fn(int $n,task_error $e):int=>2/$n);',b'',"operator '/' rejected a zero divisor")
cases['start-handler-throws']=('$v vector<int> = [0];$b=task_start($v,1,fn(int $n):int=>1/$n,null,null,fn(int $n,task_error $e):int=>2/$n);task_join($b);',b'',"operator '/' rejected a zero divisor")
cases['coordinator-handler']=('class Counter{public int $n=0;}$c=new Counter();$v vector<int> = [0,0];$r=task_run($v,2,fn(int $n):int=>1/$n,null,null,function(int $n,task_error $e)use($c):int{++$c->n;return 42;});echo $c->n,$r[0],$r[1];',b'24242',False)
cases['owned-background-handler']=('function launch():task_batch{$fallback=42;$v vector<int> = [0];return task_start($v,1,fn(int $n):int=>1/$n,null,null,function(int $n,task_error $e)use($fallback):int{return $fallback;});}$b=launch();$r=task_join($b);$s=task_join($b);echo $r[0],$s[0];',b'4242',False)
cases['string-replacement']=('$v vector<int> = [0];$r=task_run($v,1,function(int $n):string{1/$n;return "unreachable";},null,null,fn(int $n,task_error $e):string=>"recovered");echo $r[0];',b'recovered',False)
cases['float-replacement']=('$v vector<int> = [0];$r=task_run($v,1,function(int $n):float{1/$n;return 0.0;},null,null,fn(int $n,task_error $e):float=>42.5);echo $r[0];',b'42.5',False)
cases['nullable-replacement']=('$v vector<int> = [0];$r=task_run($v,1,function(int $n):?int{1/$n;return $n;},null,null,function(int $n,task_error $e):?int{return null;});echo $r[0]??7;',b'7',False)
cases['nullable-input']=('$v vector<?int> = [null];$r=task_run($v,1,fn(?int $n):int=>1/($n??0),null,null,function(?int $n,task_error $e):int{echo $n??7;return 42;});echo $r[0];',b'742',False)
if a.case:
 assert set(a.case)<=set(cases),set(a.case)-set(cases)
 cases={n:c for n,c in cases.items() if n in a.case}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def check(name,source,expected,failure):
  directory=out/name;(directory/'source').mkdir(parents=True);(directory/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',directory,metadata,'tasks','concrete'])
  assert json.loads((directory/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for temperature in ['cold','warm']:
   build=json.loads(runner.run(name+'-'+temperature,['php',APP/'tools/build_cpp.php',directory/'published',directory/'cache'],timeout=300))
   label=name+'-'+temperature+'-run'
   assert runner.run(label,[build['executable']],expected=1 if failure else 0)==expected
   if failure:assert failure in (out/'logs'/(label+'.stderr')).read_text()
   if temperature=='warm':assert build['compiled']==build['linked']==0
 run_tasks([Task(n,lambda n=n,s=s,e=e,f=f:check(n,s,e,f)) for n,(s,e,f) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases)},indent=2)+'\n')
