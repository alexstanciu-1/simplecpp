"""V-R07 source helpers, copy boundaries and production publication/build."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True)
out=p.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/vector_helpers.php',out])),
  Task('copy-requirements',lambda:runner.run('copy-requirements',['php',APP/'tests/runtime_copy_requirements.php',out]))])
 checks=json.loads((out/'checks.json').read_text());cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 cpp.flags.extend(['-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
 def execute(name):
  cpp.compile(name+'-build',out/(name+'.cpp'),out/name);runner.run(name+'-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/name])
 run_tasks([Task(n,lambda n=n:execute(n)) for n in checks['programs']+['copy_control']])
 # Admitted move-only fills need explicit investigation before claiming closure.
 blockers=[]
 for name,row in checks['move_probes'].items():
  if row['compiled']:
   try:
    cpp.compile('move-'+name+'-build',out/('move_'+name+'.cpp'),out/('move_'+name))
   except RuntimeError as error:
    blockers.append({'probe':name,'diagnostic':str(error)})
   else:
    blockers.append({'probe':name,'diagnostic':'Noncopyable fill was admitted; preparation must reject it'})
 source=out/'source';source.mkdir();(source/'main.phs').write_text('$v=vector_filled(2,7);vector_reserve($v,16);vector_resize($v,3,9);vector_compact($v);vector_compact($v,4);echo count($v),":",$v[2],":";vector_clear($v);vector_clear_keep_capacity($v);echo count($v),"\\n";')
 published=out/'published';cache=out/'cache'
 def production():
  runner.run('publish',['php',APP/'tests/fixtures/runtime_build_publish.php',ROOT/'runtime/metadata/modules',source,published,'core'],cli_reason='Independent production publication')
  for name in ['cold','warm']:
   result=json.loads(runner.run(name+'-build',['php',APP/'tests/fixtures/native_cache_driver.php',published,cache,ROOT/'runtime/include','/usr/bin/clang++'],timeout=300,cli_reason='Managed production provider build'))
   actual=runner.run(name+'-execute',[result['executable']]);assert actual==b'3:9:0\n',actual
   if name=='warm':assert result['compiled']==0 and result['linked']==0,result
   (out/(name+'.json')).write_text(json.dumps(result,indent=2)+'\n')
 run_tasks([Task('production',production)])
(out/'summary.json').write_text(json.dumps({'passed':not blockers,'blockers':blockers,'checks':checks,'production_build':True,'runtime_changes':False},indent=2)+'\n')
if blockers:
 raise RuntimeError('Vector copy-requirement prerequisite remains open; see summary.json')
