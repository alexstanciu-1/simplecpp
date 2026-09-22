import importlib.util,pathlib,json,time
path=pathlib.Path('/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/tests/run.py')
spec=importlib.util.spec_from_file_location('suite',path);suite=importlib.util.module_from_spec(spec);spec.loader.exec_module(suite)
selected=[f for f in suite.PHP_FIXTURES if f.startswith(('03_parse/','04_analyze/','05_generate_code/prepare_backend/'))]
results=[]
for f in selected:
 start=time.monotonic();r=suite.run_fixture(f,180);results.append({'fixture':f,'exit':r.returncode,'seconds':time.monotonic()-start,'stdout':r.stdout,'stderr':r.stderr});print(f,r.returncode,flush=True)
 if r.returncode:break
pathlib.Path('/tmp/scpp-cursor-retained-isolated.json').write_text(json.dumps(results,indent=2)+'\n')
assert len(results)==len(selected) and all(r['exit']==0 for r in results)
