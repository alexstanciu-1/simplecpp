"""Common checked-cast spelling, legacy alias and concrete object-type transport."""
import argparse,json,shutil,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];APP=ROOT/'compiler/my-try'
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True);source=out/'source';source.mkdir()
shutil.copyfile(ROOT/'tests/portability/fixtures/object_cast_common.php',source/'main.php')
tools=ROOT/'tools/php_portability';expected=b'9:9:1:1:0\n'
with CommandRunner(out/'logs',ROOT) as r:
 def portable():
  assert r.run('php',['php8.5','-d','auto_prepend_file='+str(tools/'runtime/bootstrap.php'),source/'main.php'])==expected
  r.run('converter-contracts',['php8.5',ROOT/'tests/portability/object_casts.php'])
  r.run('convert',['php8.5',tools/'convert.php',source,out/'legacy'])
  project=out/'legacy';phs=(project/'main.phs').read_text();assert 'scpp_portability_object_cast' not in phs
  r.run('init',['php8.5',ROOT/'bin/scpp.php','init','--php-profile=strict'],project,cli_reason='CLI fixture setup')
  config=json.loads((project/'prism.json').read_text());config['build']['cxx']='clang++-18';(project/'prism.json').write_text(json.dumps(config))
  r.run('legacy-build',['php8.5',ROOT/'bin/scpp.php','build','--build-runtime','--no-stan'],project,timeout=240,cli_reason='CLI fixture build')
  assert r.run('legacy-run',[project/'.prism/build/main'])==expected
 run_tasks([Task('portable',portable)])
 phs=(out/'legacy/main.phs').read_text()
 cases={
 'identity':(phs,expected,None),
 'weak':('class P {public int $n=7;} $p=new P();$w=weak($p);$held=object_cast(lock($w),P::class);$held->n=9;echo $p->n;',b'9',None),
 'struct':('interface I {function read():int;} struct B implements I {int $n=7;function read():int{return $this->n;}} struct C extends B {} $c shared<C> =new C();$b shared<B> =$c;$out=object_cast($b,C::class);$out->n=9;echo $c->n, ":", $b instanceof C ? 1 : 0;',b'9:1',None),
 'empty':('class P {} $p ?P = null; $out=object_cast($p,P::class);',b'','Object cast requires a present compatible object'),
 'mismatch':('interface I {} class A implements I {} class B implements I {} $a ?I=new A();$out=object_cast($a,B::class);',b'','Object cast requires a present compatible object'),
 }
 def check(name,mode,data):
  src,stdout,error=data;folder=out/(name+'-'+mode);(folder/'source').mkdir(parents=True);(folder/'source/main.phs').write_text(src)
  key=folder.name
  r.run(key+'-publish',['php8.5',APP/'tests/async_tasks_publish.php',folder,ROOT/'runtime/metadata/modules','core',mode])
  manifest=json.loads((folder/'published/.scpp-output.manifest').read_text());assert manifest['application_templates']==('allowed' if mode=='ordinary' else 'forbidden')
  built=json.loads(r.run(key+'-build',['php8.5',APP/'tools/build_cpp.php',folder/'published',folder/'cache','-O0'],timeout=240))
  assert r.run(key+'-run',[built['executable']],expected=1 if error else 0)==stdout
  if error:assert error in (out/'logs'/(key+'-run.stderr')).read_text()
 tasks=[Task(n+'-'+m,lambda n=n,m=m,d=d:check(n,m,d)) for n,d in cases.items() for m in ['ordinary','concrete']]
 contracts=out/'contracts';contracts.mkdir()
 tasks.append(Task('semantic-contracts',lambda:r.run('semantic-contracts',['php8.5',APP/'tests/object_types.php',contracts])))
 run_tasks(tasks,jobs=12)
(out/'result.json').write_text(json.dumps({'passed':True,'native_cases':10,'legacy':True,'php':True,'nullable_object_inputs':True,'native_compiler_rebuilt':False},indent=2))
