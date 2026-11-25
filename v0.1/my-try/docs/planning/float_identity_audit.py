"""Static closeout audit of V-L06/S04 evidence; no compiler or matrix execution."""
import gzip,hashlib,json,subprocess
from pathlib import Path
P=Path(__file__).resolve().parent;ROOT=P.parents[3]
m=json.loads((P/'float_identity_source_final.json').read_text());initial=json.loads((P/'float_identity_source_initial.json').read_text());r=json.loads((P/'float_identity_results.json').read_text())
archive=(P/'float_identity_evidence.json.gz').read_bytes();assert hashlib.sha256(archive).hexdigest()==r['evidence_sha256'];e=json.loads(gzip.decompress(archive))
checked=[p for p in m['files'] if not p.startswith('compiler/my-try/docs/')]
bad=[p for p in checked if hashlib.sha256((ROOT/p).read_bytes()).hexdigest()!=m['files'][p]];assert not bad,bad
runtime=[p for p in m['files'] if p.startswith('runtime/')];assert all(m['files'][p]==initial['files'][p] for p in runtime)
assert r['signatures']==[['float','===','float','bool'],['float','!==','float','bool']]
assert r['runtime_probe']['runtime_pairs']==256 and r['runtime_probe']['source_forms']==0
carriers=r['carrier_reconciliation']['signatures'];assert len(carriers)==12 and set(map(tuple,carriers))=={(t,o,t,'bool') for t in ['?int','result<int>','result_or_false<int>'] for o in ['==','!=','===','!==']}
for name in r['suites']:
 commands=[c for key,value in e.items() if key.startswith('verification/'+name+'/') and key.endswith('/commands.json') for c in json.loads(value)]
 assert commands and all(c['code']==c['expected'] for c in commands),name
source=r['s04']['source'];baseline='75de94c71e37060c564b6ade19850b7686bd457a';old=subprocess.check_output(['git','show',baseline+':'+source],cwd=ROOT)
assert old==(ROOT/source).read_bytes() and hashlib.sha256(old).hexdigest()==r['s04']['source_sha256']
assert e['verification/s04/logs/original-execute.stdout']==r['s04']['expected_stdout']
assert e['verification/s04/logs/original-execute.stderr']==''
assert 'Cross-family exclusion failed' in e['verification/float-identity/logs/source-prepare.stderr']
issues=json.loads((P/'language_verification_issues.json').read_text())['issues'];assert len(issues)==64 and sum(i.get('repair_status')=='implemented-and-verified' for i in issues)==22
assert all(next(i for i in issues if i['id']==key)['repair_status']=='implemented-and-verified' for key in ['S04','V-L06'])
a={'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),'source_mismatches':bad,'remote_source_files_checked':r['source_integrity']['checked'],'unchanged_runtime_files':len(runtime),'new_scalar_signatures':2,'reconciled_original_carrier_cells':12,'s04_matches_pinned_v01_bytes':True,'s04_exact_output':True,'historical_failed_attempts_preserved':True,'accepted_suites':len(r['suites']),'accepted_commands':sum(x['commands'] for x in r['suites'].values()),'evidence_records':len(e),'evidence_digest_verified':True,'original_issue_closure':'22/64','newly_exposed_implementation_blockers':[],'full_matrix_run':False,'docs_closeout_after_tested_snapshot':True}
(P/'float_identity_audit.json').write_text(json.dumps(a,indent=2)+'\n');print(json.dumps(a,indent=2))
