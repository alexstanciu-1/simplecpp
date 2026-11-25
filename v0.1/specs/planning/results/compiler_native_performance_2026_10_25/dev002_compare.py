from pathlib import Path
import json,collections,hashlib
base=Path('/home/alex-ai/compiler-self-o2-20261025/results-03');r1=base/'jobs-1/published';r2=base/'jobs-32/published'
a={str(p.relative_to(r1)):p for p in r1.rglob('*') if p.suffix in ['.hpp','.cpp']};b={str(p.relative_to(r2)):p for p in r2.rglob('*') if p.suffix in ['.hpp','.cpp']}
old=sorted(a.keys()-b.keys());new=sorted(b.keys()-a.keys());renames={Path(x).stem:Path(y).stem for x,y in zip(old,new)}
def norm(t):
 for x,y in renames.items():t=t.replace(x,y)
 return t
remaining=[]
for k,p in a.items():
 nk=norm(k)
 if nk not in b or norm(p.read_text())!=b[nk].read_text():remaining.append(nk)
report={'files_one':len(a),'files_32':len(b),'renames':renames,'remaining_after_symbol_normalization':remaining,'remaining_equal_line_multisets':{k:collections.Counter(norm(a[next(x for x in a if norm(x)==k)].read_text()).splitlines())==collections.Counter(b[k].read_text().splitlines()) for k in remaining}}
(base/'normalized_comparison.json').write_text(json.dumps(report,indent=2)+'\n');print(json.dumps(report,indent=2))
for jobs in [1,32]:
 p=base/('jobs-'+str(jobs))/'threads.json';d=json.loads(p.read_text());rows=d['samples'];bins=collections.defaultdict(float)
 for r in rows:
  if r['seconds']<5:bins[int(r['seconds']/0.25)]+=r['cpu_seconds']
 print('jobs',jobs,'peak_threads',d['peak_threads'],'peak_active',d['peak_active_threads'],'max_250ms_cpu_cores',max(bins.values())/0.25,'thread_cpu_seconds',sum(d['observed_thread_cpu_seconds'].values()))
