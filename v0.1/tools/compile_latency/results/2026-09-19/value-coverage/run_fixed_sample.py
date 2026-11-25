import json,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
root=Path('/tmp/scpp-edit-latency-20260919/value-coverage');app=root.parent/'app'
eligibility=json.loads((root.parent/'coverage-study/eligibility.json').read_text())
(root/'eligibility.json').write_text(json.dumps(eligibility,indent=2)+'\n')
commits=['e8e8a063','43a83246','1a24a85d','5ce42535','cf3c2d08','534d70a6','1610af1a','f1d3a846','b54f2766','cec8c616','4abe635f']
assert set(commits)=={c['commit'] for c in eligibility['commits'] if c['textual_replay_eligible']}
for commit in commits:
 print('START',commit,flush=True)
 with (root/(commit+'-driver.log')).open('w') as log:
  result=subprocess.run([sys.executable,str(repo/'tools/compile_latency/run_coverage_replay.py'),'--app',str(app),'--commit',commit,'--isolate-adapters','--isolate-composition-type','--isolate-shared-carriers','--isolate-value-traits','--output-root',str(root)],stdout=log,stderr=subprocess.STDOUT,cwd=repo)
 if result.returncode:raise SystemExit('Driver failed: '+commit)
 state=json.loads((root/commit/'status.json').read_text());assert state.get('restored'),state
 print('DONE',commit,state['result'],flush=True)
 subprocess.run([sys.executable,str(repo/'tools/compile_latency/report_coverage.py'),'--root',str(root)],check=True,cwd=repo)
