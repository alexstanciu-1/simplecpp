from pathlib import Path
import json,re
base=Path('/home/alex-ai/compiler-self-o2-20261025')
result={}
for candidate in ['performance-manifest01','performance-layout01']:
 out=base/candidate; rows={}
 for label in ['1','8','32','repeat']:
  folder=out/('measure-'+label)
  if not (folder/'measurement.json').exists():continue
  d=json.loads((folder/'measurement.json').read_text());t=d['time']
  marks={p.name:p.stat().st_mtime for p in (folder/'stages').iterdir()}
  phases={name:marks[b]-marks[a] for name,a,b in [('frontend','sync-start','prepare-start'),('preparation','prepare-start','emit-start'),('rendering','profile-render-start','profile-headers-start'),('layout_depths','profile-layout-depths-start','profile-sort-start'),('manifest','profile-generation-end','profile-publication-manifest-end')]}
  elapsed=re.search(r'Elapsed \(wall clock\) time \(h:mm:ss or m:ss\): (.*)',t).group(1)
  parts=[float(x) for x in elapsed.split(':')];wall=sum(v*60**i for i,v in enumerate(reversed(parts)))
  rows[label]={'seconds':wall,'peak_rss_kib':int(re.search(r'Maximum resident set size \(kbytes\): (\d+)',t).group(1)),'phases':phases}
 result[candidate]=rows
for name,folder in [('compiler',base/'performance-layout01/published'),('workload',base/'performance-layout01/measure-32/published')]:
 files=list(folder.rglob('*.cpp'))+list(folder.rglob('*.hpp'))
 result[name+'_sizes']={ext:{'maximum_bytes':max(p.stat().st_size for p in files if p.suffix==ext),'over_200000':sum(p.stat().st_size>200000 for p in files if p.suffix==ext)} for ext in ['.cpp','.hpp']}
a=base/'performance-manifest01/measure-1/published/.scpp-output.manifest';b=base/'performance-layout01/measure-1/published/.scpp-output.manifest'
result['layout_one_worker_manifest_exact_match']=a.read_bytes()==b.read_bytes()
threads=base/'performance-layout01/measure-threads-fast/threads.json'
if threads.exists():
 d=json.loads(threads.read_text());result['threads']={'exit':d['exit'],'stages':{stage:{'maximum_threads':max(r['threads'] for r in d['samples'] if r['stage']==stage),'maximum_active_threads':max(r['active'] for r in d['samples'] if r['stage']==stage)} for stage in sorted({r['stage'] for r in d['samples']})}}
print(json.dumps(result,indent=2))
