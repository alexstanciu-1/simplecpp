import json,os,subprocess
from pathlib import Path
root=Path('/tmp/scpp-edit-latency-20260920');app=Path('/tmp/scpp-edit-latency-20260919/app');out=root/'historical-fixture';fixture=out/'fixtures/int64_local_minus_return';fixture.mkdir(parents=True,exist_ok=True)
for name in ['main.phs','project.manifest']:
 (fixture/name).write_bytes(subprocess.check_output(['git','-C','/home/alexv/__AI/simple_cpp_compiler','show','7d58f273:compiler/v2/fixtures/int64_local_minus_return/'+name]))
env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};build=out/'build';env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS=fixture.name,SCPP_V2_BUILD_ROOT=str(build))
r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,text=True,capture_output=True,timeout=30);(out/'pipeline.log').write_text(r.stdout+r.stderr);assert r.returncode==0 and 'compiler_project_runner_completed=1' in r.stdout.splitlines(),r.stdout
llvm=build/'phs_int64_local_minus_return_native/compiler.ll';assert 'define i64 @scpp_run()' in llvm.read_text()
r=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(build/'fixture')],text=True,capture_output=True);(out/'llvm-link.log').write_text(r.stdout+r.stderr);r.check_returncode();r=subprocess.run([str(build/'fixture')],capture_output=True);assert r.returncode==38
(out/'proof.json').write_text(json.dumps({'fixture_source':'exact 7d58f273 historical fixture','compiler':'current full compiler application under provider-publication policy','native_exit':r.returncode,'expected':38,'scope':'preserved current behavior; not full twelve-source historical replay'},indent=2)+'\n')
print('historical_int64_subtraction_exit=38')
