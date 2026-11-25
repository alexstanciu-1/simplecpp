"""Static audit of S09 search evidence; no compiler/native execution."""
from pathlib import Path
import gzip, hashlib, json, subprocess
P = Path(__file__).resolve().parent
ROOT = P.parents[3]
m = json.loads((P/'s09_search_source_final.json').read_text())
initial = json.loads((P/'s09_search_source_initial.json').read_text())
r = json.loads((P/'s09_search_results.json').read_text())
archive = (P/'s09_search_evidence.json.gz').read_bytes()
assert hashlib.sha256(archive).hexdigest() == r['evidence_sha256']
e = json.loads(gzip.decompress(archive))
checked = [p for p in m['files'] if not p.startswith('compiler/my-try/docs/')]
bad = [p for p in checked if hashlib.sha256((ROOT/p).read_bytes()).hexdigest() != m['files'][p]]
assert not bad, bad
changed_runtime = [p for p in m['files'] if p.startswith('runtime/') and m['files'][p] != initial['files'].get(p)]
assert set(changed_runtime) == {'runtime/include/modules/strings/strings.hpp', 'runtime/include/lang/php/support/php_string.hpp', 'runtime/metadata/modules/strings.json'}
assert all(m['files'][p] == h for p,h in initial['files'].items() if p.startswith('compiler/my-try/src/'))
for name in r['suites']:
    commands = json.loads(e['verification/'+name+'/logs/commands.json'])
    assert commands and all(c['code'] == c['expected'] for c in commands)
checks = r['summaries']['source-02']['checks']
assert len(checks['rejections']) == 18 and checks['recovery_cycles'] == 4
assert 'exact signature' in checks['downstream_boundary']['diagnostic']
assert checks['originals']['002_str_edges']['compiled']
assert not checks['originals']['004_strpos_false_branch_regression']['compiled']
assert 'substr_replace' in checks['originals']['001_str_surface']['diagnostic']
for row in checks['originals'].values():
    source = row['path']
    old = subprocess.check_output(['git','show','75de94c71e37060c564b6ade19850b7686bd457a:'+source], cwd=ROOT)
    assert old == (ROOT/source).read_bytes() and hashlib.sha256(old).hexdigest() == row['sha256']
assert e['verification/source-02/logs/behavior-execute.stdout'] == 'search_checks=1760\n'
assert e['verification/source-02/logs/behavior-execute.stderr'] == ''
assert r['summaries']['runtime-01']['observation'] == 'search_checks=1760\n'
assert 'separately owned S09 blocker' in e['verification/regression-conditionals/logs/prepare.stderr']
# Existing text functions must remain byte-identical, not just pass ASCII controls.
for path,start,end in [
    ('runtime/include/lang/php/support/php_string.hpp','inline result_or_false<int_t<>> strpos(', 'inline string_t strtolower('),
    ('runtime/include/modules/strings/strings.hpp','inline nullable<int_t<>> find(', 'inline string_t lower(')]:
    old = subprocess.check_output(['git','show','d0f18cf7:'+path],cwd=ROOT).decode()
    new = (ROOT/path).read_text()
    assert old[old.index(start):old.index(end)] == new[new.index(start):new.index(end)]
path = 'runtime/metadata/modules/strings.json'
old = json.loads(subprocess.check_output(['git','show','d0f18cf7:'+path],cwd=ROOT))
new = json.loads((ROOT/path).read_text())
ids = {'strings.'+n+'.'+str(a) for n in ['strpos','strrpos'] for a in [2,3]}
for key in ['functions','cpp']:
    rows = [x for x in new[key] if x['id'] in ids]
    assert len(rows) == 4
    new[key] = [x for x in new[key] if x['id'] not in ids]
assert new == old
issues = json.loads((P/'language_verification_issues.json').read_text())['issues']
assert len(issues) == 64 and sum(i.get('repair_status') == 'implemented-and-verified' for i in issues) == 26
assert next(i for i in issues if i['id'] == 'S09')['repair_status'] == 'partially-implemented-and-verified'
assert r['passed'] and not r['compiler_implementation_changes'] and not r['full_matrix_run']
a = {'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),
    'remote_source_files_checked':r['source_integrity']['checked'],'source_mismatches':bad,
    'runtime_changed':changed_runtime,'compiler_implementation_unchanged':True,'text_helpers_unchanged':True,
    'native_executions':sum(s['native_executions'] for s in r['suites'].values()),
    'accepted_commands':sum(s['commands'] for s in r['suites'].values()),'runtime_search_checks':1760,
    'source_search_checks':1760,'rejections':18,'recovery_cycles':4,'evidence_records':len(e),
    'evidence_digest_verified':True,'failed_attempt_preserved':True,'original_fixtures_match_pinned_v01_bytes':True,
    'original_edges_passed':True,'original_false_branch_passed':False,'remaining_blockers':['substr_replace','S09-B1'],
    'original_issue_closure':'26/64 closed; S09 partial','docs_closeout_after_tested_snapshot':True}
(P/'s09_search_audit.json').write_text(json.dumps(a,indent=2)+'\n')
print(json.dumps(a,indent=2))
