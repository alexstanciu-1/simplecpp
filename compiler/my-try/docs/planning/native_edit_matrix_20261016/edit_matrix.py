"""Experimental native lowering/Ninja edit matrix; no compiler implementation changes."""
import hashlib,json,re,subprocess,time
from pathlib import Path
import benchmark_support as b
B=b.BASE
RUNTIME=b.REPO/'runtime'
CASE_NAMES=['multiple-bodies','signature-callers','class-layout','common-declaration','pch-header','provider-implementation']

def git(root,*args):
 return subprocess.check_output(['git','-C',str(root),*args],text=True)
def restore():
 git(b.APP,'restore','--source=HEAD','--worktree','--','src')
 git(RUNTIME,'restore','--source=HEAD','--worktree','--','include')
def replace(path,old,new):
 s=path.read_text();assert s.count(old)==1,(path,old);path.write_text(s.replace(old,new))
def edit(case):
 p=b.APP/'src/customer_profile.phs'
 if case=='multiple-bodies':
  replace(p,'return $this->credit_limit - $this->exposure;','return $this->credit_limit - $this->exposure + 0;')
  replace(p,'$snapshot->credit_limit = $this->credit_limit;','$snapshot->credit_limit = $this->credit_limit + 0;')
 elif case=='signature-callers':
  replace(p,'function available_credit(): int','function available_credit(int $reserved): int')
  replace(p,'return $this->credit_limit - $this->exposure;','return $this->credit_limit - $this->exposure - $reserved;')
  hits=0
  for f in (b.APP/'src').glob('*.phs'):
   s=f.read_text();n=s.count('->available_credit()')
   if n:f.write_text(s.replace('->available_credit()','->available_credit(0)'));hits+=n
  assert hits==4,hits
 elif case=='class-layout':
  replace(p,'class CustomerProfile {','class CustomerProfile {\n    public int $integration_marker = 0;')
 elif case=='common-declaration':
  replace(b.APP/'src/core.phs','class AccountingCheck {','class AccountingCheck {\n    public int $integration_marker = 0;')
 elif case=='pch-header':
  replace(RUNTIME/'include/scpp/int_t.hpp','return value_;','return static_cast<Rep>(value_ + 0);')
 elif case=='provider-implementation':
  replace(RUNTIME/'include/scpp/support/var_dump.cpp','for (int i = 0; i < n; ++i)','for (int i = 0; i < n; i += 1)')
 else:raise AssertionError(case)

def snapshot():
 p=b.PUBLISHED/'.scpp-output.manifest'
 if not p.exists():return {}
 return {str((b.PUBLISHED/r['path']).resolve()):r['sha256'] for r in json.loads(p.read_text())['files']}
def dependencies():
 text=subprocess.check_output(['/usr/bin/ninja','-C',str(b.BUILD),'-t','deps'],text=True)
 result={};current=None
 for line in text.splitlines():
  if ': #deps ' in line:
   current=line.split(': #deps ',1)[0];result[current]=set()
  elif line.startswith('    ') and current is not None:
   p=Path(line[4:]);result[current].add(str((p if p.is_absolute() else b.BUILD/p).resolve()))
 return result

def union(calls):
 merged=[]
 for a,z in sorted((c['start_ns'],c['end_ns']) for c in calls):
  if merged and a<=merged[-1][1]:merged[-1][1]=max(merged[-1][1],z)
  else:merged.append([a,z])
 return sum(z-a for a,z in merged)/1e9

def label(path):
 p=Path(path)
 if p.is_relative_to(RUNTIME/'include'):return 'runtime/'+str(p.relative_to(RUNTIME/'include'))
 if p.name=='entry.cpp':return 'entry.cpp'
 if p.stem.startswith('n'):
  try:return bytes.fromhex(p.stem[1:]).decode()
  except ValueError:pass
 return str(p.relative_to(b.PUBLISHED))

