import sys,json
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01')
sys.path.insert(0,str(repo/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_artifacts import materialize_artifacts
root=Path(__file__).parent
cases=json.loads((root/sys.argv[1]).read_text())
out=root/sys.argv[2];out.mkdir()
with CommandRunner(out/'logs',repo,jobs=12) as runner:
 def probe(name,source):
  d=out/name;d.mkdir();(d/'main.phs').write_text(source+'\n')
  status=json.loads(runner.run(name+'-prepare',['php',root/'probe.php',d]))
  if status['stage']=='emitted':
   sources=materialize_artifacts((d/'artifacts.json').read_text(),d/'cpp')
   errors=[]
   for i,s in enumerate(sources):
    try: runner.run(name+'-clang-'+str(i),['clang++','-std=c++20','-Werror=return-type','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',repo/'runtime/include','-I',d/'cpp','-fsyntax-only',s])
    except RuntimeError: errors.append((out/'logs'/f'{name}-clang-{i}.stderr').read_text())
   status['clang']='failed' if errors else 'passed';status['errors']=errors
  (d/'result.json').write_text(json.dumps(status,indent=2))
  print(name+': '+status['stage']+' '+status.get('clang',status.get('error',''))[:160],flush=True)
  return status
 results=run_tasks([Task(n,lambda n=n,s=s:probe(n,s)) for n,s in cases.items()],jobs=12,report=False)
(out/'summary.json').write_text(json.dumps({r.name:r.value for r in results},indent=2))
