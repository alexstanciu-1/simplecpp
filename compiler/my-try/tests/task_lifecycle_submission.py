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
if a.case:
 assert set(a.case)<=set(cases),set(a.case)-set(cases)
 cases={n:c for n,c in cases.items() if n in a.case}
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
 run_tasks([Task(n,lambda n=n,s=s,e=e,f=f:check(n,s,e,f)) for n,(s,e,f) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases)},indent=2)+'\n')
