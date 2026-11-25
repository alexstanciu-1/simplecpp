"""Large-source partition execution and warm/edit stability in both output modes."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',required=True,type=Path);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True)
with CommandRunner(out/'logs',ROOT) as runner:
 def check(mode):
  folder=out/mode;folder.mkdir()
  runner.run(mode+'-publish',['php8.5',APP/'tests/implementation_partition.php',folder,mode],timeout=180)
  build=json.loads(runner.run(mode+'-build',['php8.5',APP/'tools/build_cpp.php',folder/'published',folder/'cache','-O2'],timeout=600))
  assert runner.run(mode+'-execute',[build['executable']])==b'71'
 run_tasks([Task(mode,lambda mode=mode:check(mode)) for mode in ['ordinary','concrete']])
(out/'result.json').write_text(json.dumps({'passed':True,'expected':'71'})+'\n')
