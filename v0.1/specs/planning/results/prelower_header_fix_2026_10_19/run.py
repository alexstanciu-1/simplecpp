from pathlib import Path
import sys,json,time,statistics
ROOT=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');OUT=Path('/tmp/accounting-header-fix');OLD=Path('/tmp/accounting-timing')
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(OUT/'logs',ROOT,jobs=12) as runner:
 def benchmark():
  pub=OUT/'prepared'
  runner.run('publish',['php',OLD/'publish.php',ROOT/'examples/accounting_workload',pub,'prepared'])
  for path in pub.rglob('*.cpp'):
   if 'specializations' not in path.parts:
    assert '"scpp/keyed_path.hpp"' not in path.read_text(),path
    assert '"lang/php/support/php_string.hpp"' not in path.read_text(),path
  command=['php',OLD/'build.php',pub,OUT/'cache',ROOT/'runtime/include',OUT/'timed_clang.py','0']
  cold=json.loads(runner.run('cold',command,timeout=300,cli_reason='Native build'))
  assert b'CHECKS passed=998 failed=0' in runner.run('cold-run',[cold['executable']])
  fixed=[json.loads(s) for s in (OUT/'timed_clang.jsonl').read_text().splitlines()]
  warm=json.loads(runner.run('warm',command,timeout=120,cli_reason='Native reuse'))
  assert warm['compiled']==0 and warm['linked']==0,warm
  baseline=json.loads((OLD/'results.json').read_text())['builds']['prepared-O0']['actions']
  results={'cold':cold,'warm':warm,'variants':{'baseline':[],'fixed':[]}}
  for trial in range(3):
   for label in (['baseline','fixed'] if trial%2==0 else ['fixed','baseline']):
    rows=baseline if label=='baseline' else fixed
    root=OLD/'prepared' if label=='baseline' else pub
    compiles=[r for r in rows if r['phase']=='object_compile' and not any('/specializations/layout' in x for x in r['arguments'])]
    link=[r for r in rows if r['phase']=='link' and not any('/specializations/layout' in x for x in r['arguments'])][-1]['arguments']
    objects=[x for x in link if x.endswith('.o')];assert len(objects)==len(compiles)
    replacements={};seconds=0
    for i,r in enumerate(compiles):
     args=r['arguments'].copy();src=args[args.index('-c')+1]
     if not src.startswith(str(root)) or '/specializations/' in src:continue
     obj=str(OUT/f'{label}-{i}.o');args[args.index('-o')+1]=obj;replacements[objects[i]]=obj
     start=time.monotonic();runner.run(f'{label}-{trial}-{i}',['/usr/bin/env','-i','PATH=/usr/bin:/bin','LC_ALL=C','TZ=UTC','SOURCE_DATE_EPOCH=0','/usr/bin/clang++',*args],timeout=120);seconds+=time.monotonic()-start
    args=[replacements.get(x,x) for x in link];binary=str(OUT/f'{label}.bin');args[args.index('-o')+1]=binary
    start=time.monotonic();runner.run(f'{label}-{trial}-link',['/usr/bin/clang++',*args]);link_seconds=time.monotonic()-start
    assert b'CHECKS passed=998 failed=0' in runner.run(f'{label}-{trial}-run',[binary])
    results['variants'][label].append({'application':seconds,'link':link_seconds});print(label,trial,seconds,flush=True)
    (OUT/'summary.json').write_text(json.dumps(results,indent=2)+'\n')
 run_tasks([Task('accounting-header-fix',benchmark)])
