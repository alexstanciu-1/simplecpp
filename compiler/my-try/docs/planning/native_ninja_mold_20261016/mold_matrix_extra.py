import json, subprocess, time
from pathlib import Path
import benchmark_mold_support as b
B=b.BASE
files=[b.APP/'src/self_test.phs']+sorted((b.APP/'src').glob('check_*.phs'))[:7]
originals={p:p.read_text() for p in files}
def restore():
 subprocess.run(['git','-C',str(b.APP),'restore','--source=HEAD','--worktree','--',*[str(p.relative_to(b.APP)) for p in files]],check=True)
def edit(count):
 for i,p in enumerate(files[:count]):
  s=originals[p]
  if i==0:s=s.replace('accounting workload:','accounting edited 1:')
  else:
   assert s.count(' valid draft"')==1
   s=s.replace(' valid draft"',' valid edited draft"')
  assert s!=originals[p]
  p.write_text(s)
 time.sleep(1.05)
def measure(name,reuse):
 row=b.measure(name,reuse)
 calls=json.loads((b.RESULTS/name/'clang-calls.json').read_text())
 objects=[c for c in calls if c['stage'] in ('runtime','object','pch')]
 events=sorted([(c['start_ns'],1) for c in objects]+[(c['end_ns'],-1) for c in objects])
 active=0;peak=0
 for _,delta in events:active+=delta;peak=max(peak,active)
 row['peak_parallel_compiles']=peak
 row['compile_span_s']=(max(c['end_ns'] for c in objects)-min(c['start_ns'] for c in objects))/1e9 if objects else 0
 row['compile_sum_s']=sum(c['wall_s'] for c in objects)
 row['compile_max_s']=max([c['wall_s'] for c in objects] or [0])
 expected=93 if name=='full' else (0 if name=='unchanged' else (8 if name=='eight-body-j12' else 1))
 assert row['actions']['object']==expected,(name,row['actions'])
 assert row['actions']['pch']==(1 if name=='full' else 0)
 assert row['actions']['link']==(0 if name=='unchanged' else 1)
 p=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True,timeout=60)
 (b.RESULTS/name/'app-output.txt').write_text(p.stdout+p.stderr)
 assert p.returncode==0 and 'ACCOUNTING_512K_PASS' in p.stdout
 row['self_test_pass']=True
 (b.RESULTS/name/'result.json').write_text(json.dumps(row,indent=2))
 return row
def proof():
 rows=[]
 try:
  for trial in range(4,5):
   # Alternate order to limit a systematic warm-up/order bias.
   for linker in (['gnu','mold'] if trial%2 else ['mold','gnu']):
    restore();b.PARALLEL12=False
    label=f'{linker}-{trial}'
    b.BUILD=B/f'matrix-build-{label}';b.PUBLISHED=B/f'matrix-published-{label}'
    b.RESULTS=B/'mold-results'/label;b.RESULTS.mkdir(parents=True)
    b.WRAPPER=B/('measure-mold' if linker=='mold' else 'measure-clang')
    for name,reuse in [('full',False),('unchanged',True),('body-edit',True)]:
     if name=='body-edit':edit(1)
     row=measure(name,reuse);row.update(linker=linker,trial=trial,jobs=8);rows.append(row)
    if linker=='mold':
     restore();measure('reset-before-j12',True)
     b.PARALLEL12=True;edit(1)
     row=measure('one-body-j12',True);row.update(linker=linker,trial=trial,jobs=12);rows.append(row)
     restore();measure('reset-before-eight',True);edit(8)
     row=measure('eight-body-j12',True);row.update(linker=linker,trial=trial,jobs=12);rows.append(row)
    (B/'mold-results/summary-extra.json').write_text(json.dumps(rows,indent=2))
 finally:restore()
if __name__=='__main__':b.run_tasks([b.Task('mold-accounting-matrix',proof)])
