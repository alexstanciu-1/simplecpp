"""Static evidence audit for S09 replacement; no compiler/native execution."""
from pathlib import Path
import gzip, hashlib, json, subprocess
P = Path(__file__).resolve().parent
ROOT = P.parents[3]
m = json.loads((P/'s09_replace_source_final.json').read_text())
initial = json.loads((P/'s09_replace_source_initial.json').read_text())
r = json.loads((P/'s09_replace_results.json').read_text())
archive = (P/'s09_replace_evidence.json.gz').read_bytes()
assert hashlib.sha256(archive).hexdigest() == r['evidence_sha256']
e = json.loads(gzip.decompress(archive))
checked = [p for p in m['files'] if not p.startswith('compiler/my-try/docs/')]
bad = [p for p in checked if hashlib.sha256((ROOT/p).read_bytes()).hexdigest() != m['files'][p]]
assert not bad, bad
changed_runtime = [p for p in m['files'] if p.startswith('runtime/') and m['files'][p] != initial['files'].get(p)]
assert set(changed_runtime) == {'runtime/include/modules/strings/strings.hpp', 'runtime/metadata/modules/strings.json', 'runtime/specs/catalog.md'}
assert all(m['files'][p] == h for p,h in initial['files'].items() if p.startswith('compiler/my-try/src/'))
for name in r['suites']:
    commands = json.loads(e['verification/'+name+'/logs/commands.json'])
    assert commands and all(c['code'] == c['expected'] for c in commands)
checks = r['summaries']['source-01']['checks']
assert len(checks['rejections']) == 11 and checks['recovery_cycles'] == 2
assert checks['original']['compiled']
source = checks['original']['path']
old = subprocess.check_output(['git','show','75de94c71e37060c564b6ade19850b7686bd457a:'+source],cwd=ROOT)
assert old == (ROOT/source).read_bytes() and hashlib.sha256(old).hexdigest() == checks['original']['sha256']
info = json.loads((ROOT/source).with_suffix('.test-info.json').read_text())
assert e['verification/source-01/logs/original-execute.stdout'] == info['expect']['run']['stdout']
assert e['verification/source-01/logs/original-execute.stderr'] == ''
assert e['verification/source-01/logs/behavior-execute.stdout'] == 'replace_checks=3312\n'
assert e['verification/source-01/logs/behavior-execute.stderr'] == ''
assert r['summaries']['runtime-01']['observation'] == 'replace_checks=3312\n'
assert 'exact signature' in r['summaries']['regression-search']['checks']['downstream_boundary']['diagnostic']
# Only the replacement owner may change in the shared implementation header.
path = 'runtime/include/modules/strings/strings.hpp'
old = subprocess.check_output(['git','show','c37e0597:'+path],cwd=ROOT).decode()
new = (ROOT/path).read_text()
start = 'inline string_t substr_replace('
end = 'inline string_t replace('
new_start = '// Zero-length windows insert, but unusable negative-length windows are no-ops.'
assert old[:old.index(start)] == new[:new.index(new_start)]
assert old[old.index(end):] == new[new.index(end):]
path = 'runtime/metadata/modules/strings.json'
old = json.loads(subprocess.check_output(['git','show','c37e0597:'+path],cwd=ROOT))
new = json.loads((ROOT/path).read_text())
ids = {'strings.substr_replace.3','strings.substr_replace.4'}
for key in ['functions','cpp']:
    assert len([x for x in new[key] if x['id'] in ids]) == 2
    new[key] = [x for x in new[key] if x['id'] not in ids]
assert new == old
issues = json.loads((P/'language_verification_issues.json').read_text())['issues']
assert len(issues) == 64 and sum(i.get('repair_status') == 'implemented-and-verified' for i in issues) == 26
issue = next(i for i in issues if i['id'] == 'S09')
assert issue['repair_status'] == 'partially-implemented-and-verified'
assert [x['id'] for x in issue['remaining_blockers']] == ['S09-B1']
assert r['passed'] and not r['compiler_implementation_changes'] and not r['full_matrix_run']
a = {'doc_status':'supporting','date':'2026-10-20','passed':True,'source_files_checked':len(checked),
    'remote_source_files_checked':r['source_integrity']['checked'],'source_mismatches':bad,
    'runtime_changed':changed_runtime,'compiler_implementation_unchanged':True,'other_string_implementations_unchanged':True,
    'native_executions':sum(s['native_executions'] for s in r['suites'].values()),
    'accepted_commands':sum(s['commands'] for s in r['suites'].values()),'runtime_replacement_checks':3312,
    'source_replacement_checks':3312,'rejections':11,'recovery_cycles':2,'evidence_records':len(e),
    'evidence_digest_verified':True,'original_fixture_matches_pinned_v01_bytes':True,
    'original_surface_fixture_end_to_end_passed':True,'remaining_replacement_blockers':[],
    'remaining_s09_blockers':['S09-B1'],'original_issue_closure':'26/64 closed; S09 partial',
    'docs_closeout_after_tested_snapshot':True}
(P/'s09_replace_audit.json').write_text(json.dumps(a,indent=2)+'\n')
print(json.dumps(a,indent=2))
