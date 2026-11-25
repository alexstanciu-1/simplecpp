import json,os,subprocess
from pathlib import Path
root=Path('/tmp/scpp-edit-latency-20260920');app=Path('/tmp/scpp-edit-latency-20260919/app');out=root/'literal-control';fixture=out/'fixtures/literal';fixture.mkdir(parents=True,exist_ok=True)
(fixture/'main.phs').write_text('function run(): int32 { return 42; }\n')
(fixture/'project.manifest').write_text('entry=run\nexpected_result=42\nsource=main.phs\n')
env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};build=out/'build';env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS=fixture.name,SCPP_V2_BUILD_ROOT=str(build))
r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,text=True,capture_output=True,timeout=30);(out/'pipeline.log').write_text(r.stdout+r.stderr);assert r.returncode==0 and 'compiler_project_runner_completed=1' in r.stdout.splitlines(),r.stdout
llvm=build/'phs_literal_native/compiler.ll';assert 'define i32 @scpp_run()' in llvm.read_text()
r=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(build/'fixture')],text=True,capture_output=True);(out/'llvm-link.log').write_text(r.stdout+r.stderr);r.check_returncode();r=subprocess.run([str(build/'fixture')],capture_output=True);assert r.returncode==42
(out/'proof.json').write_text(json.dumps({'fixture_source':'known literal-return control','compiler':'current full compiler application under provider-publication policy','native_exit':r.returncode,'expected':42,'scope':'preserved current behavior; not full twelve-source historical replay'},indent=2)+'\n')
print('literal_control_exit=42')
