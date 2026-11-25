import sys,json
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
root=Path(__file__).parent; cases=json.loads((root/'cases18.json').read_text()); out=root/'batch18b';out.mkdir(exist_ok=True)
with CommandRunner(out/'logs',repo,jobs=12) as runner:
 def probe(n,c):
  d=out/n;d.mkdir(exist_ok=True);p=d/'case.json';p.write_text(json.dumps(c));s=runner.run(n,['php',root/'incremental.php',d,p]);r=json.loads(s);(d/'result.json').write_text(json.dumps(r,indent=2));print(n,json.dumps(r.get('edit',r['base'])),flush=True);return r
 results=run_tasks([Task(n,lambda n=n,c=c:probe(n,c)) for n,c in cases.items()],jobs=12,report=False)
(out/'summary.json').write_text(json.dumps({r.name:r.value for r in results},indent=2))
