from pathlib import Path
import sys,json,time,statistics,os
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-parallel');SOURCE=Path('/tmp/accounting-header-fix')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
rows=[json.loads(s) for s in (SOURCE/'timed_clang.jsonl').read_text().splitlines()]
compiles=[r for r in rows if r['phase']=='object_compile' and not any('/specializations/layout' in x for x in r['arguments'])]
link=[r for r in rows if r['phase']=='link' and not any('/specializations/layout' in x for x in r['arguments'])][-1]['arguments']
objects=[x for x in link if x.endswith('.o')];assert len(objects)==len(compiles)
app=[(i,r['arguments']) for i,r in enumerate(compiles) if r['arguments'][r['arguments'].index('-c')+1].startswith(str(SOURCE/'prepared')) and '/specializations/' not in r['arguments'][r['arguments'].index('-c')+1]]
assert len(app)==16
results={'logical_cpus':os.cpu_count(),'units':16,'samples':{'1':[],'12':[]}}
with CommandRunner(OUT/'logs',ROOT,jobs=12) as runner:
 for trial in range(3):
  for jobs in ([1,12] if trial%2==0 else [12,1]):
   replacements={};tasks=[]
   for i,original in app:
    args=original.copy();obj=str(OUT/f'j{jobs}-{i}.o');args[args.index('-o')+1]=obj;replacements[objects[i]]=obj
    name=f'j{jobs}-r{trial}-unit{i}'
    tasks.append(Task(name,lambda args=args,name=name:runner.run(name,['/usr/bin/env','-i','PATH=/usr/bin:/bin','LC_ALL=C','TZ=UTC','SOURCE_DATE_EPOCH=0','/usr/bin/clang++',*args],timeout=120)))
   started=time.monotonic();run_tasks(tasks,jobs=jobs,report=False);compile_seconds=time.monotonic()-started
   args=[replacements.get(x,x) for x in link];binary=str(OUT/f'j{jobs}.bin');args[args.index('-o')+1]=binary
   def link_and_check():
    started=time.monotonic();runner.run(f'j{jobs}-r{trial}-link',['/usr/bin/clang++',*args]);seconds=time.monotonic()-started
    assert b'CHECKS passed=998 failed=0' in runner.run(f'j{jobs}-r{trial}-run',[binary])
    return seconds
   link_seconds=run_tasks([Task('link-and-check',link_and_check)],report=False)[0].value
   sample={'compilation_wall_seconds':compile_seconds,'link_seconds':link_seconds,'total_seconds':compile_seconds+link_seconds}
   results['samples'][str(jobs)].append(sample);print(jobs,trial,sample,flush=True)
   (OUT/'summary.json').write_text(json.dumps(results,indent=2)+'\n')
for jobs,samples in results['samples'].items():
 print('MEDIAN',jobs,{key:statistics.median(row[key] for row in samples) for key in samples[0]},flush=True)
