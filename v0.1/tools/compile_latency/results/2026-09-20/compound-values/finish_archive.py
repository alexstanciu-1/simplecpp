import sys,subprocess,json,hashlib,shutil
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260920');app=Path('/tmp/scpp-edit-latency-20260919/app')
for name in ['compound-valid-screen','compound-private-provider']:
 s=json.loads((root/name/'status.json').read_text());assert s['restored'] and s['result']=='measured',s
subprocess.run([sys.executable,str(root/'verify_literal_control.py')],check=True,cwd=repo)
manifest=json.loads((app.parent/'manifest.json').read_text());source=Path(manifest['source'])
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
external=[k for k,h in manifest['source_sha256'].items() if not (source/k).is_file() or digest(source/k)!=h]
scratch=[k for k,h in manifest['source_sha256'].items() if k.endswith('.phs') and k!='main.phs' and (not (app/k).is_file() or digest(app/k)!=h)]
assert not external and not scratch,(external,scratch)
r=subprocess.run(['ninja','-f','latency-expanded.ninja','-j12'],cwd=app/'.prism/build',capture_output=True,text=True,check=True);assert 'no work to do' in r.stdout
(root/'final-noop.log').write_text(r.stdout+r.stderr);(root/'integrity.json').write_text(json.dumps({'external_mismatches':external,'scratch_phs_mismatches_except_main':scratch,'main_sha256':digest(app/'main.phs'),'native_noop':True},indent=2)+'\n')
for name in ['latency-expanded.ninja','latency-expanded-manifest.json']:
 shutil.copy2(app/'.prism/build'/name,root/name)
files=['expanded_layout.py','type_publication.py','type_publication_metadata.json','run_compound_value_probe.py','verify_value_traits.py']
(root/'policy-hashes.json').write_text(json.dumps({k:digest(repo/'tools/compile_latency'/k) for k in files},indent=2)+'\n')
dst=repo/'tools/compile_latency/results/2026-09-20/compound-values'
for p in root.rglob('*'):
 if p.is_file() and p.suffix in {'.json','.jsonl','.log','.ll','.phs','.manifest','.md','.ninja','.txt','.py','.cpp','.patch'}:
  target=dst/p.relative_to(root);target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,target)
print('Proofs, integrity and archive completed:',dst)
