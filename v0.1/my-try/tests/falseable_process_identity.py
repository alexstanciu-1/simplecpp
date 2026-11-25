"""S04 unchanged strict shell_exec fixture and deterministic sentinel/string controls."""
import argparse,hashlib,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',required=True,type=Path);args=parser.parse_args()
out=args.results.resolve();out.mkdir(parents=True,exist_ok=False)
source=ROOT/'tests/php/process/strict_level_01/strict_process_001_shell_exec_contract.phs'
metadata=source.with_suffix('.test-info.json');expect=json.loads(metadata.read_text())['expect']['run']
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 runner.run('prepare',['php',APP/'tests/falseable_process_identity.php',out])
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof(name):
  cpp.compile(name+'-build',out/(name+'.cpp'),out/name);runner.run(name+'-execute',[out/name])
  if name=='original':
   assert (out/'logs/original-execute.stdout').read_text()==expect['stdout']
   assert (out/'logs/original-execute.stderr').read_text()==expect['stderr']
 run_tasks([Task(n,lambda n=n:proof(n)) for n in ['original','controls']])
(out/'summary.json').write_text(json.dumps({'passed':True,'source':str(source.relative_to(ROOT)),'source_sha256':hashlib.sha256(source.read_bytes()).hexdigest(),'metadata_sha256':hashlib.sha256(metadata.read_bytes()).hexdigest(),'source_changed':False,'expected_stdout':expect['stdout'],'control_forms':4,'full_matrix_run':False},indent=2)+'\n')
