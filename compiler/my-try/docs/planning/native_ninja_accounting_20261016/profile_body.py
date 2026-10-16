"""Supplemental isolated replay; these trace times are not mixed into normal build totals."""
from pathlib import Path
import json,sys
BASE=Path(__file__).resolve().parent;REPO=BASE.parent/'repo'
sys.path.insert(0,str(REPO/'compiler/my-try/tools'))
sys.path.insert(0,str(BASE.parent/'recovered/accounting-scans-20261010/lab/harness'))
from test_runner import CommandRunner,Task,run_tasks
from clang_timings import summarize_trace
out=BASE/'body-trace';out.mkdir()
calls=json.loads((BASE/'measurements/body-edit/clang-calls.json').read_text())
objects=[c for c in calls if c['stage']=='object'];assert len(objects)==1
args=list(objects[0]['args'])
for flag,value in [('-o',out/'body.o'),('-MF',out/'body.d'),('-MQ',out/'body.o')]:args[args.index(flag)+1]=str(value)
args.append('-ftime-trace='+str(out/'body.json'))
with CommandRunner(out/'logs',BASE/'app-build',jobs=12) as runner:
 def proof():
  runner.run('body-trace',['/usr/bin/time','-v','-o',out/'resource.txt','/usr/bin/clang++-18',*args],timeout=180)
  (out/'summary.json').write_text(json.dumps(summarize_trace(out/'body.json'),indent=2))
 run_tasks([Task('body-frontend-backend-replay',proof)])
