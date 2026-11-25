"""Object identity keys through portable PHP, legacy and concrete v0.2."""
import argparse,json,shutil,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];APP=ROOT/'compiler/my-try'
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True);source=out/'source';source.mkdir()
shutil.copyfile(ROOT/'tests/portability/fixtures/object_keys_common.php',source/'main.php')
tools=ROOT/'tools/php_portability';expected=b'2:11:20:9:11:7:20:1\n'
with CommandRunner(out/'logs',ROOT) as r:
 def portable():
  assert r.run('php',['php8.5','-d','auto_prepend_file='+str(tools/'runtime/bootstrap.php'),source/'main.php'])==expected
  r.run('convert',['php8.5',tools/'convert.php',source,out/'legacy'])
  project=out/'legacy';phs=(project/'main.phs').read_text();assert 'hash<int, Row>' in phs
  r.run('init',['php8.5',ROOT/'bin/scpp.php','init','--php-profile=strict'],project,cli_reason='CLI fixture setup')
  config=json.loads((project/'prism.json').read_text());config['build']['cxx']='clang++-18';(project/'prism.json').write_text(json.dumps(config))
  r.run('legacy-build',['php8.5',ROOT/'bin/scpp.php','build','--build-runtime','--no-stan'],project,timeout=240,cli_reason='CLI fixture build')
  assert r.run('legacy-run',[project/'.prism/build/main'])==expected
 run_tasks([Task('portable',portable)])
 phs=(out/'legacy/main.phs').read_text()
 cases={'identity':(phs,expected,None), 'upcast':("""
interface Key {} class Base implements Key {} class Row extends Base {}
$a = new Row(); $alias Key = $a; $b = new Row();
$m hash<int, Key> = [$a => 4];
$m[$alias] = 5; $m[$b] = 6;
echo $m[$a], ":", isset($m[$a]), ":", count($m), ":";
unset($m[$a]); echo isset($m[$alias]), ":", $m[$b], "\\n";
$ints hash<int, int> = new hash<int, int>(); $ints[1] = 7; echo $ints[1], "\\n";
""", b'5:1:2::6\n7\n', None)}
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
 tasks.append(Task('semantic-contracts',lambda:r.run('semantic-contracts',['php8.5',APP/'tests/object_key_contracts.php',contracts])))
 run_tasks(tasks,jobs=12)
(out/'result.json').write_text(json.dumps({'passed':True,'native_cases':4,'legacy':True,'php':True,'native_compiler_rebuilt':False},indent=2))
