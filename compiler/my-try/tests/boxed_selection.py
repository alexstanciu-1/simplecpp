"""Bounded boxed-bitwise and scalar-wrapper coalescing acceptance on dev002."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/boxed_selection.php',out]))])
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True);cpp.flags.extend(['-I',APP/'tests','-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
 def execute(source):
  name=source.stem;cpp.compile(name+'-build',source,out/name);actual=runner.run(name+'-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/name])
  if name.startswith('recovery-'):assert actual==({'recovery-coalesce':b'1','recovery-boxed':b'2'}[name]),actual
 sources=list(out.glob('*.cpp'))+[APP/'tests/boxed_selection_runtime_control.cpp']
 run_tasks([Task(s.stem,lambda s=s:execute(s)) for s in sources])
 source=out/'production-source';source.mkdir()
 (source/'main.phs').write_text('$a dynamic=7;$b dynamic=2;$c int=$a & $b;$x result<int> = 0;$y result<int> = 9;$z int=$x ?? $y;echo $c,":",$z,"\\n";')
 published=out/'published';cache=out/'cache'
 runner.run('publish',['php',APP/'tests/fixtures/runtime_build_publish.php',ROOT/'runtime/metadata/modules',source,published,'core'],cli_reason='Production core publication')
 for name in ['cold','warm']:
  build=json.loads(runner.run(name+'-build',['php',APP/'tests/fixtures/native_cache_driver.php',published,cache,ROOT/'runtime/include','/usr/bin/clang++'],timeout=300,cli_reason='Production native build'))
  assert runner.run(name+'-execute',[build['executable']])==b'2:0\n'
  if name=='warm':assert build['compiled']==0 and build['linked']==0,build
  (out/(name+'.json')).write_text(json.dumps(build,indent=2)+'\n')
(out/'summary.json').write_text(json.dumps({'passed':True,'programs':len(sources),'checks':json.loads((out/'checks.json').read_text()),'full_matrix_run':False},indent=2)+'\n')
