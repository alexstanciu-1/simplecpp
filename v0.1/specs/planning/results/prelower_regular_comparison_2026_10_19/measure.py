from pathlib import Path
import sys,json,time,statistics
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-regular-comparison')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
inputs={
 'ordinary':(OUT/'timed_clang.jsonl',OUT/'ordinary'),
 'prepared':(Path('/tmp/accounting-template-free/timed_clang.jsonl'),Path('/tmp/accounting-concrete-handles/prepared')),
}
plans={}
for mode,(record,published) in inputs.items():
 rows=list(map(json.loads,record.read_text().splitlines()))
 compiles={}
 for row in rows:
  if row['phase']!='object_compile':continue
  args=row['arguments'];src=Path(args[args.index('-c')+1])
  if src.is_relative_to(published/'units') or src==published/'entry.cpp':compiles[src]=args
 link=[row['arguments'] for row in rows if row['phase']=='link' and not any('/specializations/layout' in x for x in row['arguments'])][-1]
 assert len(compiles)==16
 assert all(('-include-pch' in args)==(mode=='ordinary') for args in compiles.values())
 plans[mode]=(compiles,link)
results={'optimization':'O0','native_debug':False,'units':16,'ordinary_pch':True,'prepared_pch':False,'samples':[]}
with CommandRunner(OUT/'measurement-logs',ROOT,jobs=12) as runner:
 for trial in range(3):
  order=[('ordinary',1),('prepared',1),('ordinary',12),('prepared',12)]
  if trial%2:order.reverse()
  for mode,jobs in order:
   compiles,link=plans[mode];replacements={};tasks=[];units=[]
   for index,(src,original) in enumerate(compiles.items()):
    args=original.copy();obj=str(OUT/f'{mode}-j{jobs}-{index}.o');replacements[args[args.index('-o')+1]]=obj;args[args.index('-o')+1]=obj
    name=f'{mode}-j{jobs}-r{trial}-u{index}'
    label=bytes.fromhex(src.stem[1:]).decode() if src.stem.startswith('n') else src.name
    def unit(args=args,name=name,label=label):
     start=time.monotonic()
     runner.run(name,['/usr/bin/env','-i','PATH=/usr/bin:/bin','LC_ALL=C','TZ=UTC','SOURCE_DATE_EPOCH=0','/usr/bin/clang++',*args],timeout=120)
     return {'source':label,'seconds':time.monotonic()-start}
    tasks.append(Task(name,unit))
   start=time.monotonic();outcomes=run_tasks(tasks,jobs=jobs,report=False);elapsed=time.monotonic()-start
   sample={'mode':mode,'jobs':jobs,'trial':trial,'compilation_wall_seconds':elapsed,'units':[o.value for o in outcomes]}
   args=[replacements.get(x,x) for x in link];binary=str(OUT/f'{mode}-j{jobs}.bin');args[args.index('-o')+1]=binary
   def validate():
    runner.run(f'{mode}-j{jobs}-r{trial}-link',['/usr/bin/clang++',*args])
    output=runner.run(f'{mode}-j{jobs}-r{trial}-run',[binary]);assert b'CHECKS passed=998 failed=0' in output
   run_tasks([Task('link-and-run',validate)],report=False)
   results['samples'].append(sample);(OUT/'summary.json').write_text(json.dumps(results,indent=2)+'\n')
   print(mode,jobs,trial,round(elapsed,3),flush=True)
for mode in plans:
 for jobs in [1,12]:
  samples=[s for s in results['samples'] if s['mode']==mode and s['jobs']==jobs]
  print('MEDIAN',mode,jobs,statistics.median(s['compilation_wall_seconds'] for s in samples),flush=True)
