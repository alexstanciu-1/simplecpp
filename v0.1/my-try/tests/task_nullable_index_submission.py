"""Production task handler signatures and recovery, default prelowering on dev002."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--case',action='append',default=[])
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
metadata=ROOT/'runtime/metadata/modules'
rows=[r for r in json.loads((metadata/'tasks.json').read_text())['functions'] if r['id'].endswith('.nullable-key')]
assert len(rows)==32
cases={}
for row in rows:
 params=row['parameters'];background=row['name']=='task_start';vector=params[0]['type']['name']=='vector'
 context=len(params[2]['type']['callable']['parameters'])==2
 kind=params[3]['type']['callable']['returns']['arguments'][0];error=isinstance(params[5]['type'],dict)
 for void in [False,True]:
  for absent in [False,True]:
   name=row['id'].replace('.','-')+('-void' if void else '-value')+('-absent' if absent else '-present')
   source='$v vector<int> = [0];' if vector else '$v hash<int,string> = {"a":0};'
   worker='function(int $n'+(',task_context $c' if context else '')+'):'+('void' if void else 'int')+'{'+('1/$n;' if error else '')+('' if void else 'return 42;')+'}'
   key='fn(int $n):?'+kind+'=>'+('null' if absent else '7' if kind=='int' else '"key"')
   args=['$v','1',worker,key,'null']
   if error:args+=['function(int $n,task_error $e):'+('void{}' if void else 'int{return 42;}')]
   call=row['name']+'('+','.join(args)+')';source+=('$b='+call+';$r=task_join($b);' if background else '$r='+call+';')
   omitted=error and void
   failure=kind=='string' and absent and not omitted
   source+='echo count($r);';expected=b'0' if omitted else b'1'
   if not void and not failure:
    lookup='0' if absent else '7' if kind=='int' else '"key"'
    source+='echo $r['+lookup+'];';expected+=b'42'
   cases[name]=(source,b'' if failure else expected,'requires integer keys' if failure else False)
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