def measure(name,case='',phase='',runtime_changed=None,cold=False):
 before=snapshot();deps={} if cold else dependencies()
 r=b.measure(name,not cold)
 out=b.RESULTS/name
 after=snapshot();changed={p for p in before.keys()|after.keys() if before.get(p)!=after.get(p)}
 if runtime_changed:changed.add(str(runtime_changed.resolve()))
 calls=json.loads((out/'clang-calls.json').read_text())
 compiles=[c for c in calls if c['stage']!='link']
 obj=[c for c in calls if c['stage'] in ['object','runtime']]
 actual={c['args'][c['args'].index('-o')+1] for c in compiles}
 pch=str(b.BUILD/'runtime.pch')
 expected={target for target,inputs in deps.items() if inputs & changed}
 if pch in expected:expected.update(t for t in deps if t.startswith('objects/generated-'))
 if not cold:assert actual==expected,(name,'unexpected',actual-expected,'missing',expected-actual)
 assert r['actions']['link']==(1 if cold or expected else 0),(name,r['actions'])
 if phase=='unchanged' or phase=='revert-unchanged':assert r['actions']==dict(pch=0,runtime=0,object=0,link=0)
 if case in ['multiple-bodies','signature-callers','class-layout','common-declaration']:
  assert r['actions']['pch']==0 and r['actions']['runtime']==0
 if case=='multiple-bodies' and phase in ['edit','revert']:assert r['actions']['object']==1
 if case=='pch-header' and phase in ['edit','revert']:assert r['actions']['pch']==1 and r['actions']['object']==93
 if case=='provider-implementation' and phase in ['edit','revert']:assert r['actions']==dict(pch=0,runtime=1,object=0,link=1)
 if cold:assert r['actions']==dict(pch=1,runtime=3,object=93,link=1)
 events=sorted([(c['start_ns'],1) for c in compiles]+[(c['end_ns'],-1) for c in compiles]);active=0;peak=0
 for _,delta in events:active+=delta;peak=max(peak,active)
 pch_calls=[c for c in calls if c['stage']=='pch'];generated=[c for c in calls if c['stage']=='object']
 if pch_calls and generated:assert min(c['start_ns'] for c in generated)>=max(c['end_ns'] for c in pch_calls),'PCH dependency order broken'
 rebuilt=sorted(label(c['args'][c['args'].index('-c')+1]) for c in obj)
 all_objects=sorted(t for t in dependencies() if t.startswith('objects/'))
 r.update(case=case,phase=phase,rebuilt_objects=rebuilt,reused_objects_count=len(all_objects)-len(obj),expected_compile_outputs=sorted(expected),actual_compile_outputs=sorted(actual),changed_inputs=sorted(changed),peak_parallel_compiles=peak,pch_wall_s=union(pch_calls),object_active_wall_s=union(obj),compile_active_wall_s=union(compiles),compile_sum_wall_s=sum(c['wall_s'] for c in compiles),dependency_set_verified=not cold)
 if pch_calls:
  a=min(c['start_ns'] for c in pch_calls);z=max(c['end_ns'] for c in pch_calls)
  r['provider_overlap_pch_sum_s']=sum(max(0,min(z,c['end_ns'])-max(a,c['start_ns'])) for c in calls if c['stage']=='runtime')/1e9
 run=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True,timeout=60)
 (out/'app-output.txt').write_text(run.stdout+run.stderr)
 assert run.returncode==0 and 'ACCOUNTING_512K_PASS' in run.stdout,(name,run.stdout,run.stderr)
 r['self_test_pass']=True
 (out/'result.json').write_text(json.dumps(r,indent=2))
 return r

def proof():
 rows=[]
 b.WRAPPER=B/'measure-clang';b.LINKER=B/'mold';b.LINKER_THREADS=8;b.PARALLEL12=False
 root=B/'edit-results';root.mkdir()
 try:
  for trial in range(1,4):
   restore()
   b.PUBLISHED=B/f'published-{trial}';b.BUILD=B/f'build-{trial}';b.RESULTS=root/f'trial-{trial}';b.RESULTS.mkdir()
   for name,cold in [('full',True),('baseline-unchanged',False)]:
    r=measure(name,phase='baseline',cold=cold);r['trial']=trial;rows.append(r)
   (b.RESULTS/'baseline-deps.txt').write_text(subprocess.check_output(['/usr/bin/ninja','-C',str(b.BUILD),'-t','deps'],text=True))
   for case in CASE_NAMES:
    edit(case);time.sleep(1.05)
    (b.RESULTS/(case+'.patch')).write_text(git(b.APP,'diff','--','src')+git(RUNTIME,'diff','--','include'))
    rp=RUNTIME/'include/scpp/int_t.hpp' if case=='pch-header' else (RUNTIME/'include/scpp/support/var_dump.cpp' if case=='provider-implementation' else None)
    for phase in ['edit','unchanged','revert','revert-unchanged']:
     if phase=='revert':restore();time.sleep(1.05)
     r=measure(case+'-'+phase,case,phase,rp if phase in ['edit','revert'] else None);r['trial']=trial;rows.append(r)
     (root/'summary.json').write_text(json.dumps(rows,indent=2))
 finally:restore()
if __name__=='__main__':b.run_tasks([b.Task('native-complex-edit-matrix',proof)])
