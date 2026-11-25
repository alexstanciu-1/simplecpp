import hashlib,json,shutil,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
root=Path('/tmp/scpp-edit-latency-20260919');app=root/'app';study=root/'value-coverage'
manifest=json.loads((root/'manifest.json').read_text());source=Path(manifest['source'])
report=json.loads((study/'coverage.json').read_text());assert not report['counts'].get('pending'),report['counts']
assert all(c.get('restored') for c in report['cases'] if c['status'] in ('measured','replay_incompatible'))
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
external=[k for k,h in manifest['source_sha256'].items() if not (source/k).exists() or digest(source/k)!=h]
scratch=[k for k,h in manifest['source_sha256'].items() if k.endswith('.phs') and k!='main.phs' and (not (app/k).exists() or digest(app/k)!=h)]
extra=[str(p.relative_to(app)) for p in app.rglob('*.phs') if '.prism' not in p.parts and str(p.relative_to(app)) not in manifest['source_sha256']]
assert not external and not scratch and not extra,(external,scratch,extra)
r=subprocess.run(['ninja','-f','latency-expanded.ninja','-j12'],cwd=app/'.prism/build',capture_output=True,text=True,check=True);assert 'no work to do' in r.stdout,r.stdout
(study/'final-noop.log').write_text(r.stdout+r.stderr)
(study/'final-integrity.json').write_text(json.dumps({'external_mismatches':external,'scratch_phs_mismatches_except_main':scratch,'extra_phs':extra,'main_sha256':digest(app/'main.phs'),'native_noop':True,'workload_manifest':manifest},indent=2)+'\n')
for name in ['latency-expanded.ninja','latency-expanded-manifest.json','latency-uniform-callable-groups.json','latency-callable-locations.json','latency-callable-partitions.json']:
 shutil.copy2(app/'.prism/build'/name,study/name)
files=['callable_surface.py','callable_groups.py','expanded_layout.py','partitioned_callables.py','layout_isolation.py','counter_boundary.py','stable_locals.py','run_coverage_replay.py','experiment.py','adapter_surface.py','adapter_catalog.py','verify_adapter_semantics.py','audit_callable_ownership.py','private_composition_type.py','verify_composition_type.py','run_corpus.py','prove_type_publication_stability.py','type_publication.py','type_publication_metadata.json','verify_shared_carriers.py','verify_value_traits.py','run_value_trait_probe.py']
(study/'policy-hashes.json').write_text(json.dumps({name:digest(repo/'tools/compile_latency'/name) for name in files},indent=2)+'\n')
dst=repo/'tools/compile_latency/results/2026-09-19/value-coverage'
for path in study.rglob('*'):
 if path.is_file() and path.suffix in {'.json','.jsonl','.log','.ll','.phs','.manifest','.md','.ninja','.txt','.py','.cpp','.patch'}:
  out=dst/path.relative_to(study);out.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(path,out)
print(json.dumps({'archive':str(dst),'counts':report['counts'],'timed_code_cases':report['timed_code_cases'],'timed_code_cases_within_target':report['timed_code_cases_within_target']}))
