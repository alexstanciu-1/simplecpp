from pathlib import Path
import json,re
base=Path('/home/alex-ai/compiler-self-o2-20261025');root=base/'repo';out=base/'concurrency-07'
manifest=json.loads((out/'published/.scpp-output.manifest').read_text())
units=[root/'runtime/include'/s for s in manifest['runtime_build']['sources']]+[out/'published'/f['path'] for f in manifest['files'] if f['kind']=='translation_unit']
def field(s,key):
 for line in s.splitlines():
  if key in line:return line.split(': ',1)[1]
 return None
def seconds(v):
 n=0.0
 for p in v.split(':'):n=n*60+float(p)
 return n
rows=[]
for i,p in enumerate(units):
 timefile=out/'times'/('release-'+str(i)+'.time')
 if not timefile.exists():continue
 s=timefile.read_text();wall=field(s,'Elapsed (wall clock) time');rss=field(s,'Maximum resident set size')
 rows.append({'file':str(p.relative_to(out/'published')) if p.is_relative_to(out/'published') else str(p.relative_to(root)), 'bytes':p.stat().st_size,'clang_wall_seconds':seconds(wall),'clang_peak_rss_kib':int(rss)})
report={'threshold_bytes':{'cpp':200000,'hpp':100000},'clang_compile_jobs':32,'clang_units':len(rows),'slowest_clang_units':sorted(rows,key=lambda r:r['clang_wall_seconds'],reverse=True)[:15],'largest_compiled_units':sorted(rows,key=lambda r:r['bytes'],reverse=True)[:15],'outputs':{}}
for name in ['published','jobs-1/published','jobs-2/published','jobs-32/published','jobs-32-repeat/published']:
 folder=out/name
 if not folder.exists():continue
 files=[{'file':str(p.relative_to(folder)),'bytes':p.stat().st_size} for p in folder.rglob('*') if p.is_file() and p.suffix in ['.cpp','.hpp']]
 report['outputs'][name]={'file_count':len(files),'largest_cpp':sorted([p for p in files if p['file'].endswith('.cpp')],key=lambda p:p['bytes'],reverse=True)[:10],'largest_hpp':sorted([p for p in files if p['file'].endswith('.hpp')],key=lambda p:p['bytes'],reverse=True)[:10],'over_200kb':[p for p in files if p['bytes']>200000],'headers_over_100kb':[p for p in files if p['file'].endswith('.hpp') and p['bytes']>100000]}
(out/'sizes-and-clang.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))
