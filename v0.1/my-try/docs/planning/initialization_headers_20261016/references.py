from pathlib import Path
import sys
B=Path(__file__).resolve().parent;R=B.parent/'repo';sys.path.insert(0,str(R/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(B/'reference-logs',R) as runner:
 def emit(level):
  runner.run(f'emit-{level}',['php',R/'compiler/my-try/main.php','--s2s',B.parent/'native-ninja-accounting-20261016/accounting','--entry','main.phs','--optimization',str(level),'--output',B/'php-references'/f'policy{level}'],timeout=600,cli_reason='Independent PHP artifact parity')
 run_tasks([Task(f'emit-{i}',lambda i=i:emit(i)) for i in [0,2]])
