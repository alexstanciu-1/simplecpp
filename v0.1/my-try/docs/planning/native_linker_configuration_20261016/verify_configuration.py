import json,os,shutil,subprocess
from pathlib import Path
import benchmark_mold_support as b
B=b.BASE

def proof():
 b.BUILD=B/'matrix-build-mold-3';b.PUBLISHED=B/'matrix-published-mold-3';b.WRAPPER=B/'measure-clang';b.PARALLEL12=False
 b.RESULTS=B/'configuration-results';b.RESULTS.mkdir()
 rows=[]
 def run(name,links,objects=0,fail=False):
  before=set((b.BUILD/'.measure').glob('*.json'))
  if fail:
   try:b.measure(name,True)
   except RuntimeError:pass
   else:raise AssertionError('Failed linker unexpectedly succeeded')
   calls=[json.loads(p.read_text()) for p in (b.BUILD/'.measure').glob('*.json') if p not in before]
   counts={s:sum(c['stage']==s for c in calls) for s in ['pch','runtime','object','link']}
   assert counts==dict(pch=0,runtime=0,object=0,link=1),counts
   assert (b.BUILD/'.scpp-ninja.status').read_text()=='pending\n'
   rows.append(dict(name=name,expected_failure=True,actions=counts))
  else:
   r=b.measure(name,True);assert r['actions']==dict(pch=0,runtime=0,object=objects,link=links),r['actions']
   calls=json.loads((b.RESULTS/name/'clang-calls.json').read_text())
   for c in calls:
    assert c['args']==c['actual_args'],'Timing wrapper must not substitute flags'
    if c['stage']=='link':
     assert '-fuse-ld='+str(b.LINKER) in c['args']
     assert ('--threads='+str(b.LINKER_THREADS) in c['args'])==(b.LINKER_THREADS>0)
   app=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True)
   assert app.returncode==0 and 'ACCOUNTING_512K_PASS' in app.stdout
   (b.RESULTS/name/'app-output.txt').write_text(app.stdout)
   rows.append(r)
  (b.RESULTS/'summary.json').write_text(json.dumps(rows,indent=2))
 # The heavy benchmark leaves this publication and cache compiled at baseline.
 run('baseline-warm',0)
 selected=B/'selected-mold';shutil.copy2(B/'mold',selected);b.LINKER=selected
 run('select-copy',1);run('copy-warm',0)
 b.LINKER_THREADS=2;run('threads-two',1)
 b.LINKER_THREADS=8;run('threads-eight',1)
 stamp=selected.stat();selected.write_bytes(selected.read_bytes()+b'\0');os.utime(selected,ns=(stamp.st_atime_ns,stamp.st_mtime_ns))
 run('replace-preserved-mtime',1);run('replacement-warm',0)
 alternate=B/'alternate-mold';shutil.copy2(selected,alternate);selected.unlink();selected.symlink_to(alternate)
 run('symlink-retarget-same-bytes',1)
 selected.unlink();selected.write_text('#!/bin/sh\nexit 37\n');selected.chmod(0o755)
 run('failed-link',1,fail=True)
 shutil.copy2(B/'mold',selected);run('recover',1)
 b.LINKER=Path('/usr/bin/ld');b.LINKER_THREADS=0;run('gnu-revert',1);run('gnu-warm',0)
 b.LINKER=B/'mold';b.LINKER_THREADS=8;run('mold-revert',1);run('mold-final-warm',0)
if __name__=='__main__':b.run_tasks([b.Task('native-linker-configuration',proof)])
