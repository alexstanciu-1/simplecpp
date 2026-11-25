import os,json,subprocess,csv
from pathlib import Path
root=Path('/tmp/scpp-edit-latency-20260920/provider-coverage');app=Path('/tmp/scpp-edit-latency-20260919/app');fixture=root/'fixtures/int64_local_minus_return';reports={}
for label in ['previous-policy','candidate-restored']:
 out=root/(label+'-diagnostic');out.mkdir(exist_ok=True);rows=out/'rows.tsv'
 env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS=fixture.name,SCPP_V2_BUILD_ROOT=str(out/'build'),SCPP_V2_PROJECT_RUN_ROWS_TSV=str(rows))
 r=subprocess.run([str(root/(label+'-compiler'))],cwd=app,env=env,text=True,capture_output=True,timeout=30);(out/'pipeline.log').write_text(r.stdout+r.stderr);assert r.returncode==0
 reports[label]=list(csv.DictReader(rows.read_text().splitlines(),delimiter='\t'));assert len(reports[label])==1
assert reports['previous-policy']==reports['candidate-restored'],reports
(root/'paired-diagnostic.json').write_text(json.dumps({'reports':reports,'equal':True,'scope':'existing blocked-layer/reason export from two retained compiler executables; no instrumentation'},indent=2)+'\n');print(json.dumps(reports))
