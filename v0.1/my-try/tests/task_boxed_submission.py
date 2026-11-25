"""Production task metadata with native input fixtures, default prelowering on dev002.

Native fixtures isolate known general boxed literal/index prelowering gaps. They
are not evidence that those source operations work. Mixed controls replace the
caller variable; dynamic controls mutate shared input after start snapshots it.
"""
import argparse,json,sys,shutil
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--case',action='append',default=[])
p.add_argument('--source-only',action='store_true',help='Use production source literals and indexing instead of native fixtures')
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=out/'metadata'
shutil.copytree(ROOT/'runtime/metadata/modules',metadata)
module={'version':1,'module':'task_boxed_probe','dependencies':['tasks','datetime'],'functions':[],'cpp':[]}
for family in ['mixed','dynamic']:
 for shape in ['input','nested']:
  name='probe_'+family+'_'+shape
  module['functions'].append({'id':name,'name':name,'parameters':[],'returns':family})
  module['cpp'].append({'id':name,'symbol':'task_boxed_probe::'+family+'_'+shape,'header':str(APP/'tests/fixtures/tasks/boxed.hpp')})
module['functions'].append({'id':'probe_dynamic_first','name':'probe_dynamic_first','parameters':[{'name':'value','type':'dynamic','passing':'value'}],'returns':'dynamic'})
module['cpp'].append({'id':'probe_dynamic_first','symbol':'task_boxed_probe::dynamic_first','header':str(APP/'tests/fixtures/tasks/boxed.hpp')})
module['functions'].append({'id':'probe_dynamic_mutate','name':'probe_dynamic_mutate','parameters':[{'name':'value','type':'dynamic','passing':'value'}],'returns':'void'})
module['cpp'].append({'id':'probe_dynamic_mutate','symbol':'task_boxed_probe::dynamic_mutate','header':str(APP/'tests/fixtures/tasks/boxed.hpp')})
if a.source_only:module['functions']=[];module['cpp']=[]
(metadata/'task_boxed_probe.json').write_text(json.dumps(module))
cases={}
for family in ['mixed','dynamic']:
 for background in [False,True]:
  for context in [False,True]:
   prefix=family+('-start' if background else '-run')+('-context' if context else '')
   params=family+' $n'+(',task_context $ctx' if context else '')
   for result,body,expected in [('int','return 7;',b'7'),('float','return 1.5;',b'1.5'),('bool','return true;',b'1'),('string','return "ok";',b'ok'),(family,'return $n;',b'3'),('void','',b'')]:
    worker='function('+params+'):'+result+'{'+body+'}'
    call=('task_start' if background else 'task_run')+'($v,1,'+worker+')'
    source='$v=probe_'+family+'_input();'+('$b='+call+';$r=task_join($b);' if background else '$r='+call+';')+('echo probe_dynamic_first($r);' if family=='dynamic' else 'echo $r[0];')
    cases[prefix+'-'+result]=(source,expected,False)
   for scenario,body,message in [('nested-input','return $n;','task payload must be a scalar boxed value'),('nested-result','return probe_'+family+'_input();','task payload must be a scalar boxed value')]:
    worker='function('+params+'):'+family+'{'+body+'}'
    call=('task_start' if background else 'task_run')+'($v,1,'+worker+')'
    source='$v=probe_'+family+('_nested();' if scenario=='nested-input' else '_input();')+('$b='+call+';task_join($b);' if background else call+';')
    cases[prefix+'-'+scenario]=(source,b'',message)
for family in ['mixed','dynamic']:
 for background in [False,True]:
  for context in [False,True]:
   prefix=family+('-start' if background else '-run')+('-context' if context else '')
   params=family+' $n'+(',task_context $ctx' if context else '')
   for mode in ['replace','omit','nested-handler']:
    result=family if mode=='nested-handler' else 'int'
    worker='function('+params+'):'+result+'{$z=0;1/$z;return '+('$n' if result==family else '1')+';}'
    handler='function('+family+' $n,task_error $e):'+('void{}' if mode=='omit' else result+'{return '+('probe_'+family+'_input()' if mode=='nested-handler' else '42')+';}')
    call=('task_start' if background else 'task_run')+'($v,1,'+worker+',null,null,'+handler+')'
    source='$v=probe_'+family+'_input();'+('$b='+call+';$r=task_join($b);' if background else '$r='+call+';')
    source+=('echo count($r);' if mode=='omit' else ('echo probe_dynamic_first($r);' if family=='dynamic' else 'echo $r[0];'))
    cases[prefix+'-'+mode]=(source,b'0' if mode=='omit' else (b'' if mode=='nested-handler' else b'42'),'task payload must be a scalar boxed value' if mode=='nested-handler' else False)
