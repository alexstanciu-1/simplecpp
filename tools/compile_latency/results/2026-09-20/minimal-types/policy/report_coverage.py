#!/usr/bin/env python3
"""Consolidate measured cases and explicit replay limits; never omit slow cases."""
import argparse,json,statistics
from pathlib import Path
p=argparse.ArgumentParser();p.add_argument('--root',type=Path,required=True);a=p.parse_args();root=a.root
eligibility=json.loads((root/'eligibility.json').read_text());rows=[]
for c in eligibility['commits']:
 r=dict(c);rev=c['commit'];r['comment_only']=rev=='cec8c616'
 if not c['textual_replay_eligible']:r['status']='context_limit'
 elif root.name=='coverage-study' and rev=='1610af1a' and not (root/rev/'status.json').exists() and (root.parent/'literal-history-results/measurements.jsonl').exists():
  prior=root.parent/'literal-history-results/measurements.jsonl';ms=[json.loads(l) for l in prior.read_text().splitlines()];r['status']='measured';r['validation']='prior exact hunk; before/after literal LLVM exit42 and smoke'
 else:
  f=root/rev/'status.json'
  if not f.exists():r['status']='pending'
  else:
   state=json.loads(f.read_text());r['status']=state['result'];r['restored']=state.get('restored',False);r['phase']=state['phase'];r['error']=state.get('error');r['validation']=state.get('validation');r['baseline_setup_seconds']=state.get('before_hunk_setup_seconds')
  f=root/rev/'measurements.jsonl';ms=[json.loads(l) for l in f.read_text().splitlines()] if f.exists() else []
 if r['status']=='measured':
  vals=[m['native_wall_seconds'] for m in ms];r['native_trials']=vals;r['native_summary_seconds']=statistics.median(vals);r['summary_kind']='median' if len(vals)>1 else 'single_screen';r['with_assumed_frontend_seconds']=r['native_summary_seconds']+1.5;r['within_target']=r['native_summary_seconds']<=8.5;r['all_trials_within_target']=all(v<=8.5 for v in vals);r['objects']=[sum(x['output'].endswith('.o') for x in m['native_steps']) for m in ms];r['pch_counts']=[sum(x['output'].endswith(('.pch','.gch')) for x in m['native_steps']) for m in ms]
 rows.append(r)
counts={s:sum(r['status']==s for r in rows) for s in sorted({r['status'] for r in rows})};timed=[r for r in rows if r['status']=='measured'];code=[r for r in timed if not r['comment_only']]
report={'scope':'fixed output policy per study; recent commit sample in current surroundings, not general editor-save success probability; generic smoke/literal validation does not prove every changed feature','counts':counts,'timed_code_cases':len(code),'timed_code_cases_within_target':sum(r['within_target'] for r in code),'comment_only_cases':sum(r['comment_only'] for r in timed),'cases':rows}
(root/'coverage.json').write_text(json.dumps(report,indent=2)+'\n')
lines=['| Commit | Change | Result | Native seconds |','|---|---|---|---|']
for r in rows:
 label=r['status'];value='—'
 if label=='measured':label='within target' if r['within_target'] else 'over target';value=f"{r['native_summary_seconds']:.3f} ({r['summary_kind']}, n={len(r['native_trials'])})"
 lines.append(f"| {r['commit']} | {r['subject']} | {label} | {value} |")
(root/'coverage-table.md').write_text('\n'.join(lines)+'\n');print(json.dumps({k:v for k,v in report.items() if k!='cases'}))
