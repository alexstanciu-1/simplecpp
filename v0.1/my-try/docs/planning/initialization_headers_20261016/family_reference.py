import ast,sys
from pathlib import Path
B=Path(__file__).resolve().parent;R=B.parent/'repo';sys.path.insert(0,str(R/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
module=ast.parse((B/'family_checks.py').read_text());sources=ast.literal_eval(next(n.value for n in module.body if isinstance(n,ast.Assign) and any(isinstance(t,ast.Name) and t.id=='sources' for t in n.targets)))
with CommandRunner(B/'family-reference-logs',R) as runner:
 def emit(name,source):
  src=B/'family-reference-source'/name;src.mkdir(parents=True,exist_ok=True);(src/'main.phs').write_text(source)
  out=B/'family-references'/name;out.mkdir(parents=True,exist_ok=True)
  runner.run(name,['php',R/'compiler/my-try/main.php','--s2s',src,'--entry','main.phs','--optimization','0','--output',out],cli_reason='Validate native witness sources before remote runtime execution')
 run_tasks([Task(n,lambda n=n,s=s:emit(n,s)) for n,s in sources.items()])
