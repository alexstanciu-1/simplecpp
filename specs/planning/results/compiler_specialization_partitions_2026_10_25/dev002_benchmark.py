from pathlib import Path
import json,sys,time,hashlib
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';out=base/'partition-benchmark-02';out.mkdir(exist_ok=False)
profile=base/'clang-profile';published=out/'published'
sys.path.insert(0,str(app/'tools'))
from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root,jobs=32) as runner:
 def work():
  host=out/'host';host.mkdir()
  for name,value in {'request':base/'compiler-input','output':published,'runtime':root/'runtime/metadata/modules','jobs':32}.items():(host/(name+'.txt')).write_text(str(value))
  runner.run('publish',['/usr/bin/time','-v','-o',out/'publish.time','php8.5','-d','memory_limit=4G','-r','require $argv[1]; require $argv[2];',app/'boot.php',base/'driver-source/main.php'],cwd=host,timeout=900)
  old=base/'results-03/published'
  headers=list(published.rglob('*.hpp'))
  changed_headers=[str(p.relative_to(published)) for p in headers if p.read_bytes()!=(old/p.relative_to(published)).read_bytes()]
  assert all(p.startswith('specializations/runtime_') for p in changed_headers),changed_headers
  compile_command=None;link=None
  for p in sorted((profile/'actions').glob('*.command.json')):
   d=json.loads(p.read_text());a=d['arguments']
   if '-c' in a and any(x.endswith('/specializations/containers.cpp') for x in a):compile_command=d
   if '-c' not in a and sum(x.endswith('.o') for x in a)>50:link=d
  assert compile_command and link
  units=sorted((published/'specializations').glob('containers_*.cpp'))
  (out/'objects').mkdir();(out/'times').mkdir()
  def compile_one(unit):
   args=compile_command['arguments'].copy()
   args=[str(unit) if x.endswith('/specializations/containers.cpp') else x.replace(str(old),str(published)) for x in args]
   args=[x for x in args if not x.startswith('-ftime-trace=')]
   args[args.index('-o')+1]=str(out/'objects'/(unit.stem+'.o'))
   if '-MF' in args:args[args.index('-MF')+1]=str(out/'objects'/(unit.stem+'.d'))
   runner.run(unit.stem,['/usr/bin/time','-v','-o',out/'times'/(unit.stem+'.time'),compile_command['executable'],*args],timeout=300)
  start=time.monotonic();run_tasks([Task(p.stem,lambda p=p:compile_one(p)) for p in units],jobs=32);elapsed=time.monotonic()-start
  args=link['arguments'].copy();old_object=str(max((profile/'cache/artifacts').glob('*.o'), key=lambda p:p.stat().st_size))
  index=args.index(old_object);args[index:index+1]=[str(out/'objects'/(p.stem+'.o')) for p in units]
  args[args.index('-o')+1]=str(out/'compiler')
  for i,arg in enumerate(args):
   if arg=='--dependency-file':args[i+(2 if args[i+1]=='-Xlinker' else 1)]=str(out/'link.d')
   if arg.startswith('--dependency-file='):args[i]='--dependency-file='+str(out/'link.d')
  runner.run('link',[link['executable'],*args],timeout=300)
  times=[]
  for p in (out/'times').glob('*.time'):
   lines=p.read_text().splitlines();duration=next(l.rsplit(': ',1)[1] for l in lines if 'Elapsed (wall clock)' in l);seconds=0
   for n in duration.split(':'):seconds=seconds*60+float(n)
   rss=int(next(l.rsplit(': ',1)[1] for l in lines if 'Maximum resident' in l))
   times.append({'unit':p.stem,'seconds':seconds,'rss_kib':rss})
  report={'units':len(units),'implementation_bytes':sum(p.stat().st_size for p in units),'max_unit_bytes':max(p.stat().st_size for p in units),'parallel_compile_seconds':elapsed,'jobs':32,'headers_unchanged':len(headers)-len(changed_headers),'declaration_headers_changed':changed_headers,'slowest':sorted(times,key=lambda x:x['seconds'],reverse=True)[:10],'peak_unit_rss_kib':max(x['rss_kib'] for x in times),'all_times':times}
  (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
  sample=out/'smoke';(sample/'source').mkdir(parents=True);(sample/'source/main.phs').write_text('echo 6 * 7;')
  for name,value in {'request':sample/'source','output':sample/'published','runtime':root/'runtime/metadata/modules','jobs':32}.items():(sample/(name+'.txt')).write_text(str(value))
  assert runner.run('native-compile',[out/'compiler'],cwd=sample,timeout=300)==b'OK'
  build=json.loads(runner.run('smoke-build',['php8.5',app/'tools/build_cpp.php',sample/'published',sample/'cache','-O2'],timeout=300))
  assert runner.run('smoke-run',[build['executable']])==b'42'
  report['native_compiler_smoke']='42';(out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
 run_tasks([Task('partition-benchmark',work)],jobs=1)
