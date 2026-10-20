"""Direct regex replacement/filter prerequisite and behavior oracle on dev002."""
import argparse, json, shlex, sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1]; ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
    cpp.flags.extend(['-I',APP/'tests','-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
    provider=out/'regex.o'
    runner.run('provider-build',[*cpp.flags,'-c',ROOT/'runtime/include/modules/regex/regex.cpp','-o',provider])
    cpp.link_flags.extend([provider,'-lpcre2-8'])
    def execute():
        exe=out/'probe';cpp.compile('probe-build',APP/'tests/regex_replacement_runtime.cpp',exe)
        runner.run('probe-execute',['env','ASAN_OPTIONS=detect_leaks=0',exe]+(['baseline'] if a.baseline else []))
    run_tasks([Task('runtime',execute)])
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'observations':(out/'logs/probe-execute.stdout').read_text()},indent=2)+'\n')
