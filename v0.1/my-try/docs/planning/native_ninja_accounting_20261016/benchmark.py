import hashlib, importlib.util, json, os, re, shutil, subprocess, sys, time
from pathlib import Path
BASE=Path(__file__).resolve().parent
REPO=BASE.parent/'repo'
sys.path.insert(0,str(REPO/'compiler/my-try/tools'))
from test_runner import Task, run_tasks
APP=BASE/'accounting'
EXE=BASE/'phpp/.prism/build/main'
PUBLISHED=BASE/'published';BUILD=BASE/'app-build'
RESULTS=BASE/'measurements';RESULTS.mkdir(exist_ok=True)
FIXTURE=BASE.parent/'recovered/accounting-large-20261010/accounting_512k'
if not APP.exists():
 shutil.copytree(FIXTURE,APP)
 subprocess.run(['git','init',str(APP)],check=True,capture_output=True)
 subprocess.run(['git','-C',str(APP),'add','.'],check=True)
 subprocess.run(['git','-C',str(APP),'-c','user.name=Accounting benchmark','-c','user.email=benchmark@local','commit','-m','Preserved 515 KiB accounting fixture'],check=True,capture_output=True)

def tree(pid):
 pending=[pid];seen=set();rss=0;root_hwm=0
 while pending:
  current=pending.pop()
  if current in seen:continue
  seen.add(current)
  try:
   text=Path(f'/proc/{current}/status').read_text()
   match=re.search(r'^VmRSS:\s+(\d+)',text,re.M)
   if match:rss+=int(match[1])
   if current==pid:
    match=re.search(r'^VmHWM:\s+(\d+)',text,re.M)
    if match:root_hwm=int(match[1])
   for children in Path(f'/proc/{current}/task').glob('*/children'):
    pending.extend(map(int,children.read_text().split()))
  except (FileNotFoundError,ProcessLookupError):pass
 return rss,root_hwm

def measure(name,reuse):
 out=RESULTS/name;out.mkdir()
 marks=out/'marks';marks.mkdir()
 request=dict(marks=str(marks),catalogs=str(REPO/'runtime/metadata/modules'),source=str(APP),published=str(PUBLISHED),runtime=str(REPO/'runtime/include'),build=str(BUILD),clang=str(BASE/'measure-clang'),reuse=reuse)
 (out/'request.json').write_text(json.dumps(request))
 before=set((BUILD/'.measure').glob('*.json'))
 start_real=time.time_ns();start=time.monotonic_ns();peak=0;compiler_peak=0;samples=[]
 with (out/'stdout').open('w') as stdout,(out/'stderr').open('w') as stderr:
  proc=subprocess.Popen([str(EXE)],cwd=out,stdout=stdout,stderr=stderr)
  while proc.poll() is None:
   now=time.monotonic_ns();rss,hwm=tree(proc.pid)
   peak=max(peak,rss);compiler_peak=max(compiler_peak,hwm)
   samples.append([now,rss,hwm]);time.sleep(.01)
  code=proc.wait()
 end=time.monotonic_ns()
 (out/'memory-samples.json').write_text(json.dumps(samples))
 timings={p.name:p.stat().st_mtime_ns for p in marks.iterdir()}
 (out/'marks.json').write_text(json.dumps(timings,indent=2))
 row=dict(name=name,exit=code,wall_s=(end-start)/1e9,tree_peak_rss_kib=peak,compiler_peak_rss_kib=compiler_peak,start_ns=start,end_ns=end,marks=timings)
 if code:
  (out/'result.json').write_text(json.dumps(row,indent=2))
  raise RuntimeError(f'{name}: native driver exit {code}: '+(out/'stderr').read_text()[-4000:])
 calls=[json.loads(p.read_text()) for p in (BUILD/'.measure').glob('*.json') if p not in before and not p.name.endswith('.trace.json')]
 (out/'clang-calls.json').write_text(json.dumps(calls,indent=2))
 intervals={}
 ordered=[('01-start','02-setup','setup'),('02-setup','03-sync','sync_parse'),('03-sync','04-prepare','prepare'),('04-prepare','05-cpp-ready','emit_publish'),('05-cpp-ready','06-build-done','native_build')]
 for a,b,label in ordered:intervals[label]=(timings[b]-timings[a])/1e9
 intervals['request_to_cpp_ready']=(timings['05-cpp-ready']-start_real)/1e9
 row['request_to_ready_s']=(timings['06-build-done']-start_real)/1e9
 row['stages_s']=intervals;row['actions']={s:sum(c['stage']==s for c in calls) for s in ['pch','runtime','object','link']}
 row['clang_sum_wall_s']={s:sum(c['wall_s'] for c in calls if c['stage']==s) for s in row['actions']}
 row['clang_peak_rss_kib']={s:max([c['peak_rss_kib'] for c in calls if c['stage']==s] or [0]) for s in row['actions']}
 links=[c for c in calls if c['stage']=='link']
 row['request_to_link_complete_s']=(max(c['end_ns'] for c in links)-start)/1e9 if links else None
 row['last_clang_to_process_exit_s']=(end-max(c['end_ns'] for c in calls))/1e9 if calls else None
 # Clang wall intervals overlap under Ninja. Union gives time with >=1 active tool.
 merged=[]
 for a,b in sorted((c['start_ns'],c['end_ns']) for c in calls):
  if merged and a<=merged[-1][1]:merged[-1][1]=max(merged[-1][1],b)
  else:merged.append([a,b])
 row['clang_active_wall_s']=sum(b-a for a,b in merged)/1e9
 row['native_other_wall_s']=intervals['native_build']-row['clang_active_wall_s']
 manifest=json.loads((PUBLISHED/'.scpp-output.manifest').read_text())
 if name=='full':
  reference=BASE/'php-published'
  reference_manifest=json.loads((reference/'.scpp-output.manifest').read_text())
  a={r['path']:r['sha256'] for r in manifest['files']}
  b={r['path']:r['sha256'] for r in reference_manifest['files']}
  assert a==b, 'Native/PHP publication mismatch'
  row['php_artifact_parity']=True
 row['published_files']=len(manifest['files']);row['translation_units']=sum(f['kind']=='translation_unit' for f in manifest['files'])
 row['generated_bytes']=sum((PUBLISHED/f['path']).stat().st_size for f in manifest['files'])
 row['executable_bytes']=(BUILD/'app').stat().st_size
 (out/'result.json').write_text(json.dumps(row,indent=2));print(json.dumps(row),flush=True)
 return row

def main():
 target=APP/'src/self_test.phs';original=target.read_text()
 rows=[]
 def proof():
  try:
   for name,reuse in [('full',False),('unchanged',True),('body-edit',True)]:
    if name=='body-edit':
     assert original.count('accounting workload:')==1
     target.write_text(original.replace('accounting workload:','accounting edited 1:'))
     time.sleep(1.05) # Outside timing; avoid coarse source snapshot timestamps.
    rows.append(measure(name,reuse))
    p=subprocess.run([str(BUILD/'app')],capture_output=True,text=True,timeout=60)
    (RESULTS/name/'app-output.txt').write_text(p.stdout+p.stderr)
    assert p.returncode==0 and 'ACCOUNTING_512K_PASS' in p.stdout,(name,p.returncode,p.stdout,p.stderr)
   (RESULTS/'summary.json').write_text(json.dumps(rows,indent=2))
  finally:
   subprocess.run(['git','-C',str(APP),'restore','--source=HEAD','--worktree','--','src/self_test.phs'],check=True)
 run_tasks([Task('native-accounting-ninja',proof)])

if __name__=='__main__':main()
