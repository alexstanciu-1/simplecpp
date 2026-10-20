from pathlib import Path
import sys,json,time
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-edit-cycle')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
rows=list(map(json.loads,(OUT/'prepared-gnu/clang.jsonl').read_text().splitlines()))
original=[r['arguments'] for r in rows if r['phase']=='link'][-1]
i=original.index('--dependency-file');assert original[i-1]=='-Xlinker' and original[i+1]=='-Xlinker';del original[i-1:i+3]
linkers={'gnu':'/usr/bin/ld','mold':'/home/alexv/.local/bin/mold','lld':'/usr/bin/ld.lld'}
results=[]
with CommandRunner(OUT/'linker-logs',ROOT,jobs=12) as runner:
 for trial in range(3):
  for name in (list(linkers) if trial%2==0 else list(reversed(linkers))):
   args=['-fuse-ld='+linkers[name] if a.startswith('-fuse-ld=') else a for a in original]
   binary=OUT/f'link-{name}-{trial}.bin';args[args.index('-o')+1]=str(binary)
   def check():
    started=time.monotonic();runner.run(f'{name}-{trial}-link',['/usr/bin/clang++',*args]);elapsed=time.monotonic()-started
    assert b'CHECKS passed=998 failed=0' in runner.run(f'{name}-{trial}-run',[binary])
    return elapsed
   elapsed=run_tasks([Task('link',check)],report=False)[0].value
   results.append({'linker':name,'trial':trial,'seconds':elapsed});print(results[-1],flush=True)
(OUT/'linkers.json').write_text(json.dumps(results,indent=2)+'\n')
