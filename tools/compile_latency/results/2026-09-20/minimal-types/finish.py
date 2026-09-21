import hashlib,json,re,shutil,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');app=Path('/tmp/scpp-edit-latency-20260919/app');out=Path('/tmp/scpp-edit-latency-20260920/minimal-types');sys.path.insert(0,str(repo/'tools/compile_latency'))
from minimal_type_layout import prepare,DEFINITION
assert json.loads((out/'coherent/summary.json').read_text())['restored']
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
initial=json.loads((out/'initial-policy-hashes.json').read_text());assert all(digest(repo/'tools/compile_latency'/k)==v for k,v in initial.items())
build=app/'.prism/build';graph=(build/'latency-minimal.ninja').read_text();before={p:digest(p) for p in (app/'.prism/minimal/generated').rglob('*') if p.is_file()};manifest=prepare(app);assert before=={p:digest(p) for p in before};assert graph==(build/'latency-minimal.ninja').read_text();assert '__project_' not in graph and 'expanded-project.pch' not in graph
names={m[2] for p in (app/'.prism/generated').rglob('*.hpp') for m in DEFINITION.finditer(p.read_text())};assert names==set(manifest['types'])
r=subprocess.run(['ninja','-f','latency-minimal.ninja','-j12'],cwd=build,capture_output=True,text=True,check=True);assert 'no work to do' in r.stdout;(out/'final-noop.log').write_text(r.stdout+r.stderr)
r=subprocess.run([sys.executable,str(repo/'tools/compile_latency/verify_minimal_types.py'),'--app',str(app),'--out',str(out/'final-proofs')],capture_output=True,text=True);(out/'final-proofs-driver.log').write_text(r.stdout+r.stderr);r.check_returncode()
m=json.loads((app.parent/'manifest.json').read_text());src=Path(m['source']);external=[k for k,h in m['source_sha256'].items() if digest(src/k)!=h];scratch=[k for k,h in m['source_sha256'].items() if k.endswith('.phs') and k!='main.phs' and digest(app/k)!=h];assert not external and not scratch
integrity=json.loads((out/'initial-integrity.json').read_text());assert integrity['main_sha256']==digest(app/'main.phs');assert integrity['catalog_sha256']==digest(app/'.prism/cache/declared_type_kind_catalog.json')
(out/'final-integrity.json').write_text(json.dumps({'external_mismatches':external,'scratch_phs_mismatches_except_instrumented_main':scratch,'main_and_catalog_restored':True,'generation_idempotent':True,'native_noop':True,'policy_frozen_during_replays':True,'all_current_original_types_published':True,'type_count':len(names),'aggregate_headers_reachable':False,'runtime_only_pch':True},indent=2)+'\n')
for name in ['latency-minimal.ninja','latency-minimal-manifest.json']:shutil.copy2(build/name,out/name)
policy=out/'policy';policy.mkdir(exist_ok=True)
for p in (repo/'tools/compile_latency').iterdir():
 if p.is_file() and p.suffix in {'.py','.json','.php'}:shutil.copy2(p,policy/p.name)
(out/'final-policy-hashes.json').write_text(json.dumps({p.name:digest(p) for p in sorted(policy.iterdir())},indent=2)+'\n')
archive=repo/'tools/compile_latency/results/2026-09-20/minimal-types'
for p in out.rglob('*'):
 if p.is_file() and p.suffix in {'.py','.php','.json','.jsonl','.log','.ll','.phs','.manifest','.md','.ninja','.txt','.cpp','.patch','.tsv'}:
  dst=archive/p.relative_to(out);dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dst)
print('Final proofs, unchanged-source checks, idempotence and no-op passed; archived',archive)
