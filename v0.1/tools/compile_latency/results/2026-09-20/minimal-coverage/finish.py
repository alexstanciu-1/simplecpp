import hashlib,json,shutil,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');tools=repo/'tools/compile_latency';root=Path('/tmp/scpp-edit-latency-20260920/minimal-coverage');app=Path('/tmp/scpp-edit-latency-20260919/app');build=app/'.prism/build';sys.path.insert(0,str(tools))
from minimal_type_layout import prepare
assert json.loads((root/'compound-capture/summary.json').read_text())['restored'];assert json.loads((root/'summary.json').read_text())['code_cases']==12
for group in ['first-15','next-15']:
 s=json.loads((root/group/'coverage.json').read_text());assert 'pending' not in s['counts'];assert all(r.get('restored') for r in s['cases'] if r['status'] in ['measured','replay_incompatible'])
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
hashes=json.loads((root/'policy-hashes.json').read_text());assert all(digest(tools/n)==h for n,h in hashes.items())
graph=(build/'latency-minimal.ninja').read_text();manifest=(build/'latency-minimal-manifest.json').read_bytes();prepare(app);assert graph==(build/'latency-minimal.ninja').read_text();assert manifest==(build/'latency-minimal-manifest.json').read_bytes();assert graph==(tools/'results/2026-09-20/minimal-types/latency-minimal.ninja').read_text()
r=subprocess.run(['ninja','-f','latency-minimal.ninja','-j12'],cwd=build,capture_output=True,text=True,check=True);assert 'no work to do' in r.stdout;(root/'final-noop.log').write_text(r.stdout+r.stderr)
r=subprocess.run([sys.executable,str(tools/'verify_minimal_types.py'),'--app',str(app),'--out',str(root/'final-proofs')],cwd=repo,capture_output=True,text=True);(root/'final-proofs-driver.log').write_text(r.stdout+r.stderr);r.check_returncode()
m=json.loads((app.parent/'manifest.json').read_text());external=[k for k,h in m['source_sha256'].items() if digest(Path(m['source'])/k)!=h];scratch=[k for k,h in m['source_sha256'].items() if k.endswith('.phs') and k!='main.phs' and digest(app/k)!=h];assert not external and not scratch
prior=json.loads((tools/'results/2026-09-20/history-expansion/final-integrity.json').read_text());assert digest(app/'main.phs')==prior['main_sha256'];assert digest(app/'.prism/cache/declared_type_kind_catalog.json')==prior['catalog_sha256']
(root/'final-integrity.json').write_text(json.dumps({'original_source_unchanged':True,'scratch_source_restored':True,'main_and_catalog_restored':True,'all_cases_restored':True,'policy_frozen_through_coverage':True,'generation_idempotent':True,'native_noop':True,'graph_equals_prior_minimal_baseline':True,'final_representation_and_adapter_proofs_passed':True},indent=2)+'\n')
for name in ['latency-minimal.ninja','latency-minimal-manifest.json']:shutil.copy2(build/name,root/name)
policy=root/'policy';policy.mkdir(exist_ok=True)
for p in tools.iterdir():
 if p.is_file() and p.suffix in {'.py','.json','.php'}:shutil.copy2(p,policy/p.name)
(root/'final-policy-hashes.json').write_text(json.dumps({p.name:digest(p) for p in sorted(policy.iterdir())},indent=2)+'\n')
archive=tools/'results/2026-09-20/minimal-coverage'
for p in root.rglob('*'):
 if p.is_file() and p.suffix in {'.py','.php','.json','.jsonl','.log','.ll','.phs','.manifest','.md','.ninja','.txt','.cpp','.hpp','.patch','.tsv'}:
  dest=archive/p.relative_to(root);dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dest)
print('Final proofs, restoration, frozen-policy checks and baseline graph passed; archived',archive)
