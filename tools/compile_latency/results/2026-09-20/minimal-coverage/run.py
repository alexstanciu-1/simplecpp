import hashlib,json,subprocess,sys,shutil
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260920/minimal-coverage');app=Path('/tmp/scpp-edit-latency-20260919/app');tools=repo/'tools/compile_latency'
policies=['minimal_type_layout.py','run_coverage_replay.py','expanded_layout.py','type_publication.py','run_corpus.py'];hashes={n:hashlib.sha256((tools/n).read_bytes()).hexdigest() for n in policies};(root/'policy-hashes.json').write_text(json.dumps(hashes,indent=2)+'\n')
samples=[('first-15',tools/'results/2026-09-20/provider-coverage/eligibility.json',tools/'results/2026-09-19/historical-patterns/recent-source-sample'),('next-15',tools/'results/2026-09-20/history-expansion/coverage-16-30/eligibility.json',tools/'results/2026-09-20/history-expansion/source-sample-16-30')]
for label,eligibility,sample in samples:
 out=root/label;out.mkdir(exist_ok=False);shutil.copy2(eligibility,out/'eligibility.json')
 for case in json.loads(eligibility.read_text())['commits']:
  if not case['textual_replay_eligible']:continue
  commit=case['commit'];print('START',label,commit,flush=True)
  with (out/(commit+'-driver.log')).open('w') as log:
   r=subprocess.run([sys.executable,str(tools/'run_coverage_replay.py'),'--app',str(app),'--commit',commit,'--isolate-adapters','--isolate-composition-type','--isolate-shared-carriers','--isolate-value-traits','--isolate-provider-traits','--minimal-types','--output-root',str(out),'--sample-dir',str(sample)],cwd=repo,stdout=log,stderr=subprocess.STDOUT)
  r.check_returncode();state=json.loads((out/commit/'status.json').read_text());assert state['restored'];assert all(hashlib.sha256((tools/n).read_bytes()).hexdigest()==h for n,h in hashes.items());print('DONE',commit,state['result'],flush=True)
  subprocess.run([sys.executable,str(tools/'report_coverage.py'),'--root',str(out)],check=True)
print('COVERAGE COMPLETE',flush=True)
