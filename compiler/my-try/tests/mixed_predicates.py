"""S07 unchanged strict fixture and exact-mixed observation proofs."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True);p.add_argument('--baseline',action='store_true');a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
expected=b'not-bool\nnot-int\nnot-float\nbool\nint\nfloat\n'
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 run_tasks([Task('prepare',lambda:runner.run('prepare',['php',APP/'tests/mixed_predicates.php',out]+(['--baseline'] if a.baseline else [])))])
 if not a.baseline:
  cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
  cpp.flags.extend(['-fsanitize=address','-fno-omit-frame-pointer'])
  def execute(name):
   cpp.compile(name+'-build',out/(name+'.cpp'),out/name)
   actual=runner.run(name+'-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/name])
   if name=='original':assert actual==expected,repr(actual)
   assert (out/'logs'/(name+'-execute.stderr')).read_bytes()==b''
  run_tasks([Task(n,lambda n=n:execute(n)) for n in ['original','behavior']])
checks=json.loads((out/('baseline.json' if a.baseline else 'checks.json')).read_text())
(out/'summary.json').write_text(json.dumps({'passed':True,'baseline':a.baseline,'checks':checks,'expected_stdout':expected.decode(),'address_sanitizer':not a.baseline,'runtime_changes':False,'full_matrix_run':False},indent=2)+'\n')
