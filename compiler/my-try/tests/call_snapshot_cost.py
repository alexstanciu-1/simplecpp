"""Interleaved compiler-overhead measurement on two isolated source trees."""
import argparse, hashlib, json, os, platform, statistics, sys, time
from pathlib import Path
APP=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
p=argparse.ArgumentParser(description=__doc__)
p.add_argument('--before',type=Path,required=True)
p.add_argument('--after',type=Path,required=True)
p.add_argument('--results',type=Path,required=True)
p.add_argument('--rounds',type=int,default=15)
p.add_argument('--php', default='php')
p.add_argument('--workload-dir', type=Path, help='Replay retained workload subdirectories exactly')
a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
cpu=min(os.sched_getaffinity(0));records=[]
workloads={
 'simple_1000':'function work(string &$s,string &$t):int{$n=0;'+('$n+=substr_compare($s,$t,0);'*1000)+'return $n;}',
 'fallback_1000':'function other():string{return "abc";}function work(string &$s):int{$n=0;'+('$n+=substr_compare($s,other(),0);'*1000)+'return $n;}',
 'string_benchmark':(a.after/'specs/planning/benchmarks/string_defaults/main.phs').read_text().replace('bench_fence();', '').replace('@@CASES@@','\n'.join(f'run_case("case{i}","compare","unused");' for i in range(42))),
}
if a.workload_dir:
 workloads = {name:(a.workload_dir/name/'main.phs').read_text() for name in workloads}
for name,source in workloads.items():
 d=out/name;d.mkdir();(d/'main.phs').write_text(source);(d/'prism.json').write_text('{"runtime":{"languages":{"php":{"profile":"strict"}}}}')
with CommandRunner(out/'logs',a.after,jobs=12) as runner:
 def measure():
  for workload in workloads:
   for mode in ['ordinary','concrete']:
    for iteration in range(-1,a.rounds):
     order=['before','after'] if iteration%2==0 else ['after','before']
     for variant in order:
      name=f'{workload}-{mode}-{iteration+1}-{variant}'
      started=time.monotonic()
      raw=runner.run(name,['taskset','-c',str(cpu),a.php,'-d','opcache.enable_cli=0','-d','xdebug.mode=off',APP/'tests/call_snapshot_cost.php',getattr(a,variant),out/workload,mode])
      wall=(time.monotonic()-started)*1000
      row=json.loads(raw);row.update(workload=workload,mode=mode,round=iteration,variant=variant,process_ms=wall)
      records.append(row)
      (out/'samples.json').write_text(json.dumps(records,indent=2)+'\n')
    print(workload,mode,'done',flush=True)
 run_tasks([Task('interleaved-compilation',measure)])
summary=[]
for workload in workloads:
 for mode in ['ordinary','concrete']:
  data={variant:[r for r in records if r['round']>=0 and r['variant']==variant and r['mode']==mode and r['workload']==workload] for variant in ['before','after']}
  for metric in ['boot_ms','frontend_ms','prepare_ms','emit_ms','total_ms','process_ms','peak_bytes','artifact_bytes']:
   values={v:statistics.median(r[metric] for r in data[v]) for v in data}
   summary.append(dict(workload=workload,mode=mode,metric=metric,**values,change_pct=(values['after']/values['before']-1)*100))
(out/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
(out/'environment.json').write_text(json.dumps({'host':platform.node(),'platform':platform.platform(),'cpu':cpu,'rounds':a.rounds,'workload_hashes':{k:hashlib.sha256(v.encode()).hexdigest() for k,v in workloads.items()}},indent=2)+'\n')
