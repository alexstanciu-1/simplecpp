import sys,json,subprocess,os,time,shutil,hashlib
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260920/provider-coverage');app=Path('/tmp/scpp-edit-latency-20260919/app');sys.path.insert(0,str(repo/'tools/compile_latency'))
from expanded_layout import prepare
from run_corpus import warm,verify
corpus=json.loads((app.parent/'corpus.json').read_text());fixture=root/'fixtures/int64_local_minus_return';fixture.mkdir(parents=True,exist_ok=True)
for name in ['main.phs','project.manifest']:
 (fixture/name).write_bytes(subprocess.check_output(['git','-C','/home/alexv/__AI/simple_cpp_compiler','show','7d58f273:compiler/v2/fixtures/int64_local_minus_return/'+name]))
def check(label):
 env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};build=root/(label+'-fixture');env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS=fixture.name,SCPP_V2_BUILD_ROOT=str(build))
 r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,capture_output=True,text=True,timeout=30);(root/(label+'.pipeline.log')).write_text(r.stdout+r.stderr)
 lines=r.stdout.splitlines();result={'returncode':r.returncode,'completed':[s for s in lines if s.startswith('compiler_project_runner_completed=')],'blocked':[s for s in lines if s.startswith('compiler_project_runner_blocked=')],'llvm_artifacts':[str(p.relative_to(build)) for p in build.rglob('*.ll')] if build.exists() else []}
 (root/(label+'.fixture.json')).write_text(json.dumps(result,indent=2)+'\n');return result
results={'candidate_before':check('candidate-before')}
for label,provider in [('previous-policy',False),('candidate-restored',True)]:
 print('CONVERT',label,flush=True);prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True,isolate_adapters=True,isolate_composition_type=True,isolate_shared_carriers=True,isolate_value_traits=True,isolate_provider_traits=provider)
 start=time.perf_counter();warm(app,'latency-expanded.ninja');elapsed=time.perf_counter()-start
 (root/(label+'.setup.json')).write_text(json.dumps({'native_setup_seconds':elapsed,'incremental_trial':False})+'\n');verify(app,corpus,root,label)
 results[label]=check(label);shutil.copy2(app/'.prism/build/main',root/(label+'-compiler'))
 print('FIXTURE',label,results[label],flush=True)
assert results['candidate_before']==results['previous-policy']==results['candidate-restored'],results
(root/'paired-fixture.json').write_text(json.dumps({'results':results,'equal':True,'scope':'same pinned source and historical fixture, previous value policy versus provider candidate; blocked status predates provider publication'},indent=2)+'\n')
print('PAIRED_FIXTURE_COMPLETE',flush=True)
subprocess.run([sys.executable,str(root/'run_fixed_sample.py')],cwd=repo,check=True)
