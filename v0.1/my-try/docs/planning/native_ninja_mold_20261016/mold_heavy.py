import json, subprocess, time
from pathlib import Path
import benchmark_mold_support as b
B=b.BASE
# Eight comparable class implementation units; change only a diagnostic body literal.
names=['allocation_driver','asset_revision','cash_forecast_item','collection_case','customer_credit','customer_profile','department_budget','employee_advance']
files=[b.APP/'src'/f'{n}.phs' for n in names]
originals={p:subprocess.check_output(['git','-C',str(b.APP),'show','HEAD:'+str(p.relative_to(b.APP))],text=True) for p in files}
needle='id must contain 1..40 printable bytes'
for p,s in originals.items():assert s.count(needle)==1,p
def restore():
 subprocess.run(['git','-C',str(b.APP),'restore','--source=HEAD','--worktree','--','src'],check=True)
def edit(count):
 for p in files[:count]:p.write_text(originals[p].replace(needle,needle+' (edited)'))
 time.sleep(1.05)
def measure(name,expected):
 row=b.measure(name,True)
 calls=json.loads((b.RESULTS/name/'clang-calls.json').read_text());obj=[c for c in calls if c['stage']=='object']
 assert row['actions']==dict(pch=0,runtime=0,object=expected,link=1),row['actions']
 events=sorted([(c['start_ns'],1) for c in obj]+[(c['end_ns'],-1) for c in obj]);active=0;peak=0
 for _,d in events:active+=d;peak=max(peak,active)
 row.update(peak_parallel_compiles=peak,compile_span_s=(max(c['end_ns'] for c in obj)-min(c['start_ns'] for c in obj))/1e9,compile_sum_s=sum(c['wall_s'] for c in obj),compile_max_s=max(c['wall_s'] for c in obj))
 p=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True,timeout=60)
 (b.RESULTS/name/'app-output.txt').write_text(p.stdout+p.stderr)
 assert p.returncode==0 and 'ACCOUNTING_512K_PASS' in p.stdout
 row['self_test_pass']=True;(b.RESULTS/name/'result.json').write_text(json.dumps(row,indent=2))
 return row
def proof():
 b.BUILD=B/'matrix-build-mold-3';b.PUBLISHED=B/'matrix-published-mold-3';b.WRAPPER=B/'measure-mold';b.PARALLEL12=True
 b.RESULTS=B/'mold-heavy-results';b.RESULTS.mkdir()
 rows=[]
 try:
  restore();measure('restore-small-batch',8)
  for trial in range(1,4):
   edit(1);r=measure(f'one-{trial}',1);r['trial']=trial;rows.append(r)
   restore();measure(f'restore-one-{trial}',1)
   edit(8);r=measure(f'eight-{trial}',8);r['trial']=trial;rows.append(r)
   restore();measure(f'restore-eight-{trial}',8)
  (b.RESULTS/'summary.json').write_text(json.dumps(rows,indent=2))
 finally:restore()
if __name__=='__main__':b.run_tasks([b.Task('mold-business-class-scaling',proof)])
