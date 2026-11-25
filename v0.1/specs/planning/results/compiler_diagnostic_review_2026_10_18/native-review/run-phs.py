import sys,json
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
root=Path(__file__).parent;out=root/'phs';out.mkdir()
with CommandRunner(out/'logs',repo,jobs=12) as runner:
 def proof():
  r=json.loads(runner.run('sequence',['php',root/'phs-sequence.php',out],timeout=300));runner.run('execute-retained',[r['missed_invalid_edit']['build']['executable']],expected=1)
  print(json.dumps(r,indent=2))
 run_tasks([Task('phs',proof)],jobs=12)
