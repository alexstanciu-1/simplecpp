#!/usr/bin/env python3
"""Collect isolated Clang phase traces for the slowest previously timed units.

Not an incremental build benchmark. Uses captured coherent source and headers,
the same runtime-only PCH and original compiler flags. Does not link or alter
application objects. Total trace categories overlap and must not be added.
"""
import argparse,json,re,shlex,subprocess,time
from pathlib import Path
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--app',type=Path,required=True);p.add_argument('--captured',type=Path,required=True);p.add_argument('--measured',type=Path,required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args();a.out.mkdir(parents=True,exist_ok=False)
graph=(a.measured/'after/build.ninja').read_text();assert graph==(a.captured/'after/build.ninja').read_text();flags=shlex.split(re.search(r'^cxxflags = (.+)$',graph,re.M)[1]);gen=(a.captured/'after/generated').resolve();flags=[('-I'+str(gen)) if f=='-I../minimal/generated' else f for f in flags];assert '-I'+str(gen) in flags
objects={m[1]:m[2].split('/generated/',1)[1] for m in re.finditer(r'^build (\S+): compile\w* (\S+)',graph,re.M) if '/generated/' in m[2] and m[2].endswith('.cpp')};row=json.loads((a.measured/'measurements.jsonl').read_text().splitlines()[0]);selected=sorted((s for s in row['native_steps'] if s['output'] in objects),key=lambda s:s['elapsed_ms'],reverse=True)[:2];results=[]
for index,step in enumerate(selected):
 source=objects[step['output']];trace=(a.out/(str(index)+'.json')).resolve();obj=(a.out/(str(index)+'.o')).resolve();command=['clang++',*flags,'-include','expanded_runtime_pch.hpp','-ftime-trace='+str(trace),'-c',str(gen/source),'-o',str(obj)];start=time.perf_counter();r=subprocess.run(command,cwd=a.app/'.prism/build',capture_output=True,text=True);elapsed=time.perf_counter()-start;(a.out/(str(index)+'.log')).write_text(r.stdout+r.stderr);r.check_returncode();data=json.loads(trace.read_text());totals={e['name'].removeprefix('Total '):e['dur']/1000 for e in data['traceEvents'] if e['name'].startswith('Total ')};results.append({'source':source,'command':command,'isolated_wall_seconds':elapsed,'prior_parallel_job_wall_ms':step['elapsed_ms'],'total_category_ms_overlapping':totals});print(source,elapsed,flush=True)
(a.out/'summary.json').write_text(json.dumps({'scope':'two isolated diagnostic compiles, not save-latency measurements; trace categories overlap','units':results},indent=2)+'\n')
