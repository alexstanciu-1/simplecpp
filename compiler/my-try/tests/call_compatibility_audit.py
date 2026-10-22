"""Observe pinned-v0.1/v0.2 call compatibility; successful recording is not feature support."""
import argparse
import hashlib
import json
from pathlib import Path
import sys
APP=Path(__file__).resolve().parents[1]
ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--baseline',type=Path,required=True)
p.add_argument('--results',type=Path,required=True)
p.add_argument('--expectations',type=Path,default=APP/'tests/call_compatibility_expectations.json')
p.add_argument('--cases',help='Comma-separated focused subset; default is the full bounded audit')
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
inventory=json.loads((APP/'tests/call_compatibility_audit.json').read_text())
if a.cases:
 selected=set(a.cases.split(','));inventory['cases']=[c for c in inventory['cases'] if c['name'] in selected]
 assert len(inventory['cases'])==len(selected)
with CommandRunner(out/'logs',ROOT,jobs=12,journals=[out/'commands.json']) as runner:
 cpp=CppFixtureCompiler(runner,a.baseline,out,pch=False,runtime=True)
 cpp.flags.append('-std=c++23')
 def observe(case,version):
  name=version+'-'+case['name'];d=out/name;(d/'source').mkdir(parents=True)
  source=d/'source/main.phs';source.write_text(case['source'])
  row=dict(name=case['name'],version=version,group=case['group'],source_sha256=hashlib.sha256(source.read_bytes()).hexdigest(),stage='generation',supported=False)
  try:
   if version=='v01':
    generated=runner.run(name+'-generate',['php',APP/'tests/compatibility_export.php',a.baseline,source,'strict'])
    target=d/'main.cpp';target.write_bytes(generated);row['stage']='build'
    exe=d/'program';cpp.compile(name+'-build',target,exe)
   else:
    runner.run(name+'-generate',['php',APP/'main.php','--s2s',d/'source','--output',d/'published'])
    assert json.loads((d/'published/.scpp-output.manifest').read_text())['application_templates']=='forbidden'
    row['stage']='build'
    build=json.loads(runner.run(name+'-cold',['php',APP/'tools/build_cpp.php',d/'published',d/'cache'],timeout=600))
    exe=build['executable']
   row['stage']='execution';actual=runner.run(name+'-run',[exe]).decode();row['stdout']=actual
   assert actual==case['stdout'],(actual,case['stdout'])
   if version=='v02':
    row['stage']='warm-build'
    warm=json.loads(runner.run(name+'-warm',['php',APP/'tools/build_cpp.php',d/'published',d/'cache'],timeout=600))
    assert warm['compiled']==0 and not warm['linked'],warm
    assert runner.run(name+'-warm-run',[warm['executable']]).decode()==case['stdout']
    row['warm_no_work']=True
   row.update(stage='executed',supported=True)
  except Exception as error:
   row['failure']=str(error)
  (d/'outcome.json').write_text(json.dumps(row,indent=2)+'\n')
  return row
 outcomes=run_tasks([Task(v+'-'+c['name'],lambda c=c,v=v:observe(c,v)) for c in inventory['cases'] for v in ['v01','v02']])
 rows=[o.value for o in outcomes]
 (out/'outcomes.json').write_text(json.dumps(rows,indent=2)+'\n')
 runner.run('retained',['php',APP/'tests/call_audit_retained.php',out/'retained'])
 published=out/'retained-published';cache=out/'retained-cache'
 assert json.loads((published/'.scpp-output.manifest').read_text())['application_templates']=='forbidden'
 for mode in ['cold','warm']:
  build=json.loads(runner.run('retained-'+mode,['php',APP/'tools/build_cpp.php',published,cache],timeout=600))
  if mode=='warm':assert build['compiled']==0 and not build['linked']
  assert runner.run('retained-'+mode+'-run',[build['executable']])==b'79'
 if a.expectations:
  expected=json.loads(a.expectations.read_text())
  for row in rows:
   key=row['version']+'-'+row['name']
   assert row['stage']==expected[key]['stage'],row
   assert row['supported']==expected[key]['supported'],row
   assert row['source_sha256']==expected[key]['source_sha256'],row
   assert row.get('stdout')==expected[key].get('stdout'),row
 print(json.dumps({v:{'executed':sum(r['supported'] for r in rows if r['version']==v),'observations':sum(r['version']==v for r in rows)} for v in ['v01','v02']},indent=2))
