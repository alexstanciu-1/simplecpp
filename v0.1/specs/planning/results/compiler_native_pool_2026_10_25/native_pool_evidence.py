from pathlib import Path
import json,gzip,shlex
b=Path('/home/alex-ai/compiler-self-o2-20261025')
rows=[json.loads(line) for line in (b/'native-clang.jsonl').read_text().splitlines()]
starts=[i for i,r in enumerate(rows) if r['phase']=='version'];groups=[rows[a:z] for a,z in zip(starts,starts[1:]+[len(rows)])]
caches={label:str(b/('native-pool-cache-'+suffix))+'/' for label,suffix in [('one','hash-1'),('pool','hash-32'),('lines','lines-32'),('self','lines-self-32')]}
selected=[g for g in groups if any(any(cache in ' '.join(r['arguments']) for cache in caches.values()) for r in g)]
(b/'native-pool-clang.json.gz').write_bytes(gzip.compress(json.dumps(selected,separators=(',',':')).encode(),mtime=0))
resources=[]
for p in (b/'native-command-times').glob('*.time'):
 fields={}
 for line in p.read_text().splitlines():
  if ': ' in line:
   k,v=line.strip().split(': ',1);fields[k]=v
 command=fields.get('Command being timed','').strip('"')
 label=next((label for label,cache in caches.items() if cache in command),None)
 if label is None or ' -c ' not in command:continue
 args=shlex.split(command);source=next((s for s in args if s.endswith('.cpp')),None)
 if source is None:continue
 resources.append({'run':label,'source':source,'user_seconds':float(fields['User time (seconds)']),'system_seconds':float(fields['System time (seconds)']),'peak_rss_kib':int(fields['Maximum resident set size (kbytes)']),'time_file':p.name,'exit':int(fields['Exit status'])})
(b/'native-pool-object-resources.json.gz').write_bytes(gzip.compress(json.dumps(resources,separators=(',',':')).encode(),mtime=0))
summary={}
for label in caches:
 rs=[r for r in resources if r['run']==label]
 if rs:summary[label]={'objects':len(rs),'total_user_seconds':sum(r['user_seconds'] for r in rs),'total_system_seconds':sum(r['system_seconds'] for r in rs),'largest_rss_object':max(rs,key=lambda r:r['peak_rss_kib'])}
(b/'native-pool-resource-summary.json').write_text(json.dumps(summary,indent=2)+'\n')
print(json.dumps(summary,indent=2))
