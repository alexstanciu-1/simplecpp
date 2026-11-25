from pathlib import Path
import json,sys,hashlib
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';app=root/'compiler/my-try';out=base/'concurrency-07';binary=out/'compiler-release';tsan=out/'compiler-tsan'
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
report={}
def setup(name,jobs,request):
 folder=out/name;folder.mkdir()
 for key,value in {'request':request,'output':folder/'published','runtime':root/'runtime/metadata/modules','jobs':jobs,'stages':folder/'stages'}.items():(folder/(key+'.txt')).write_text(str(value))
 return folder
def measure(folder):
 marks={p.name:p.stat().st_mtime for p in (folder/'stages').iterdir() if p.is_file()};names=['init-start','sync-start','prepare-start','emit-start','done']
 stages={names[i]:marks[names[i+1]]-marks[names[i]] for i in range(4)}
 samples=json.loads((folder/'threads.json').read_text())['samples'];activity={}
 for name in names:
  group=[s for s in samples if s['stage']==name]
  if group:activity[name]={'max_threads':max(s['threads'] for s in group),'max_active_threads':max(s['active'] for s in group),'sampled_cpu_seconds':sum(s['cpu_seconds'] for s in group),'sampled_seconds':sum(s['interval'] for s in group)}
 return {'stages_seconds':stages,'concurrency':activity,'time':(folder/'compile.time').read_text()}
def save(): (out/'measurement.json').write_text(json.dumps(report,indent=2)+'\n')
def hashes(folder):return {str(p.relative_to(folder)):hashlib.sha256(p.read_bytes()).hexdigest() for p in folder.rglob('*') if p.is_file() and p.suffix in ['.cpp','.hpp']}
with CommandRunner(out/'measure-logs',root) as runner:
 def work():
  folder=setup('tsan-smoke',32,base/'concurrency-smoke-input')
  runner.run('tsan-smoke',['env','TSAN_OPTIONS=halt_on_error=1',tsan],cwd=folder,timeout=180)
  report['tsan_smoke']='pass';save();print('TSAN smoke PASS',flush=True)
  for label,jobs in [('1',1),('2',2),('32',32),('32-repeat',32)]:
   folder=setup('jobs-'+label,jobs,base/'input')
   runner.run('native-'+label,['/usr/bin/time','-v','-o',folder/'compile.time','python3',base/'concurrency_monitor.py',binary,folder/'threads.json'],cwd=folder,timeout=1200)
   report[label]=measure(folder);save();print(label,report[label]['stages_seconds'],flush=True)
  baseline=hashes(out/'jobs-1/published')
  report['output_comparison']={}
  for j in ['2','32','32-repeat']:
   other=hashes(out/('jobs-'+j)/'published');report['output_comparison'][j]={'files':len(other),'different_files':sum(baseline.get(p)!=v for p,v in other.items()),'missing_files':len(baseline.keys()-other.keys())}
  save()
  for mode in ['concrete','ordinary']:
   folder=setup('smoke-'+mode,32,base/'concurrency-smoke-input')
   if mode=='ordinary':(folder/'ordinary.txt').write_text('')
   assert runner.run('smoke-compile-'+mode,[binary],cwd=folder,timeout=180)==b'OK'
   build=json.loads(runner.run('smoke-build-'+mode,['php8.5',app/'tools/build_cpp.php',folder/'published',folder/'cache','-O2'],timeout=600))
   assert runner.run('smoke-run-'+mode,[build['executable']])==b'7:10'
   report['smoke_'+mode]='7:10';save();print(mode,'native program PASS',flush=True)
  folder=setup('tsan-full',32,base/'input')
  runner.run('tsan-full',['env','TSAN_OPTIONS=halt_on_error=1',tsan],cwd=folder,timeout=1200)
  report['tsan_full']='pass';save();print('TSAN full PASS',flush=True)
 run_tasks([Task('concurrency-measurement',work)],jobs=1)
