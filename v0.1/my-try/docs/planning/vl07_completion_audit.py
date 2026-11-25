"""Local evidence/inventory audit; does not execute compiler tests or the matrix."""
import csv
import gzip
import hashlib
import json
from pathlib import Path
P = Path(__file__).resolve().parent
ROOT = P.parents[3]
manifest = json.loads((P/'vl07_completion_source_final.json').read_text())
results = json.loads((P/'vl07_completion_results.json').read_text())
archive = (P/'vl07_completion_evidence.json.gz').read_bytes()
assert hashlib.sha256(archive).hexdigest() == results['evidence_sha256']
evidence = json.loads(gzip.decompress(archive))
# Documentation closeout follows the tested snapshot; every other included file must match.
checked = [f for f in manifest['files'] if not f.startswith('compiler/my-try/docs/')]
mismatches = [f for f in checked if hashlib.sha256((ROOT/f).read_bytes()).hexdigest()!=manifest['files'][f]]
assert not mismatches, mismatches
rows = list(csv.DictReader((P/'vl07_inventory/signatures.csv').open()))
selected = [r for r in rows if r['group'] in ('E3','O2','C3','S1')]
assert len(selected)==168
assert {(r['lhs'],r['operator'],r['rhs']) for r in selected} == {(a,o,b) for a,o,b,_ in results['carrier_signatures']}
assert sum(r['status']=='verified' for r in rows)==3376
assert sum(r['status']=='verified-existing-path' for r in rows)==17
assert sum(r['status']=='observed-rejection' for r in rows)==112
negative = {r['signature_id'] for r in rows if r['status']=='intentional-rejection'}
assert len(negative)==576 and negative=={r['signature_id'] for r in results['rejections']['records']}
assert not list(csv.DictReader((P/'vl07_inventory/remaining.csv').open()))
existing={(r['lhs'],r['operator'],r['rhs'],r['admitted']) for r in results['existing_paths']['rows']}
assert existing=={(r['lhs'],r['operator'],r['rhs'],r['status']=='verified-existing-path') for r in rows if r['group'] in ('P1V','P1R','P2')}
assert results['generator']['generated_sha256']==manifest['files']['runtime/include/scpp/generated/operators.hpp']
baseline=json.loads(evidence['verification/e3-bool-baseline/summary.json'])
assert sum(not r['passed'] for r in baseline['runtime_results'].values())==12
for name in results['suites']:
 commands=[c for key,value in evidence.items() if key.startswith('verification/'+name+'/') and key.endswith('/commands.json') for c in json.loads(value)]
 assert commands and all(c['code']==c['expected'] for c in commands),name
issues=json.loads((P/'language_verification_issues.json').read_text())['issues']
assert len(issues)==64 and sum(r.get('repair_status')=='implemented-and-verified' for r in issues)==20
record={'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),'source_mismatches':mismatches,'remote_source_files_checked':results['source_integrity']['checked'],'evidence_records':len(evidence),'evidence_digest_verified':True,'exact_new_carrier_signatures':168,'intentional_rejection_rows':576,'verified_existing_rows':17,'observed_existing_rejections':112,'total_supported_carrier_signatures':3376,'unresolved_canonical_candidates':0,'accepted_suites':len(results['suites']),'accepted_commands':sum(s['commands'] for s in results['suites'].values()),'baseline_failed_pair_compilations_preserved':12,'generator_hash_matches_tested_source':True,'docs_closeout_after_tested_snapshot':True,'original_issue_closure':'20/64, V-L07 bounded to approved canonical scope','wider_followups':'W1-W4 remain explicit in vl07_completion.md and issue ledger','full_matrix_run':False}
(P/'vl07_completion_audit.json').write_text(json.dumps(record,indent=2)+'\n')
print(json.dumps(record,indent=2))
