"""Production task handler signatures and recovery, default prelowering on dev002."""
import argparse,json,sys,shutil
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--case',action='append',default=[])
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=out/'metadata'
shutil.copytree(ROOT/'runtime/metadata/modules',metadata)
(metadata/'task_lifecycle_probe.json').write_text(json.dumps({'version':1,'module':'task_lifecycle_probe','dependencies':['tasks','datetime'],'functions':[],'cpp':[]}))
cases={}
blocked={}
for background in [False,True]:
 for handled in [False,True]:
  name=('start' if background else 'run')+('-handled-timeout' if handled else '-timeout')
  source='$v vector<int> = [1];'
  worker='function(int $n):int{dt_sleep_ms(30);return $n;}'
  handler='function(int $n,task_error $e):int{echo $e->timeout,$e->kind;return 42;}' if handled else 'null'
  call=('task_start' if background else 'task_run')+'($v,1,'+worker+',null,null,'+handler+',1)'
  source+=('$b='+call+';$r=task_join($b);' if background else '$r='+call+';')
  source+='echo $r[0];'
  cases[name]=(source,b'1timeout42' if handled else b'',False if handled else 'task batch timed out')
for pool in [0,2]:
 setup='task_set_worker_pool_size('+str(pool)+');'
 source=setup+'$v vector<int> = [];for($i=0;$i<100;++$i){$v[]=$i;}$b=task_start($v,1,function(int $n,task_context $ctx):int{task_set_status($ctx,"started");dt_sleep_ms(100);return $n;});$p=task_progress($b);$tries=0;while(task_status($b)!=="started" && $tries<1000){dt_sleep_ms(1);++$tries;}if(task_status($b)!=="started"){return 2;}task_cancel($b);task_join($b);if(!$p->stop_requested() || $p->completed()>=100 || $p->active()!==0 || !task_done($b)){return 3;}echo "cancelled";'
 cases['cancel-pool-'+str(pool)]=(source,b'cancelled',False)
 source='function launch():void{$v vector<int> = [1];task_start($v,1,function(int $n):int{dt_sleep_ms(30);echo "W";return $n;});}'+setup+'launch();echo "M";'
 cases['shutdown-pool-'+str(pool)]=(source,b'MW',False)
 source=setup+'$v vector<int> = [1];$b=task_start($v,1,function(int $n):int{throw Exception::make("worker");});for($i=0;$i<2;++$i){try{task_join($b);return 2;}catch(Exception $e){echo $e->get_message();}}echo task_done($b);'
 blocked['repeated-error-pool-'+str(pool)]=(source,'prelower_templates: throw operation requires concrete transport')
for pool in [0,2]:
 setup='task_set_worker_pool_size('+str(pool)+');'
 source='function launch():void{$v vector<int> = [1];task_start($v,1,function(int $n):int{dt_sleep_ms(30);$z=0;return 1%$z;});}'+setup+'launch();'
 cases['shutdown-error-pool-'+str(pool)]=(source,b'',"operator '%' rejected a zero divisor")
 cases['shutdown-primary-error-pool-'+str(pool)]=(source+'$z=0;echo 1/$z;',b'',"operator '/' rejected a zero divisor")
cases['publication-metrics']=('task_set_publish_try_lock(true);$v vector<int> = [1,2,3];echo task_run_publish($v,2,fn(int $n):int=>$n,function(vector<int> $values):void{},null,0,1);task_set_publish_try_lock(false);if(task_publish_published_count()!==3 || task_publish_max_batch_size()!==1 || task_publish_batch_count()!==3){return 2;}if(task_publish_lock_wait_us()<0 || task_publish_lock_hold_us()<0 || task_publish_callback_us()<0 || task_publish_failed_try_lock_count()<0 || task_publish_deferred_flush_count()<0){return 3;}echo "metrics";',b'3metrics',False)
if a.case:
 assert set(a.case)<=set(cases)|set(blocked),set(a.case)-set(cases)-set(blocked)
 cases={n:c for n,c in cases.items() if n in a.case}
 blocked={n:c for n,c in blocked.items() if n in a.case}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def check(name,source,expected,failure):
  directory=out/name;(directory/'source').mkdir(parents=True);(directory/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',directory,metadata,'task_lifecycle_probe','concrete'])
  assert json.loads((directory/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
  for temperature in ['cold','warm']:
   build=json.loads(runner.run(name+'-'+temperature,['php',APP/'tools/build_cpp.php',directory/'published',directory/'cache'],timeout=300))
   label=name+'-'+temperature+'-run'
   assert runner.run(label,[build['executable']],expected=1 if failure else 0)==expected
   if failure:assert failure in (out/'logs'/(label+'.stderr')).read_text()
   if temperature=='warm':assert build['compiled']==build['linked']==0
 def check_blocker(name,source,diagnostic):
  directory=out/name;(directory/'source').mkdir(parents=True);(directory/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php',APP/'tests/async_tasks_publish.php',directory,metadata,'task_lifecycle_probe','concrete'],expected=255)
  assert diagnostic in (out/'logs'/(name+'-publish.stderr')).read_text()
 run_tasks([Task('module-'+module,lambda module=module:runner.run('module-'+module,['php',APP/'tests/task_module_contracts.php',metadata,out/('module-'+module),module])) for module in ['tasks','core']]+[Task(n,lambda n=n,s=s,d=d:check_blocker(n,s,d)) for n,(s,d) in blocked.items()]+[Task(n,lambda n=n,s=s,e=e,f=f:check(n,s,e,f)) for n,(s,e,f) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases),'known_blockers':list(blocked)},indent=2)+'\n')
