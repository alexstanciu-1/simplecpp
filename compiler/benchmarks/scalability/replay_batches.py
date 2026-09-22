"""Diagnostic only: compare one-file and multi-file Clang drivers at 20-way concurrency. Run from the repository workspace; see README.md."""

from pathlib import Path
import subprocess,tempfile,concurrent.futures,time,json,resource
root=Path(__file__).resolve().parents[2]; base=root/'build/scalability/dispatch_probe'; ir=base/'ir'
files=sorted(ir.glob('*.ll'));target=(ir/'target.txt').read_text(); assert len(files)==669
rows=[]
for batch_size in (1,8,32):
 for trial in range(1,4):
  with tempfile.TemporaryDirectory(prefix='scpp_batch_replay_') as t:
   d=Path(t); groups=[files[i:i+batch_size] for i in range(0,len(files),batch_size)]
   before=resource.getrusage(resource.RUSAGE_CHILDREN); start=time.perf_counter()
   def job(group):
    p=subprocess.run(['clang','--target='+target,'-O0','-x','ir','-c',*[str(f) for f in group]],cwd=d,capture_output=True,text=True,timeout=30)
    if p.returncode: raise RuntimeError(p.stderr)
   with concurrent.futures.ThreadPoolExecutor(max_workers=20) as pool:list(pool.map(job,groups))
   seconds=time.perf_counter()-start; after=resource.getrusage(resource.RUSAGE_CHILDREN)
   objects=[d/(f.stem+'.o') for f in files]; assert all(f.stat().st_size for f in objects)
   p=subprocess.run(['clang','--target='+target,*map(str,objects),'-o',str(d/'program')],capture_output=True,text=True)
   assert p.returncode==0,p.stderr
   status=subprocess.run([str(d/'program')]).returncode;assert status==42,status
   row=dict(batch_size=batch_size,trial=trial,objects=len(objects),invocations=len(groups),seconds=seconds,child_user=after.ru_utime-before.ru_utime,child_system=after.ru_stime-before.ru_stime,child_inblock=after.ru_inblock-before.ru_inblock,child_outblock=after.ru_oublock-before.ru_oublock,child_minor_faults=after.ru_minflt-before.ru_minflt,child_major_faults=after.ru_majflt-before.ru_majflt,exit_code=status)
   row['cpu_equivalents']=(row['child_user']+row['child_system'])/seconds
   rows.append(row);print(json.dumps(row),flush=True)
   (base/'replay.json').write_text(json.dumps({'method':'Isolated replay of the same 669 real cold LLVM files. Small Python coordinator, at most 20 concurrent Clang drivers, no per-invocation wrapper. 1/8/32 input files per invocation. Input preparation, final link and executable check outside object timer. No compiler AST serialized.','rows':rows},indent=2)+'\n')
