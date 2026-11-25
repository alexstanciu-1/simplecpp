import json,subprocess,sys
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
root=Path('/tmp/scpp-edit-latency-20260919/uniform-coverage')
app=root.parent/'app'
eligibility=json.loads((root.parent/'coverage-study/eligibility.json').read_text())
(root/'eligibility.json').write_text(json.dumps(eligibility,indent=2)+'\n')
# Same corpus and protocol. Prioritize the worst measured case first.
commits=[c['commit'] for c in eligibility['commits'] if c['textual_replay_eligible']]
commits.remove('534d70a6');commits.insert(0,'534d70a6')
for commit in commits:
 print('START',commit,flush=True)
 with (root/(commit+'-driver.log')).open('w') as log:
  result=subprocess.run([sys.executable,str(repo/'tools/compile_latency/run_coverage_replay.py'),'--app',str(app),'--commit',commit,'--uniform-helpers','--output-root',str(root)],stdout=log,stderr=subprocess.STDOUT,cwd=repo)
 if result.returncode:raise SystemExit('Driver failed: '+commit)
 state=json.loads((root/commit/'status.json').read_text())
 assert state.get('restored'),state
 print('DONE',commit,state['result'],flush=True)
 subprocess.run([sys.executable,str(repo/'tools/compile_latency/report_coverage.py'),'--root',str(root)],check=True,cwd=repo)
