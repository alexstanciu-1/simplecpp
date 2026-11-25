"""Qualified aliases and platform contracts through shared preparation and native builds."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/platform_alias.php',out]+(['--baseline'] if a.baseline else [])))])
 if not a.baseline:
  runner.run('metadata',['php',APP/'tests/platform_alias_metadata.php',out])
  cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True);cpp.flags.extend(['-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
  def execute(name,wanted):
   cpp.compile(name+'-build',out/(name+'.cpp'),out/name)
   actual=runner.run(name+'-execute',['env','-u','SCPP_ALIAS_MISSING','SCPP_ALIAS_EMPTY=','SCPP_ALIAS_VALUE=original','ASAN_OPTIONS=detect_leaks=0',out/name])
   assert actual==wanted,(name,actual)
  source=out/'source';source.mkdir();(source/'main.phs').write_text('$a string=microtime();$b string=php::microtime();$c float=microtime(true);$d float=php::microtime(true);if($c<=0.0||$d<=0.0){return 1;}if(fs_is_windows()){return 2;}$e string=getenv("SCPP_ALIAS_EMPTY");echo strlen($e),"\\n";')
  def production():
   published=out/'published';cache=out/'cache'
   runner.run('publish',['php',APP/'tests/fixtures/runtime_build_publish.php',ROOT/'runtime/metadata/modules',source,published,'filesystem'],cli_reason='Production module publication')
   for name in ['cold','warm']:
    build=json.loads(runner.run(name+'-build',['php',APP/'tests/fixtures/native_cache_driver.php',published,cache,ROOT/'runtime/include','/usr/bin/clang++'],timeout=300,cli_reason='Production native provider build'))
    actual=runner.run(name+'-execute',['env','SCPP_ALIAS_EMPTY=',build['executable']]);assert actual==b'0\n',actual
    if name=='warm':assert build['compiled']==0 and build['linked']==0,build
    (out/(name+'.json')).write_text(json.dumps(build,indent=2)+'\n')
  expected={'original':b'ok\n','behavior':b'four clock forms; false throws; Linux target; two policy branches; environment states and once-only names\n','shadow':b'7','free-shadow':b'9','custom':b'','retained-alias':b'','retained-shadow':b''}
  run_tasks([Task(n,lambda n=n,w=w:execute(n,w)) for n,w in expected.items()]+[Task('production',production)])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'checks':json.loads((out/('baseline.json' if a.baseline else 'checks.json')).read_text()),'full_matrix_run':False},indent=2)+'\n')
