from pathlib import Path
import json,collections
b=Path('/home/alex-ai/compiler-self-o2-20261025')
rows=[json.loads(x) for x in (b/'native-clang.jsonl').read_text().splitlines()]
starts=[i for i,r in enumerate(rows) if r['phase']=='version']
groups=[rows[a:z] for a,z in zip(starts,starts[1:]+[len(rows)])]
results=[]
for label in ['32-hash','32-hash-warm','1-hash','32-lines','32-lines-warm','1-lines']:
 p=b/('native-pool-'+label)
 if not (p/'result.json').exists():continue
 result=json.loads((p/'result.json').read_text())
 # Commands contain the cache path, which distinguishes cold job counts. The
 # invocation boundary separates cold/warm runs sharing that same cache.
 cache='native-pool-cache-'+('lines-' if 'lines' in label else 'hash-')+str(result['jobs'])
 matches=[g for g in groups if any(cache in ' '.join(r['arguments']) for r in g)]
 group=matches[1 if label.endswith('warm') else 0]
 phase={}
 for name in sorted({r['phase'] for r in group}):
  selected=[r for r in group if r['phase']==name];worst=max(selected,key=lambda r:r['seconds'])
  phase[name]={'count':len(selected),'sum_seconds':sum(r['seconds'] for r in selected),'max_seconds':worst['seconds'],'max_arguments':worst['arguments']}
 events=sorted([(r['started'],1) for r in group]+[(r['ended'],-1) for r in group]);live=peak=0
 for _,d in events:live+=d;peak=max(peak,live)
 result.update(label=label,phases=phase,maximum_clang_overlap=peak,mean_clang_overlap=sum(r['seconds'] for r in group)/result['tree']['seconds'])
 results.append(result)
published=b/'performance-native07/published'
sizes={suffix:sorted([{'path':str(p.relative_to(published)),'bytes':p.stat().st_size} for p in published.rglob('*'+suffix)],key=lambda r:r['bytes'],reverse=True)[:10] for suffix in ['.cpp','.hpp']}
(b/'native-pool-summary.json').write_text(json.dumps({'runs':results,'largest_generated_files':sizes},indent=2)+'\n')
print(json.dumps([{'label':r['label'],'seconds':r['tree']['seconds'],'rss_bytes':r['tree']['peak_tree_rss_bytes'],'clang_overlap':r['maximum_clang_overlap'],'max_compile':r['phases'].get('object_compile',{}).get('max_seconds')} for r in results],indent=2))
