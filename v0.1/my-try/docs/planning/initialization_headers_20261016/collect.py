import pathlib,hashlib,json,subprocess,tarfile
B=pathlib.Path(__file__).resolve().parent;old=B.parent/'allocation'
rows=json.loads((B/'results/summary.json').read_text());assert len(rows)==90
assert all(r['exit']==0 for r in rows)
assert sum(r.get('php_artifact_parity',False) for r in rows)==18
app={str(p.relative_to(B/'accounting')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (B/'accounting').rglob('*.phs')}
prior={str(p.relative_to(old/'accounting')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (old/'accounting').rglob('*.phs')};assert app==prior
assert not subprocess.check_output(['git','-C',str(B/'accounting'),'status','--porcelain'],text=True)
identity=json.loads((B/'native-identity.json').read_text());assert identity['executable']==hashlib.sha256((B/'phpp/.prism/build/main').read_bytes()).hexdigest()
trace_proof=[]
for opt in [0,1]:
 for unit in ['workflow_checks','closing_checks']:
  counts={}
  for variant in ['previous','outlined']:
   events=json.loads((B/'profiles'/f'O{opt}-{variant}-{unit}.json').read_text())['traceEvents']
   matches=[e for e in events if e['name']=='InstantiateFunction' and e.get('args',{}).get('detail','').startswith('scpp::initialization_detail::populate_') and any(t in e.get('args',{}).get('detail','') for t in ['dynamic_value','mixed_t'])]
   counts[variant]=len(matches)
  assert counts['previous']>0 and counts['outlined']==0,counts
  trace_proof.append(dict(optimization=opt,unit=unit,counts=counts))
header_hashes={str(p.relative_to(B/'runtime')):hashlib.sha256(p.read_bytes()).hexdigest() for p in (B/'runtime/include/scpp').glob('data_initializer*.hpp')}
header_hashes['metadata/modules/core.json']=hashlib.sha256((B/'runtime/metadata/modules/core.json').read_bytes()).hexdigest()
families=json.loads((B/'family-results/summary.json').read_text());assert len(families)==21
assert json.loads((B/'runtime-checks/summary.json').read_text())['programs']==49
validation=dict(native_family_cases=len(families),runtime_fixture_programs=49,matrix_builds=90,php_native_artifact_comparisons=18,app_source_hashes=app,app_source_bytes=sum(p.stat().st_size for p in (B/'accounting').rglob('*.phs')),baseline_compiler_sha256=hashlib.sha256((old/'phpp/.prism/build/main').read_bytes()).hexdigest(),candidate_compiler_sha256=identity['executable'],accounting_clean=True,runtime_header_hashes=header_hashes,trace_proof=trace_proof)
(B/'validation.json').write_text(json.dumps(validation,indent=2))
with tarfile.open(B/'evidence.tar.gz','w:gz') as t:
 for n in ['results','runtime-results','profiles','runtime-probe-source','source','generated','native-identity.json','validation.json','shape-proof.json','native-build.log','native-build-resource.txt','matrix.log','runtime.log','profile.log','family-results','family-source','family.log','runtime-checks','runtime-checks.log']:
  p=B/n
  if p.is_dir():
   for f in p.rglob('*'):
    if f.is_file() and f.suffix not in ['.o','.pch'] and (f.suffix or f.name in ['stdout','stderr','app','request']):
     if f.name=='app':continue
     t.add(f,arcname=str(f.relative_to(B)))
  else:t.add(p,arcname=n)
 for n in header_hashes:t.add(B/'runtime'/n,arcname='runtime/'+n)
print('All evidence validated and archived')
