from pathlib import Path
import json,statistics,math,hashlib,re
B=Path(__file__).resolve().parent
E=B.parent/'native-edit-matrix-20261016/dev002-results/edit-results'
P=B/'published-1';D=B/'diagnostics/attempt-2'
O=B.parent/'repo/compiler/my-try/docs/planning/accounting_header_fanout_20261016'
O.mkdir(exist_ok=True)
probe={x['unit']:x for x in json.loads((D/'probe-results.json').read_text())}
def name(c):
 p=Path(c['args'][c['args'].index('-c')+1]);return bytes.fromhex(p.stem[1:]).decode() if p.stem.startswith('n') else p.name
calls={phase:[[c for c in json.loads((E/f'trial-{i}'/phase/'clang-calls.json').read_text()) if c['stage']=='object'] for i in range(1,4)] for phase in ['full','common-declaration-edit','signature-callers-edit','class-layout-edit']}
rows=[]
for unit in sorted(probe):
 path=P/'units/n6d61696e'/('n'+unit.encode().hex()+'.cpp');s=path.read_text()
 assert '#include "types/n'+b'AccountingCheck'.hex()+'.hpp"' in s
 rows.append(dict(unit=unit,needs_definition=probe[unit]['exit']!=0,diagnostics=probe[unit]['errors'],cpp_bytes=len(s.encode()),cpp_lines=len(s.splitlines()),lambda_count=s.count('([&]()'),shared_constructions=s.count('::scpp::shared<'),timings={phase:[next(c['wall_s'] for c in trial if name(c)==unit) for trial in trials] for phase,trials in calls.items() if any(name(c)==unit for c in trials[0])}))
traces={}
for f in (D/'objects').glob('*.json'):
 events=json.loads(f.read_text())['traceEvents']
 traces[f.stem]={ 'totals_s':{e['name']:e.get('dur',0)/1e6 for e in events if e['name'].startswith('Total ')},'largest_instantiations':[{'kind':e['name'],'seconds':e['dur']/1e6,'detail':e.get('args',{}).get('detail')} for e in sorted([e for e in events if e['name'] in ['InstantiateFunction','InstantiateClass']],key=lambda e:e['dur'],reverse=True)[:30]]}
summary=dict(units=rows,traces=traces,percentiles={})
for phase,trials in calls.items():
 v=sorted(c['wall_s'] for trial in trials for c in trial)
 summary['percentiles'][phase]={'count':len(v),**{k:v[math.ceil(q*len(v))-1] for k,q in [('p50',.5),('p80',.8),('p95',.95),('max',1)]}}
(O/'analysis.json').write_text(json.dumps(summary,indent=2)+'\n')
for f in ['probe.py','link_probe.py','summarize.py']:(O/f).write_text((B/f).read_text())
proof=json.loads((D/'link-proof.json').read_text());assert all(hashlib.sha256((P/f).read_bytes()).hexdigest()==h for f,h in proof['published_hashes'].items())
(O/'link-proof.json').write_text(json.dumps(proof,indent=2)+'\n')
print('Saved',O)
