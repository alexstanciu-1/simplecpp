import json,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260919/adapter-coverage');app=root.parent/'app'
# Run manually only after run_fixed_sample.py exits and all cases are restored.
report=json.loads((root/'coverage.json').read_text());assert not report['counts'].get('pending')
assert all(c.get('restored') for c in report['cases'] if c['status'] in ('measured','replay_incompatible'))
old=root/'excluded-overlapping-filesystem-audit';old.mkdir(exist_ok=True)
assert not (old/'f1d3a846').exists()
(root/'f1d3a846').rename(old/'f1d3a846')
(root/'f1d3a846-driver.log').rename(old/'f1d3a846-driver.log')
with (root/'f1d3a846-driver.log').open('w') as log:
 subprocess.run([sys.executable,str(repo/'tools/compile_latency/run_coverage_replay.py'),'--app',str(app),'--commit','f1d3a846','--isolate-adapters','--output-root',str(root)],stdout=log,stderr=subprocess.STDOUT,check=True,cwd=repo)
subprocess.run([sys.executable,str(repo/'tools/compile_latency/report_coverage.py'),'--root',str(root)],check=True,cwd=repo)
subprocess.run([sys.executable,str(repo/'tools/compile_latency/audit_callable_ownership.py'),'--app',str(app),'--isolate-adapters','--out',str(root/'final-ownership-audit.json')],check=True,cwd=repo)
subprocess.run([sys.executable,str(root/'archive_when_finished.py')],check=True,cwd=repo)
