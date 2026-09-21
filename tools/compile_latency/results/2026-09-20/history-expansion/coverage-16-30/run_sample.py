import json,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
root=Path('/tmp/scpp-edit-latency-20260920/history-expansion/coverage-16-30');app=Path('/tmp/scpp-edit-latency-20260919/app')
eligibility=json.loads((root/'eligibility.json').read_text())
commits=[c['commit'] for c in eligibility['commits'] if c['textual_replay_eligible']]
for commit in commits:
 print('START',commit,flush=True)
 with (root/(commit+'-driver.log')).open('w') as log:
  result=subprocess.run([sys.executable,str(repo/'tools/compile_latency/run_coverage_replay.py'),'--app',str(app),'--commit',commit,'--isolate-adapters','--isolate-composition-type','--isolate-shared-carriers','--isolate-value-traits','--isolate-provider-traits','--output-root',str(root),'--sample-dir',str(root.parent/'source-sample-16-30')],stdout=log,stderr=subprocess.STDOUT,cwd=repo)
 if result.returncode:raise SystemExit('Driver failed: '+commit)
 state=json.loads((root/commit/'status.json').read_text());assert state.get('restored'),state
 print('DONE',commit,state['result'],flush=True)
 subprocess.run([sys.executable,str(repo/'tools/compile_latency/report_coverage.py'),'--root',str(root)],check=True,cwd=repo)
