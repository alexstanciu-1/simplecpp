import sys,json
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_artifacts import materialize_artifacts
root=Path(__file__).parent;out=root/'batch19';out.mkdir()
with CommandRunner(out/'logs',repo,jobs=12) as runner:
 def probe(n):
  d=out/n;d.mkdir();r=json.loads(runner.run(n,['php',root/'stale.php',d,n]));sources=materialize_artifacts((d/'stale-artifacts.json').read_text(),d/'cpp')
  for i,s in enumerate(sources):runner.run(n+'-clang-'+str(i),['clang++','-std=c++20','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',repo/'runtime/include','-I',d/'cpp','-fsyntax-only',s])
  r['stale_cpp_clang']='passed';(d/'result.json').write_text(json.dumps(r,indent=2));print(n,json.dumps(r));return r
 results=run_tasks([Task(n,lambda n=n:probe(n)) for n in ['semantic','syntax']],jobs=12,report=False)
(out/'summary.json').write_text(json.dumps({r.name:r.value for r in results},indent=2))
