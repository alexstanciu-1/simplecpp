"""Validate completed experiment and normalize derived overlap units from raw timestamps."""
import hashlib,json,subprocess
from pathlib import Path
B=Path(__file__).resolve().parent
root=B/'edit-results'
rows=json.loads((root/'summary.json').read_text())
assert len(rows)==78,len(rows)
for r in rows:
 p=root/f"trial-{r['trial']}"/r['name']
 calls=json.loads((p/'clang-calls.json').read_text())
 assert r['exit']==0 and r['self_test_pass']
 assert all(c['args']==c['actual_args'] and c['exit']==0 for c in calls)
 r.pop('provider_overlap_pch_s',None)
 pchs=[c for c in calls if c['stage']=='pch']
 if pchs:
  a=min(c['start_ns'] for c in pchs);z=max(c['end_ns'] for c in pchs)
  r['provider_overlap_pch_sum_s']=sum(max(0,min(z,c['end_ns'])-max(a,c['start_ns'])) for c in calls if c['stage']=='runtime')/1e9
 if r.get('phase') in ['edit','revert']:
  changed=[x for x in r['changed_inputs'] if '/published-' in x]
  case=r['case']
  if case in ['class-layout','common-declaration','signature-callers']:assert any(x.endswith('.hpp') for x in changed),(case,changed)
  if case in ['pch-header','provider-implementation']:assert not changed,(case,changed)
  if case=='multiple-bodies':assert r['rebuilt_objects']==['src/customer_profile.phs']
  if case=='provider-implementation':assert r['rebuilt_objects']==['runtime/core/runtime.cpp']
 (p/'result.json').write_text(json.dumps(r,indent=2))
for case in ['multiple-bodies','signature-callers','class-layout','common-declaration','pch-header','provider-implementation']:
 group=[r for r in rows if r.get('case')==case and r.get('phase') in ['edit','revert']]
 assert len(group)==6
 assert all(r['rebuilt_objects']==group[0]['rebuilt_objects'] and r['actions']==group[0]['actions'] for r in group)
 if case=='common-declaration':assert all(len(r['changed_inputs'])==1 for r in group)
(root/'summary.json').write_text(json.dumps(rows,indent=2))
for repo in [B/'accounting',B.parent/'repo/runtime']:
 assert subprocess.check_output(['git','-C',str(repo),'status','--porcelain'],text=True)=='',repo
print('PASS: 78 builds, unchanged/revert checks, dependency sets, header edits, executable self-tests, and restored Git fixtures')
