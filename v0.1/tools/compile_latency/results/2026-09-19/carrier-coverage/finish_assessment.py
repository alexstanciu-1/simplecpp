import hashlib,json,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260919/carrier-coverage');app=root.parent/'app'
metadata=json.loads((repo/'tools/compile_latency/type_publication_metadata.json').read_text())
assert hashlib.sha256((app/'.prism/generated/compile/backend/llvm_text_from_plan.hpp').read_bytes()).hexdigest()==metadata['source_header_sha256'], 'Generated metadata source no longer matches pinned baseline'
report=json.loads((root/'coverage.json').read_text());assert not report['counts'].get('pending')
assert all(c.get('restored') for c in report['cases'] if c['status'] in ('measured','replay_incompatible'))
def check(script,args,log):
 with (root/log).open('w') as output:
  subprocess.run([sys.executable,str(repo/'tools/compile_latency'/script),'--app',str(app),*args],stdout=output,stderr=subprocess.STDOUT,check=True,cwd=repo)
check('verify_shared_carriers.py',['--out',str(root/'carrier-proof')],'carrier-proof.log')
check('prove_type_publication_stability.py',[],'absence-stability.log')
check('verify_composition_type.py',['--out',str(root/'proof')],'composition-proof.log')
check('verify_adapter_semantics.py',['--out',str(root/'adapter-proof')],'adapter-proof.log')
check('verify_callable_diagnostics.py',['--tables'],'source-diagnostics.log')
check('audit_callable_ownership.py',['--isolate-adapters','--out',str(root/'final-ownership-audit.json')],'ownership.log')
subprocess.run([sys.executable,str(root/'archive_when_finished.py')],check=True,cwd=repo)
