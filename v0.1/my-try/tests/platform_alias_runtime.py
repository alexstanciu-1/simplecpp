"""Runtime-first microtime/environment probe, without compiling on the local host."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True);cpp.flags.extend(['-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
 def execute():
  cpp.compile('runtime-build',APP/'tests/platform_alias_runtime.cpp',out/'runtime')
  runner.run('runtime-execute',['env','-u','SCPP_ALIAS_MISSING','SCPP_ALIAS_EMPTY=','SCPP_ALIAS_VALUE=original','ASAN_OPTIONS=detect_leaks=0',out/'runtime']+(['baseline'] if a.baseline else []))
 run_tasks([Task('runtime',execute)])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'observations':(out/'logs/runtime-execute.stdout').read_text()},indent=2)+'\n')
