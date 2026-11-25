"""Focused diagnostic delivery and shared-location regression checks."""
import argparse,sys,json
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser();p.add_argument('--results',type=Path,required=True);args=p.parse_args()
out=args.results.resolve();out.mkdir(parents=True)
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 def portable():
  code="require "+repr(str(APP/'boot.php'))+";require "+repr(str(APP/'tests/reference_advisory_proof.php'))+";echo scpp\\compiler\\Reference_Advisory_Proof::run();"
  assert b'Reference advisory parity passed' in runner.run('portable',['php','-r',code])
 def delivery():
  folder=out/'delivery';folder.mkdir();(folder/'main.phs').write_text('function &identity(int &$v):int{return $v;}\necho 42;')
  stdout=runner.run('cli',['php',APP/'main.php','--s2s',folder,'--entry','main.phs','--output',out/'published'])
  stderr=(out/'logs/cli.stderr').read_text();assert ':1:1: warning [REF001]' in stderr
  assert b'REF001' not in stdout
  build=json.loads(runner.run('build',['php',APP/'tools/build_cpp.php',out/'published',out/'cache'],timeout=300))
  assert runner.run('execute',[build['executable']])==b'42'
 run_tasks([Task('portable',portable),Task('delivery',delivery),
  Task('source-selection',lambda:runner.run('source-selection',['php',APP/'tests/project_source_selection.php'])),
  Task('module-sync',lambda:runner.run('module-sync',['php',APP/'tests/module_sync.php'])),
  Task('dbg-locations',lambda:runner.run('dbg-locations',['php',APP/'tests/scalar_debug_recovery.php',out/'dbg-source']))])
