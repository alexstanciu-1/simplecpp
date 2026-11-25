"""Static V-L11/V-D14 closeout audit; does not run compiler or matrix tests."""
import gzip,hashlib,itertools,json
from pathlib import Path
P=Path(__file__).resolve().parent;ROOT=P.parents[3]
m=json.loads((P/'carrier_text_source_final.json').read_text());initial=json.loads((P/'carrier_text_source_initial.json').read_text());r=json.loads((P/'carrier_text_results.json').read_text())
archive=(P/'carrier_text_evidence.json.gz').read_bytes();assert hashlib.sha256(archive).hexdigest()==r['evidence_sha256'];e=json.loads(gzip.decompress(archive))
checked=[p for p in m['files'] if not p.startswith('compiler/my-try/docs/')];bad=[p for p in checked if hashlib.sha256((ROOT/p).read_bytes()).hexdigest()!=m['files'][p]];assert not bad,bad
runtime=[p for p in m['files'] if p.startswith('runtime/') and p!='runtime/specs/config.json'];assert all(m['files'][p]==initial['files'][p] for p in runtime)
assert m['files']['runtime/specs/config.json']!=initial['files']['runtime/specs/config.json']
def wrapped(f,p):return p if f=='plain' else '?'+p if f=='nullable' else f+'<'+p+'>'
families=['plain','nullable','result','result_or_false','result_or_bool'];types=[wrapped(f,p) for f in families for p in ['int','float','bool','string']]+['int8','int16','int32','int64','uint8','uint16','uint32']
strings=[wrapped(f,'string') for f in families];expected={(a,'.',b,'string') for a,b in itertools.product(types,repeat=2)}|{(a,'.=',b,a) for a in strings for b in types}
expected|={(a,'.','null','string') for a in types}|{('null','.',a,'string') for a in types}|{('null','.','null','string')}|{(a,'.=','null',a) for a in strings}
assert len(expected)==924 and set(map(tuple,r['signatures']))==expected and len(r['signatures'])==924
probe=r['runtime_probe'];assert probe['passed'] and probe['existing_state_payload_composition_supported'] and not probe['cast_matches_plain_float'];assert probe['observations']['composition_states']=='188'
assert len(r['controls']['original_findings'])==4 and len(r['controls']['rejections'])==71
assert all(row['supported'] for row in r['indexed_preservation']['rows'].values())
for name in r['suites']:
 commands=[c for key,value in e.items() if key.startswith('verification/'+name+'/') and key.endswith('/commands.json') for c in json.loads(value)]
 assert commands and all(c['code']==c['expected'] for c in commands),name
assert r['passed'] and not r['full_matrix_run'] and not r['runtime_implementation_changes'] and not r['native_compiler_rebuilt']
assert 'deferred width-carrier concatenation' in e['verification/smoke-01/logs/concat-plain-plain-int8-uint32-prepare.stderr']
assert 'Invalid compound assignment' in e['verification/regression-mixed/logs/compound_assignments.stderr']
assert not any(r['regression_resolved_exits'].values()) and len(r['regression_resolved_exits'])==8
issues=json.loads((P/'language_verification_issues.json').read_text())['issues'];assert len(issues)==64 and sum(i.get('repair_status')=='implemented-and-verified' for i in issues)==24
assert all(next(i for i in issues if i['id']==key)['repair_status']=='implemented-and-verified' for key in ['V-L11','V-D14'])
a={'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),'source_mismatches':bad,'remote_source_files_checked':r['source_integrity']['checked'],'unchanged_runtime_implementation_files':len(runtime),'runtime_contract_metadata_changed':True,'source_rows':924,'original_findings_replayed':4,'scope_rejections':71,'accepted_suites':len(r['suites']),'accepted_commands':sum(s['commands'] for s in r['suites'].values()),'evidence_records':len(e),'evidence_digest_verified':True,'failed_attempts_preserved':True,'original_issue_closure':'24/64','remaining_blockers_in_scope':r['remaining_blockers_in_scope'],'deferred_domains':r['deferred_domains'],'full_matrix_run':False,'docs_closeout_after_tested_snapshot':True}
(P/'carrier_text_audit.json').write_text(json.dumps(a,indent=2)+'\n');print(json.dumps(a,indent=2))
