"""Focused native journal addition/removal recovery and the complete accounting CLI contract."""
from pathlib import Path
import importlib.util,json,subprocess,sys
BASE=Path(__file__).resolve().parent;REPO=BASE.parent/'repo';APP=BASE/'accounting';EXE=BASE/'phpp/.prism/build/main'
sys.path.insert(0,str(REPO/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
out=BASE/'validation';out.mkdir()
with CommandRunner(out/'logs',REPO,jobs=12) as runner:
 def publication():
  extra=APP/'src/ninja_publication_probe.phs'
  try:
   for name in ['add','remove']:
    if name=='add':extra.write_text('function ninja_publication_probe(): int { return 17; }\n')
    else:extra.unlink()
    folder=out/name;folder.mkdir();(folder/'marks').mkdir()
    request=json.loads((BASE/'measurements/full/request.json').read_text());request['marks']=str(folder/'marks');request['reuse']=True
    (folder/'request.json').write_text(json.dumps(request))
    runner.run(name,[EXE],cwd=folder,timeout=180)
    manifest=json.loads((BASE/'published/.scpp-output.manifest').read_text())
    units=sum(row['kind']=='translation_unit' for row in manifest['files'])
    assert units==(94 if name=='add' else 93),(name,units)
  finally:extra.unlink(missing_ok=True)
 run_tasks([Task('native-publication-add-remove',publication)])
 sys.path.insert(0,str(APP));spec=importlib.util.spec_from_file_location('accounting_cases',APP/'test_cli.py');module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
 def check(row):
  name,args,expected,code=row
  actual=runner.run('cli-'+name,[BASE/'app-build/app',*args],expected=code,timeout=60)
  assert actual.decode()==expected,(name,actual,expected)
 rows=module.cases();results=run_tasks([Task(row[0],lambda row=row:check(row)) for row in rows],report=False)
 (out/'summary.json').write_text(json.dumps(dict(native_publication_add_remove=True,cli_checks=len(results),all_passed=True),indent=2))
 print(f'PASS: native publication add/remove; {len(results)} accounting CLI checks')
