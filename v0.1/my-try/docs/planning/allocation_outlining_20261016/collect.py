import pathlib,hashlib,json,subprocess,tarfile
B=pathlib.Path(__file__).resolve().parent;old=B.parent/'bench'
rows=json.loads((B/'results/summary.json').read_text());assert len(rows)==65
assert all(r['exit']==0 for r in rows)
assert sum(r.get('php_artifact_parity',False) for r in rows)==13
app={str(p.relative_to(B/'accounting')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (B/'accounting').rglob('*.phs')}
prior={str(p.relative_to(old/'accounting')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (old/'accounting').rglob('*.phs')};assert app==prior
identity=json.loads((B/'native-identity.json').read_text())
assert identity['executable']==hashlib.sha256((B/'phpp/.prism/build/main').read_bytes()).hexdigest()
status=subprocess.check_output(['git','-C',str(B/'accounting'),'status','--porcelain'],text=True);assert not status,status
(B/'validation.json').write_text(json.dumps(dict(matrix_builds=len(rows),php_native_artifact_comparisons=13,app_source_hashes=app,app_source_bytes=sum(p.stat().st_size for p in (B/'accounting').rglob('*.phs')),baseline_compiler_sha256=hashlib.sha256((old/'phpp/.prism/build/main').read_bytes()).hexdigest(),candidate_compiler_sha256=identity['executable'],accounting_clean=True),indent=2))
with tarfile.open(B/'evidence.tar.gz','w:gz') as t:
 for n in ['results','runtime-results','switch-results','profiles','runtime-probe-source','source','generated','native-identity.json','validation.json','shape-proof.json','native-build.log','native-build-resource.txt','native-build-attempt1.log','native-build-attempt1-resource.txt','matrix.log','runtime.log','switches.log','profile.log']:
  p=B/n
  if p.is_dir():
   for f in p.rglob('*'):
    if f.is_file() and f.suffix not in ['.o','.pch']:t.add(f,arcname=str(f.relative_to(B)))
  else:t.add(p,arcname=n)
print('All evidence validated and archived')
