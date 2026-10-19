import sys,json,hashlib,subprocess,os
from pathlib import Path
sys.dont_write_bytecode=True
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01'); root=Path(__file__).resolve().parent
sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_artifacts import materialize_artifacts
from cpp_fixture import build_runtime_object
inventory=repo/'compiler/my-try/docs/planning/diagnostic_inventory_2026_10_18/cases.json'
data=json.loads(inventory.read_text()); (root/'inventory.json').write_text(inventory.read_text())
out=root/'results';out.mkdir()
def fingerprints():
 files=[]
 for area in ['compiler/my-try','runtime/include','runtime/metadata']:
  files.extend(p for p in (repo/area).rglob('*') if p.is_file() and p.suffix in {'.php','.hpp','.h','.cpp','.json'} and not any(x in p.parts for x in ['temp','docs','__pycache__']))
 return {str(p.relative_to(repo)):hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(files)}
before=fingerprints();(root/'baseline.json').write_text(json.dumps({'head':subprocess.check_output(['git','rev-parse','HEAD'],cwd=repo,text=True).strip(),'status':subprocess.check_output(['git','status','--porcelain'],cwd=repo,text=True),'files':before,'clang':subprocess.check_output(['clang++','--version'],text=True),'php':subprocess.check_output(['php','--version'],text=True)},indent=2))
with CommandRunner(out/'logs',repo,jobs=12) as runner:
 runtime=build_runtime_object(runner,repo,out)
 def program(case,kind):
  name=case['id']+'-'+kind;d=out/name;d.mkdir()
  for f,s in case[kind+'_files'].items():(d/f).write_text(s)
  result=json.loads(runner.run(name+'-prepare',['php',root/'probe.php',d]))
  if result['stage']=='emitted':
   units=materialize_artifacts((d/'artifacts.json').read_text(),d/'cpp');objects=[];errors=[]
   for i,unit in enumerate(units):
    obj=unit.with_suffix('.o');objects.append(obj)
    try:runner.run(name+'-compile-'+str(i),['clang++','-std=c++20','-Werror=return-type','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',repo/'runtime/include','-I',d/'cpp','-c',unit,'-o',obj])
    except RuntimeError:errors.append(name+'-compile-'+str(i)+'.stderr')
   result['compile']='failed' if errors else 'passed';result['compile_logs']=errors
   if not errors:
    try:runner.run(name+'-link',['clang++',*objects,runtime,'-pthread','-o',d/'program']);result['link']='passed'
    except RuntimeError:result['link']='failed'
   if result.get('link')=='passed' and case['id'] in ['OP-01','OP-02','OP-03','OP-04','LOW-01']:
    expected={'OP-01':4,'OP-02':3,'OP-03':2,'OP-04':3,'LOW-01':1}[case['id']]
    try:runner.run(name+'-execute',[d/'program'],expected=expected);result['execution']='passed';result['expected_exit']=expected
    except RuntimeError:result['execution']='failed'
  (d/'result.json').write_text(json.dumps(result,indent=2))
  return result
 def task(c):
  try:
   if c['id'].startswith('REC-'):
    d=out/c['id'];d.mkdir();p=d/'case.json';p.write_text(json.dumps({'base':c['sequence']['baseline'],'edit':c['sequence']['edit']}))
    result={'recovery':json.loads(runner.run(c['id'],['php',root/'incremental.php',d,p]))}
   else:result={'control':program(c,'control'),'source':program(c,'source')}
  except Exception as e:result={'harness_error':str(e)[:1000]}
  (out/(c['id']+'.json')).write_text(json.dumps(result,indent=2))
  print(c['id']+' evidence collected',flush=True)
  return result
 active=[c for c in data['cases'] if c['status']=='inventory-ready-not-run']
 results=run_tasks([Task(c['id'],lambda c=c:task(c)) for c in active],jobs=12,report=False)
 (out/'summary.json').write_text(json.dumps({r.name:r.value for r in results},indent=2))
after=fingerprints();changed=[p for p in before.keys()|after.keys() if before.get(p)!=after.get(p)]
(root/'integrity.json').write_text(json.dumps({'changed_source_files':changed,'case_groups':len(active)},indent=2));print('Complete; source changes: '+str(len(changed)),flush=True)
