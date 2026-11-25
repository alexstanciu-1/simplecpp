import sys,json
from pathlib import Path
sys.dont_write_bytecode=True
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path(__file__).parent
sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(root/'cli-logs',repo,jobs=12,php_executor='cli') as runner:
 def check(id):
  runner.run(id,['php',repo/'compiler/my-try/main.php','--s2s',root/'results'/(id+'-source'),'--entry','main.phs'],expected=1)
  return (root/'cli-logs'/(id+'.stderr')).read_text()
 rows=run_tasks([Task(i,lambda i=i:check(i)) for i in ['NAME-04','REF-03']],jobs=12,report=False)
 (root/'cli-results.json').write_text(json.dumps({r.name:r.value for r in rows},indent=2))
 print(json.dumps({r.name:r.value for r in rows}))
