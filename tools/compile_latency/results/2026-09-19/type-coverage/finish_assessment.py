import json,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260919/type-coverage-stable');app=root.parent/'app'
report=json.loads((root/'coverage.json').read_text());assert not report['counts'].get('pending')
assert all(c.get('restored') for c in report['cases'] if c['status'] in ('measured','replay_incompatible'))
def check(script,args,log):
 with (root/log).open('w') as output:
  subprocess.run([sys.executable,str(repo/'tools/compile_latency'/script),'--app',str(app),*args],stdout=output,stderr=subprocess.STDOUT,check=True,cwd=repo)
check('prove_type_publication_stability.py',[],'absence-stability.log')
check('verify_composition_type.py',['--out',str(root/'proof')],'composition-proof.log')
check('verify_adapter_semantics.py',['--out',str(root/'adapter-proof')],'adapter-proof.log')
check('verify_callable_diagnostics.py',['--tables'],'source-diagnostics.log')
check('audit_callable_ownership.py',['--isolate-adapters','--out',str(root/'final-ownership-audit.json')],'ownership.log')
subprocess.run([sys.executable,str(root/'archive_when_finished.py')],check=True,cwd=repo)