for family in ['mixed','dynamic']:
 read='probe_dynamic_first($r)' if family=='dynamic' else '$r[0]'
 mutation='probe_dynamic_mutate($v);' if family=='dynamic' else '$v=probe_mixed_nested();'
 source='$v=probe_'+family+'_input();$b=task_start($v,1,function('+family+' $n,task_context $ctx):'+family+'{dt_sleep_ms(30);task_set_status($ctx,"finished");return $n;});'+mutation+'$r=task_join($b);echo '+read+';echo task_done($b),task_status($b);$p=task_progress($b);echo $p->total(),$p->completed();task_cancel($b);$r=task_join($b);echo '+read+';'
 cases[family+'-snapshot-controls']=(source,b'31finished113',False)
if a.source_only:
 for family in ['mixed','dynamic']:
  for shape,body,expected in [
   ('scalars','[null,false,true,4,1.5,"s"];echo count($v),$v[0],$v[1],$v[2],$v[3],$v[4],$v[5];',b'6141.5s'),
   ('keyed','{a:1,b:2};$v["a"]=9;echo $v["a"],$v["b"];',b'92'),
   ('empty','[];echo count($v);$v='+family+'::{};echo count($v);',b'00'),
   ('nested-read','{a:[1]};$r=$v["a"];echo $r[0];',b'1'),
   ('nested-write','{a:[1]};$v["a"][0]=9;$r=$v["a"];echo $r[0];',b'9')]:
   cases[family+'-source-'+shape]=('$v '+family+' = '+body,expected,False)
 for family in ['mixed','dynamic']:
  source='function next(int &$n):int{++$n;return $n;}$n=0;$v '+family+' = [next($n),next($n)];echo $v[0],$v[1],$n;'
  cases[family+'-initializer-order']=(source,b'122',False)
  source='$v '+family+' = {a:[1]};$old=$v;$v["a"][0]=9;$before=$old["a"];$after=$v["a"];echo $before[0],$after[0];'
  cases[family+'-nested-alias']=(source,b'19' if family=='mixed' else b'99',False)
  source='$v '+family+' = {a:[1]};$v["a"][]=9;$r=$v["a"];echo count($r),$r[0],$r[1];'
  cases[family+'-nested-append']=(source,b'219',False)
  source='function key(int &$n):string{$n=$n*10+1;return "a";}function index(int &$n):int{$n=$n*10+2;return 0;}function value(int &$n):int{$n=$n*10+3;return 9;}$n=0;$v '+family+' = {a:[1]};$v[key($n)][index($n)]=value($n);$r=$v["a"];echo $n,$r[0];'
  cases[family+'-write-order']=(source,b'1239',False)
  source='function replace('+family+' &$v):int{$v='+family+'::{a:[7]};return 9;}$v '+family+' = {a:[1]};$old=$v;$v["a"][0]=replace($v);$r=$v["a"];$before=$old["a"];echo $before[0],$r[0];'
  cases[family+'-write-root-replacement']=(source,b'19',False)
 cases['typed-initializer-regression']=('$v vector<string> = ["a","b"];$h hash<string,string> = {x:"c"};echo $v[0],$v[1],$h["x"];',b'abc',False)
 cases['typed-boxed-initializer-regression']=('$m mixed = 7;$v vector<mixed> = [$m];$m=9;echo $v[0],$m;',b'79',False)
 converted={}
 for name,(source,expected,failure) in cases.items():
  for family in ['mixed','dynamic']:
   source=source.replace('probe_'+family+'_input()',family+'::[3]').replace('probe_'+family+'_nested()',family+'::[[3]]')
  source=source.replace('probe_dynamic_first($r)','$r[0]').replace('probe_dynamic_mutate($v)','$v[0]=9')
  converted[name]=(source,expected,failure)
 cases=converted
if a.case:
 assert set(a.case)<=set(cases),set(a.case)-set(cases)
 cases={n:c for n,c in cases.items() if n in a.case}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def check(name,source,expected,failure):
  directory=out/name;(directory/'source').mkdir(parents=True);(directory/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',directory,metadata,'task_boxed_probe','concrete'])
  assert json.loads((directory/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for temperature in ['cold','warm']:
   build=json.loads(runner.run(name+'-'+temperature,['php',APP/'tools/build_cpp.php',directory/'published',directory/'cache'],timeout=300))
   label=name+'-'+temperature+'-run'
   assert runner.run(label,[build['executable']],expected=1 if failure else 0)==expected
   if failure:assert failure in (out/'logs'/(label+'.stderr')).read_text()
   if temperature=='warm':assert build['compiled']==build['linked']==0
 run_tasks([Task('contracts',lambda:runner.run('contracts',['php',APP/'tests/task_boxed_contracts.php',metadata,out/'contract-source']))]+[Task(n,lambda n=n,s=s,e=e,f=f:check(n,s,e,f)) for n,(s,e,f) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases),'source_only':a.source_only},indent=2)+'\n')
