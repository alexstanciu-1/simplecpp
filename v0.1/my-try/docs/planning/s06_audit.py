"""Static evidence audit for S06; no compiler or native execution."""
from pathlib import Path
import gzip,hashlib,json,subprocess
P=Path(__file__).resolve().parent;ROOT=P.parents[3]
m=json.loads((P/'s06_source_final.json').read_text());initial=json.loads((P/'s06_source_initial.json').read_text());r=json.loads((P/'s06_results.json').read_text());archive=(P/'s06_evidence.json.gz').read_bytes()
assert hashlib.sha256(archive).hexdigest()==r['evidence_sha256'];e=json.loads(gzip.decompress(archive))
checked=[p for p in m['files'] if not p.startswith('compiler/my-try/docs/')];bad=[p for p in checked if hashlib.sha256((ROOT/p).read_bytes()).hexdigest()!=m['files'][p]];assert not bad,bad
runtime=[p for p in m['files'] if p.startswith('runtime/')];assert all(m['files'][p]==initial['files'][p] for p in runtime)
checks=r['summaries']['candidate-02']['checks'];assert checks['positive_programs']==46 and len(checks['rejections'])==23 and checks['recovery_cycles']==8 and checks['span_checks']==3
assert r['summaries']['control-regression-02']['generated_programs']==137 and r['summaries']['control-regression-02']['php_suites']==12
for name in r['suites']:
 commands=json.loads(e['verification/'+name+'/logs/commands.json']);assert commands and all(c['code']==c['expected'] for c in commands)
assert "Expected '{'" in r['summaries']['baseline']['checks']['original']['diagnostic']
assert 'strpos' in checks['original']['diagnostic'] and not r['original_fixture_end_to_end_passed']
source='tests/php/strings/strict_level_01/strict_strings_004_strpos_false_branch_regression.phs';old=subprocess.check_output(['git','show','75de94c71e37060c564b6ade19850b7686bd457a:'+source],cwd=ROOT)
assert old==(ROOT/source).read_bytes() and hashlib.sha256(old).hexdigest()==checks['original']['sha256']
assert 'Unsupported or incomplete branch grammar' in e['verification/control-regression/logs/control_flow.stderr']
assert any(c['code']==92 for c in json.loads(e['verification/candidate-01/logs/commands.json']))
issues=json.loads((P/'language_verification_issues.json').read_text())['issues'];assert len(issues)==64 and sum(i.get('repair_status')=='implemented-and-verified' for i in issues)==25
assert next(i for i in issues if i['id']=='S06')['repair_status']=='implemented-and-verified'
assert next(i for i in issues if i['id']=='S09').get('repair_status')!='implemented-and-verified'
assert r['passed'] and not r['runtime_changes'] and not r['full_matrix_run'] and not r['native_compiler_rebuilt']
a={'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),'source_mismatches':bad,'remote_source_files_checked':r['source_integrity']['checked'],'runtime_files_unchanged':len(runtime),'native_programs':183,'rejections':23,'recovery_cycles':8,'span_checks':3,'accepted_commands':sum(s['commands'] for s in r['suites'].values()),'evidence_records':len(e),'evidence_digest_verified':True,'failed_attempts_preserved':True,'original_fixture_matches_pinned_v01_bytes':True,'original_fixture_end_to_end_passed':False,'original_fixture_remaining_blocker':'S09: unresolved strpos','remaining_s06_blockers':[],'original_issue_closure':'25/64','full_matrix_run':False,'docs_closeout_after_tested_snapshot':True}
(P/'s06_audit.json').write_text(json.dumps(a,indent=2)+'\n');print(json.dumps(a,indent=2))
