from pathlib import Path
import json,sys,time,hashlib,shutil
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';profile=base/'clang-profile'
while not (profile/'logs/clang-profile.stdout').exists():time.sleep(5)
build=json.loads((profile/'logs/clang-profile.stdout').read_text())
out=base/'clang-parallel-02';out.mkdir(exist_ok=False);(out/'objects').mkdir();(out/'times').mkdir();(out/'traces').mkdir()
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
commands=[];link=None
for p in sorted((profile/'actions').glob('*.command.json')):
 d=json.loads(p.read_text());a=d['arguments']
 if d['executable']=='/usr/bin/clang++' and '-c' in a and not any('/specializations/layout' in x and x.endswith('.cpp') for x in a):commands.append((p.stem.split('.')[0],d))
 if '-c' not in a and sum(x.endswith('.o') for x in a)>50:link=d
assert len(commands)==250,len(commands)
with CommandRunner(out/'logs',root,jobs=32) as runner:
 def compile_one(tag,d):
  a=d['arguments'].copy();a[a.index('-o')+1]=str(out/'objects'/(tag+'.o'))
  a=[('-ftime-trace='+str(out/'traces'/(tag+'.json'))) if x.startswith('-ftime-trace=') else x for x in a]
  runner.run(tag,['/usr/bin/time','-v','-o',out/'times'/(tag+'.time'),d['executable'],*a],timeout=1800)
 started=time.monotonic()
 results=run_tasks([Task(tag,lambda tag=tag,d=d:compile_one(tag,d)) for tag,d in commands],jobs=32)
 elapsed=time.monotonic()-started
 def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
 replacements={digest(p):str(p) for p in (out/'objects').glob('*.o')}
 args=link['arguments'].copy();matched=0
 for i,arg in enumerate(args):
  p=Path(arg)
  if p.suffix=='.o' and p.is_relative_to(profile/'cache/artifacts') and p.exists():args[i]=replacements[digest(p)];matched+=1
  if arg=='-o':args[i+1]=str(out/'compiler')
  if arg=='--dependency-file':args[i+1]=str(out/'link.d')
  if arg.startswith('--dependency-file='):args[i]='--dependency-file='+str(out/'link.d')
 assert matched==250,matched
 runner.run('link',['/usr/bin/time','-v','-o',out/'link.time',link['executable'],*args],timeout=300)
 report={'jobs':32,'compile_actions':len(commands),'compile_wall_seconds':elapsed,'all_objects_match_serial_build':True,'executable_matches_serial_build':digest(out/'compiler')==digest(Path(build['executable'])),'cases':[{'name':r.name,'seconds':r.seconds} for r in results]}
 (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
