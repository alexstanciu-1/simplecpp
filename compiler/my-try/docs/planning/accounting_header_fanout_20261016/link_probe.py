"""Link the 69 forward-only objects with unchanged baseline objects and run witnesses."""
import json,sys,subprocess,hashlib
from pathlib import Path
B=Path('/home/alex-ai/accounting-edit-matrix-20261016/bench')
O=Path(__file__).resolve().parent/'attempt-2'
sys.path.insert(0,str(B.parent/'repo/compiler/my-try/tools'))
from test_runner import Task,run_tasks,CommandRunner
rows=json.loads((O/'probe-results.json').read_text())
calls=json.loads((B/'edit-results/trial-1/common-declaration-edit/clang-calls.json').read_text())
replace={}
for c in calls:
 if c['stage']!='object':continue
 n=bytes.fromhex(Path(c['args'][c['args'].index('-c')+1]).stem[1:]).decode()
 if next(r['exit'] for r in rows if r['unit']==n)==0:
  replace[c['args'][c['args'].index('-o')+1]]=str(O/'objects'/(Path(n).stem+'.o'))
lc=next(c for c in calls if c['stage']=='link')['args'];a=[];i=0
while i<len(lc):
 if lc[i]=='-Xlinker' and i+1<len(lc) and lc[i+1]=='--dependency-file':i+=4;continue
 if lc[i]=='-o':i+=2;continue
 a.append(replace.get(lc[i],lc[i]));i+=1
cmd=['/usr/bin/clang++-18',*a,'-o',str(O/'app')]
def proof():
 with CommandRunner(O/'link-proof',B/'build-1') as r:
  r.run('link',cmd)
  out=r.run('self-test',[str(O/'app')])
  assert b'ACCOUNTING_512K_PASS' in out
  return {'replaced_objects':len(replace),'self_test':out.decode()}
result=run_tasks([Task('link-and-execute-forward-only',proof)],jobs=1)[0].value
result['published_hashes']={str(p.relative_to(B/'published-1')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (B/'published-1').rglob('*') if p.is_file()}
(O/'link-proof.json').write_text(json.dumps(result,indent=2))
