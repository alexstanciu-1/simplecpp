"""Runtime-first tokenizer/resource contract probes; run on dev002."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');a=p.parse_args()
out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
    cpp.flags.extend(['-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
    def execute():
        cpp.compile('build',APP/'tests/tokenizer_io_runtime.cpp',out/'probe')
        runner.run('execute',['env','ASAN_OPTIONS=detect_leaks=0',out/'probe']+(['baseline'] if a.baseline else []),cwd=out)
    def regression():
        cpp.compile('filesystem-build',ROOT/'tests/runtime/native/test_php_filesystem.cpp',out/'filesystem')
        runner.run('filesystem-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/'filesystem'],cwd=out)
    run_tasks([Task('runtime',execute)]+([] if a.baseline else [Task('filesystem-regression',regression)]))
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'observations':(out/'logs/execute.stdout').read_text()},indent=2)+'\n')
