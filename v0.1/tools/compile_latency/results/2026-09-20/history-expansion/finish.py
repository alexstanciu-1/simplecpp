import sys,subprocess,json,hashlib,re,shutil
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260920/history-expansion');app=Path('/tmp/scpp-edit-latency-20260919/app')
assert json.loads((root/'coherent-projected/summary.json').read_text())['restored']
assert not json.loads((root/'coverage-16-30/coverage.json').read_text())['counts'].get('pending')
for tool,args in [('verify_value_traits.py',['--provider','--out',str(root/'value-proof')]),('verify_shared_carriers.py',['--out',str(root/'carrier-proof')]),('verify_composition_type.py',['--out',str(root/'composition-proof')]),('verify_adapter_semantics.py',['--out',str(root/'adapter-proof')]),('audit_callable_ownership.py',['--isolate-adapters','--out',str(root/'ownership.json')])]:
 with (root/(tool+'.log')).open('w') as f:subprocess.run([sys.executable,str(repo/'tools/compile_latency'/tool),'--app',str(app),*args],cwd=repo,stdout=f,stderr=subprocess.STDOUT,check=True)
def digest(p):return hashlib.sha256(p.read_bytes()).hexdigest()
m=json.loads((app.parent/'manifest.json').read_text());src=Path(m['source']);external=[k for k,h in m['source_sha256'].items() if not (src/k).exists() or digest(src/k)!=h];scratch=[k for k,h in m['source_sha256'].items() if k.endswith('.phs') and k!='main.phs' and (not (app/k).exists() or digest(app/k)!=h)];extra=[str(p.relative_to(app)) for p in app.rglob('*.phs') if '.prism' not in p.parts and str(p.relative_to(app)) not in m['source_sha256']];assert not external and not scratch and not extra
r=subprocess.run(['ninja','-f','latency-expanded.ninja','-j12'],cwd=app/'.prism/build',capture_output=True,text=True,check=True);assert 'no work to do' in r.stdout;(root/'final-noop.log').write_text(r.stdout+r.stderr)
graph=(app/'.prism/build/latency-expanded.ninja').read_text();old=repo/'tools/compile_latency/results/2026-09-20/provider-coverage/latency-expanded.ninja';graph_equal=graph==old.read_text()
(root/'final-integrity.json').write_text(json.dumps({'external_mismatches':external,'scratch_phs_mismatches_except_main':scratch,'extra_phs':extra,'main_sha256':digest(app/'main.phs'),'catalog_sha256':digest(app/'.prism/cache/declared_type_kind_catalog.json'),'native_noop':True,'graph_equals_prior_provider_graph':graph_equal},indent=2)+'\n')
# Read-only audit for the next publication candidate, on the restored pinned state.
from_types={'ScalarConditionOperand':'compile/backend/scalar_body_statement_collection.hpp','ControlFlowLocalWriteSet':'compile/control_flow/control_flow_dataflows.hpp','BackendModuleCompositionInput':'compile/backend/llvm_text_from_plan.hpp'}
active={x.split('/generated/',1)[1] for x in re.findall(r'^build \S+: compile\w* (\S+)',graph,re.M) if '/generated/' in x};gen=app/'.prism/expanded/generated';audit={}
for name,owner in from_types.items():
 body=re.search(r'^class '+name+r'[^\n]*\{\n[\s\S]*?^};',(gen/owner).read_text(),re.M);assert body,name
 cpp=[key for key in sorted(active) if re.search(r'\b'+name+r'\b',(gen/key).read_text())]
 headers=[str(p.relative_to(gen)) for p in gen.rglob('*.hpp') if not str(p.relative_to(gen)).startswith('__callable/') and re.search(r'\b'+name+r'\b',p.read_text())]
 audit[name]={'owner':owner,'definition_sha256':hashlib.sha256(body[0].encode()).hexdigest(),'lexical_active_cpp_consumers':cpp,'non_callable_headers_mentioning_type':headers,'scope':'restored pinned source; lexical screening only, not a complete semantic dependency proof'}
(root/'next-type-publication-audit.json').write_text(json.dumps(audit,indent=2)+'\n')
for name in ['latency-expanded.ninja','latency-expanded-manifest.json']:shutil.copy2(app/'.prism/build'/name,root/name)
files=['expanded_layout.py','private_composition_type.py','type_publication.py','historical_type_metadata.py','run_coherent_history.py','run_coverage_replay.py','type_publication_metadata.json'];(root/'final-policy-hashes.json').write_text(json.dumps({k:digest(repo/'tools/compile_latency'/k) for k in files},indent=2)+'\n')
dst=repo/'tools/compile_latency/results/2026-09-20/history-expansion'
for p in root.rglob('*'):
 if p.is_file() and p.suffix in {'.json','.jsonl','.log','.ll','.phs','.manifest','.md','.ninja','.txt','.py','.cpp','.patch','.tsv'}:
  target=dst/p.relative_to(root);target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,target)
print('Archived',dst,'graph_equal',graph_equal)
