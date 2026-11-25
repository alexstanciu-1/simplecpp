from pathlib import Path
import json,collections
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'clang-profile';rows=[];pending=[]
def seconds(s):
 result=0
 for x in s.split(':'):result=result*60+float(x)
 return result
for p in sorted((root/'actions').glob('*.command.json')):
 d=json.loads(p.read_text());a=d['arguments'];timefile=p.with_name(p.name.replace('.command.json','.time'))
 if not timefile.exists() or not timefile.stat().st_size:pending.append(str(p));continue
 values={}
 for l in timefile.read_text().splitlines():
  if ': ' in l:k,v=l.strip().rsplit(': ',1);values[k]=v
 category='other'
 if d['executable']=='/usr/bin/clang++':
  if '-c' in a:category='layout-compile' if any('/specializations/layout' in x and x.endswith('.cpp') for x in a) else 'compile'
  elif '-M' in a:category='dependencies'
  elif '-dump-tokens' in a:category='template-validation'
  elif '-o' in a:category='link' if sum(x.endswith('.o') for x in a)>50 else 'layout-link'
  else:category='clang-other'
 elif d['executable']=='/usr/bin/ld':category='link'
 elif d['executable'].endswith('.bin'):category='layout-run'
 source=next((x for x in a if x.endswith('.cpp')),'')
 if '/units/' in source:
  name=Path(source).name[:-4]
  if name.startswith('n'):
   try:source=bytes.fromhex(name[1:]).decode()
   except ValueError:pass
 row={'id':p.name.split('.')[0],'category':category,'source':source,'wall':seconds(values.get('Elapsed (wall clock) time (h:mm:ss or m:ss)','0')),'user':float(values.get('User time (seconds)',0)),'system':float(values.get('System time (seconds)',0)),'rss_kib':int(values.get('Maximum resident set size (kbytes)',0))}
 trace=p.with_name(p.name.replace('.command.json','.trace.json'))
 if trace.exists():
  td=json.loads(trace.read_text());row['clang_totals_seconds']={v['name'][6:]:v['dur']/1e6 for v in td['traceEvents'] if v.get('name','').startswith('Total ') and 'dur' in v}
 rows.append(row)
groups={}
for r in rows:
 g=groups.setdefault(r['category'],{'count':0,'wall':0,'user':0,'system':0,'peak_rss_kib':0});g['count']+=1
 for k in ['wall','user','system']:g[k]+=r[k]
 g['peak_rss_kib']=max(g['peak_rss_kib'],r['rss_kib'])
result={'groups':groups,'pending':pending,'largest_actions':sorted(rows,key=lambda r:r['wall'],reverse=True)[:20],'actions':rows}
(root/'summary.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps({'groups':groups,'pending':len(pending),'largest':[{k:r[k] for k in ['category','source','wall','user','system','rss_kib']} for r in result['largest_actions'][:6]]},indent=2))
