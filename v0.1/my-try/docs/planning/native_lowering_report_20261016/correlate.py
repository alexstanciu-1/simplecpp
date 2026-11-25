from pathlib import Path
import json,statistics,math
B=Path(__file__).resolve().parent;old=B.parent/'initialization-headers-20261016/dev002'
timings=json.loads((B/'timing-inputs.json').read_text());profiles=json.loads((B/'profile-inputs.json').read_text())
result=[]
for level in [0,1,2]:
 report=json.loads((B/'dev002'/f'O{level}-report/marks/lowering-explain.json').read_text())
 rows=[r for r in timings if r['variant']=='outlined' and r['optimization']==level and r['name']=='full'];assert len(rows)==3
 units=[]
 for a in report['artifacts']:
  if a['kind']!='translation_unit':continue
  path=a['path']
  if path.startswith('units/'):
   source=bytes.fromhex(Path(path).stem[1:]).decode()
  elif path=='entry.cpp':source='entry.cpp'
  else:continue
  samples=[r['unit_wall_s'][source] for r in rows if source in r['unit_wall_s']]
  if not samples:continue
  fragments=[f for f in report['fragments'] if f['implementation_path']==path]
  bodies=[f for f in fragments if f['kind'] in ['function_body','file_body']]
  largest=sorted(bodies,key=lambda f:f['fragment_bytes'],reverse=True)[:5]
  units.append(dict(source=source,path=path,median_clang_s=statistics.median(samples),samples_s=samples,generated_bytes=a['bytes'],direct_includes=len(a['direct_includes']),body_fragments=len(bodies),largest_bodies=[dict(owner=f['owner'],bytes=f['fragment_bytes'],headers=len(f['fragment_headers'])) for f in largest]))
 units.sort(key=lambda u:u['median_clang_s'],reverse=True)
 durations=sorted(u['median_clang_s'] for u in units)
 all_calls=sorted(x for u in units for x in u['samples_s'])
 result.append(dict(actual_compile_calls=len(all_calls),actual_p80_s=all_calls[math.ceil(len(all_calls)*.8)-1],actual_p95_s=all_calls[math.ceil(len(all_calls)*.95)-1],actual_max_s=all_calls[-1],optimization=level,unit_count=len(units),p50_s=durations[math.ceil(len(durations)*.5)-1],p80_s=durations[math.ceil(len(durations)*.8)-1],p95_s=durations[math.ceil(len(durations)*.95)-1],units=units,isolated_profiles=[p for p in profiles if p['optimization']==level and p['variant']=='outlined']))
 print('O',level,'units',len(units),'p80',result[-1]['p80_s'])
 for u in units[:6]:print(u['source'],round(u['median_clang_s'],3),u['generated_bytes'],u['direct_includes'],u['body_fragments'],u['largest_bodies'][:2])
(B/'correlation.json').write_text(json.dumps(result,indent=2)+'\n')
