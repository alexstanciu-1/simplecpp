"""Stable-host paired my-try timing; production publisher/builder, same guarded corpus."""
import argparse,csv,hashlib,json,os,platform,statistics,sys
from pathlib import Path
HERE=Path(__file__).resolve().parent
ROOT=HERE.parents[3]
sys.path.insert(0,str(ROOT/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
from run import corpus
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--before',type=Path,required=True)
p.add_argument('--after',type=Path,required=True)
p.add_argument('--results',type=Path,required=True)
p.add_argument('--mbstring',type=Path,required=True)
p.add_argument('--rounds',type=int,default=3)
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
inputs=corpus(out);cases={x['id']:x for x in json.loads((out/'cases.json').read_text())}
source=out/'source';source.mkdir()
calls=[]
for line in inputs.read_text().splitlines():
 ident,op,*_=line.split('\t');calls.append('run_case('+','.join(json.dumps(v) for v in [ident,op,str(out/'inputs'/ident)])+');')
(source/'main.phs').write_text((HERE/'main.phs').read_text().replace('@@CASES@@','\n'.join(calls)))
(source/'prism.json').write_text('{"runtime":{"languages":{"php":{"profile":"strict"}}}}')
cpu=min(os.sched_getaffinity(0));executables={};records=[]
with CommandRunner(out/'logs',a.after,jobs=12) as runner:
 def build_all():
  for variant in ['before','after']:
   for mode in ['concrete','ordinary']:
    name=variant+'_'+mode;pub=out/name
    runner.run(name+'-emit',['/usr/bin/php',HERE/'publish.php',getattr(a,variant),source,pub,mode])
    result=json.loads(runner.run(name+'-build',['/usr/bin/php8.4','-d','extension='+str(a.mbstring),getattr(a,variant)/'compiler/my-try/tools/build_cpp.php',pub,out/(name+'-cache'),'--no-pch','-O3'],cli_reason='Native builder with temporary mbstring extension'))
    executables[name]=result['executable']
  runner.run('cpp-build',['clang++','-std=c++20','-O3','-DNDEBUG','-I'+str(a.after/'runtime/include'),HERE/'bench.cpp','-o',out/'bench_cpp'])
 run_tasks([Task('build',build_all)])
 def measure():
  for round in range(a.rounds):
   order=['before','after'] if round%2==0 else ['after','before']
   names=[v+'_'+mode for mode in ['concrete','ordinary'] for v in order]+['runtime','cpp']
   for name in names:
    cmd=[executables[name]] if name in executables else [out/'bench_cpp',inputs,name]
    raw=runner.run(f'round{round+1}-{name}',['taskset','-c',str(cpu),*cmd]).decode()
    seen=set()
    for ident,label,trial,n,ns,checksum in csv.reader(raw.splitlines()):
     c=cases[ident];expected=c['compare_sum'] if c['op']=='compare' else c['output_bytes']
     assert int(checksum)==int(n)*expected and int(ns)>0
     seen.add((ident,int(trial)))
     records.append(dict(backend=name,id=ident,round=round+1,trial=int(trial),ns=int(ns)/int(n)))
    assert len(seen)==294
   print('round',round+1,'validated',flush=True)
 run_tasks([Task('paired-runtime',measure)])
summary=[]
for backend in ['before_concrete','after_concrete','before_ordinary','after_ordinary','runtime','cpp']:
 for ident in cases:
  values=[r['ns'] for r in records if r['backend']==backend and r['id']==ident]
  summary.append(dict(backend=backend,id=ident,median_ns=statistics.median(values),min_ns=min(values),max_ns=max(values),samples_ns=values))
(out/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
(out/'samples.json').write_text(json.dumps(records,indent=2)+'\n')
(out/'environment.json').write_text(json.dumps(dict(host=platform.node(),cpu=cpu,rounds=a.rounds,validated_batches=len(records),sources={p.name:hashlib.sha256(p.read_bytes()).hexdigest() for p in HERE.iterdir() if p.is_file()},executables={k:hashlib.sha256(Path(v).read_bytes()).hexdigest() for k,v in executables.items()}),indent=2)+'\n')
