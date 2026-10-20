"""Diagnostic copied-output experiment; never changes compiler or baseline artifacts."""
import json,sys,shutil,subprocess,time,hashlib
from pathlib import Path
B=Path('/home/alex-ai/accounting-edit-matrix-20261016/bench')
sys.path.insert(0,str(B.parent/'repo/compiler/my-try/tools'))
from test_runner import Task,run_tasks
O=Path(__file__).resolve().parent/'attempt-2'
O.mkdir()
P=B/'published-1'
C=O/'forward-only'
shutil.copytree(P,C)
target=Path('types/n'+b'AccountingCheck'.hex()+'.hpp')
(C/target).write_text('#pragma once\nstruct record_AccountingCheck;\n')
pchcall=next(c for c in json.loads((B/'edit-results/trial-1/full/clang-calls.json').read_text()) if c['stage']=='pch')
pa=pchcall['args']; clean=[];i=0
while i<len(pa):
 if pa[i]=='-MD':i+=1;continue
 if pa[i] in ['-MF','-MQ','-o']:i+=2;continue
 clean.append(pa[i]);i+=1
pcmd=['/usr/bin/clang++-18',*clean,'-o',str(O/'runtime.pch')]
subprocess.run(pcmd,check=True,capture_output=True)
(O/'pch-command.json').write_text(json.dumps(pcmd,indent=2))
calls=json.loads((B/'edit-results/trial-1/common-declaration-edit/clang-calls.json').read_text())
def label(c):return bytes.fromhex(Path(c['args'][c['args'].index('-c')+1]).stem[1:]).decode()
def args(c,copy):
 a=c['args'];r=[];i=0
 while i<len(a):
  if a[i] in ['-MD']:i+=1;continue
  if a[i] in ['-MF','-MQ','-o']:i+=2;continue
  part=a[i].replace(str(B/'build-1/runtime.pch'),str(O/'runtime.pch'))
  r.append(part.replace(str(P),str(C)) if copy else part);i+=1
 return r
(O/'objects').mkdir();(O/'logs').mkdir()
def probe(c):
 n=label(c);stem=Path(n).stem
 cmd=['/usr/bin/clang++-18',*args(c,True),'-o',str(O/'objects'/f'{stem}.o')]
 p=subprocess.run(cmd,capture_output=True,text=True)
 (O/'logs'/f'{stem}.stderr').write_text(p.stderr)
 return dict(unit=n,exit=p.returncode,command=cmd,errors=[s for s in p.stderr.splitlines() if 'error:' in s])
rows=run_tasks([Task(label(c),lambda c=c:probe(c)) for c in calls if c['stage']=='object'],jobs=8)
(O/'probe-results.json').write_text(json.dumps([r.value for r in rows],indent=2))
# Diagnostic profiles run serially, preserving original generated code and flags.
for n in ['src/workflow_checks.phs','src/closing_checks.phs','src/customer_profile.phs']:
 c=next(c for c in calls if c['stage']=='object' and label(c)==n)
 stem=Path(n).stem;out=O/'objects'/f'profile-{stem}.o'
 cmd=['/usr/bin/clang++-18',*args(c,False),'-ftime-trace','-o',str(out)]
 def profile():
  p=subprocess.run(cmd,capture_output=True,text=True);(O/'logs'/f'profile-{stem}.stderr').write_text(p.stderr)
  assert p.returncode==0,p.stderr
  return dict(unit=n,command=cmd)
 r=run_tasks([Task('profile-'+stem,profile)],jobs=1)
 (O/f'profile-{stem}-command.json').write_text(json.dumps(r[0].value,indent=2))
print('RESULT',sum(r.value['exit']==0 for r in rows),'forward-only successes of',len(rows),flush=True)
