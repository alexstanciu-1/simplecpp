from pathlib import Path
import json,sys,hashlib
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';profile=base/'clang-profile';out=base/'clang-parallel-02'
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
replacements={digest(p):str(p) for p in (out/'objects').glob('*.o')};link=None
for p in sorted((profile/'actions').glob('*.command.json')):
 d=json.loads(p.read_text());a=d['arguments']
 if '-c' not in a and sum(x.endswith('.o') for x in a)>50:link=d
args=link['arguments'].copy();matched=0
for i,arg in enumerate(args):
 p=Path(arg)
 if p.suffix=='.o' and p.is_relative_to(profile/'cache/artifacts') and p.exists():args[i]=replacements[digest(p)];matched+=1
 if arg=='-o':args[i+1]=str(out/'compiler')
 if arg=='--dependency-file':args[i+(2 if args[i+1]=='-Xlinker' else 1)]=str(out/'link.d')
 if arg.startswith('--dependency-file='):args[i]='--dependency-file='+str(out/'link.d')
assert matched==250,matched
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'link-retry-logs',root) as runner:
 run_tasks([Task('link',lambda:runner.run('link',['/usr/bin/time','-v','-o',out/'link-retry.time',link['executable'],*args],timeout=300))],jobs=1)
starts=[];ends=[]
for p in (out/'times').glob('*.time'):
 text=p.read_text();s=next(l.rsplit(': ',1)[1] for l in text.splitlines() if 'Elapsed (wall clock)' in l);value=0
 for n in s.split(':'):value=value*60+float(n)
 end=p.stat().st_mtime;ends.append(end);starts.append(end-value)
ref=json.loads((profile/'logs/clang-profile.stdout').read_text())['executable']
report={'jobs':32,'compile_actions':matched,'compile_wall_seconds_from_action_intervals':max(ends)-min(starts),'all_objects_match_serial_build':True,'executable_matches_serial_build':digest(out/'compiler')==digest(Path(ref)),'note':'Compile pool succeeded; diagnostic linker forwarding was corrected and link retried without recompilation.'}
(out/'summary.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps(report,indent=2))
