"""Static S07 evidence audit; no compiler/native execution."""
from pathlib import Path
import gzip,hashlib,json,subprocess
P=Path(__file__).resolve().parent;ROOT=P.parents[3]
m=json.loads((P/'s07_source_final.json').read_text());initial=json.loads((P/'s07_source_initial.json').read_text());r=json.loads((P/'s07_results.json').read_text());archive=(P/'s07_evidence.json.gz').read_bytes()
assert hashlib.sha256(archive).hexdigest()==r['evidence_sha256'];e=json.loads(gzip.decompress(archive))
checked=[p for p in m['files'] if not p.startswith('compiler/my-try/docs/')];bad=[p for p in checked if hashlib.sha256((ROOT/p).read_bytes()).hexdigest()!=m['files'][p]];assert not bad,bad
runtime=[p for p in m['files'] if p.startswith('runtime/') and p!='runtime/metadata/modules/core.json'];assert all(m['files'][p]==initial['files'][p] for p in runtime)
checks=r['summaries']['source-02']['checks'];assert len(checks['signatures'])==3 and len(checks['rejections'])==47 and checks['recovery_cycles']==6
assert r['summaries']['regression-mixed']['generated_programs']==324
assert r['summaries']['regression-conditionals']['checks']['positive_programs']==46
for name in r['suites']:
 commands=json.loads(e['verification/'+name+'/logs/commands.json']);assert commands and all(c['code']==c['expected'] for c in commands)
assert 'is_bool' in r['summaries']['source-baseline']['checks']['diagnostic']
assert 'function_savedU_result' in e['verification/source-01/logs/behavior-build-unit-1.stderr']
assert e['verification/source-02/logs/original-execute.stdout']=='not-bool\nnot-int\nnot-float\nbool\nint\nfloat\n'
assert e['verification/source-02/logs/original-execute.stderr']==''
assert e['verification/source-02/logs/behavior-execute.stdout']=='predicate_checks=69\n'
assert e['verification/source-02/logs/behavior-execute.stderr']==''
source='tests/php/control_flow/strict_level_01/strict_control_flow_002_mixed_type_predicates.phs';old=subprocess.check_output(['git','show','75de94c71e37060c564b6ade19850b7686bd457a:'+source],cwd=ROOT)
assert old==(ROOT/source).read_bytes() and hashlib.sha256(old).hexdigest()==checks['original']['sha256']
metadata='runtime/metadata/modules/core.json';old=json.loads(subprocess.check_output(['git','show','71f3d607:'+metadata],cwd=ROOT));new=json.loads((ROOT/metadata).read_text())
# Removing the three admitted rows must recover the entire previous metadata.
ids={'core.is_bool.mixed','core.is_int.mixed','core.is_float.mixed'}
def strip(value):
 if isinstance(value,list):return [strip(x) for x in value if not isinstance(x,dict) or x.get('id') not in ids]
 if isinstance(value,dict):return {k:strip(v) for k,v in value.items()}
 return value
assert strip(new)==old
issues=json.loads((P/'language_verification_issues.json').read_text())['issues'];assert len(issues)==64 and sum(i.get('repair_status')=='implemented-and-verified' for i in issues)==26
assert next(i for i in issues if i['id']=='S07')['repair_status']=='implemented-and-verified'
assert r['passed'] and not r['runtime_implementation_changes'] and not r['full_matrix_run'] and not r['native_compiler_rebuilt']
a={'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),'source_mismatches':bad,'remote_source_files_checked':r['source_integrity']['checked'],'runtime_files_unchanged_except_core_metadata':len(runtime),'native_executions':sum(s['native_executions'] for s in r['suites'].values()),'rejections':47,'recovery_cycles':6,'accepted_commands':sum(s['commands'] for s in r['suites'].values()),'evidence_records':len(e),'evidence_digest_verified':True,'failed_attempt_preserved':True,'original_fixture_matches_pinned_v01_bytes':True,'original_fixture_end_to_end_passed':True,'remaining_s07_blockers':[],'original_issue_closure':'26/64','full_matrix_run':False,'docs_closeout_after_tested_snapshot':True}
(P/'s07_audit.json').write_text(json.dumps(a,indent=2)+'\n');print(json.dumps(a,indent=2))
