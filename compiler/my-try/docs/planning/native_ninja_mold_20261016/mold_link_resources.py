import json,os,time,subprocess
from pathlib import Path
import benchmark_mold_support as b
B=b.BASE

def proof():
 calls=json.loads((B/'mold-results/mold-3/body-edit/clang-calls.json').read_text())
 args=next(c['actual_args'] for c in calls if c['stage']=='link')[:]
 out=B/'mold-link-resources';out.mkdir()
 args[args.index('-o')+1]=str(out/'app')
 args[args.index('--dependency-file')+2]=str(out/'app.d')
 args.append('-Wl,--no-fork')
 rows=[]
 for trial in range(3):
  start=time.monotonic_ns();pid=os.fork()
  if pid==0:
   os.chdir(B/'matrix-build-mold-3');os.execv('/usr/bin/clang++-18',['/usr/bin/clang++-18',*args])
  _,status,usage=os.wait4(pid,0)
  assert os.waitstatus_to_exitcode(status)==0
  rows.append(dict(wall_s=(time.monotonic_ns()-start)/1e9,user_s=usage.ru_utime,system_s=usage.ru_stime,peak_rss_kib=usage.ru_maxrss))
 p=subprocess.run([str(out/'app')],capture_output=True,text=True)
 assert p.returncode==0 and 'ACCOUNTING_512K_PASS' in p.stdout
 (out/'result.json').write_text(json.dumps(dict(mode='supplemental no-fork link resource replay; excluded from primary timings',args=args,trials=rows,self_test_pass=True),indent=2))
if __name__=='__main__':b.run_tasks([b.Task('mold-no-fork-resources',proof)])
