import sys,subprocess,time,json
from pathlib import Path
repo=Path('/home/alexv/__AI/simple_cpp/simple_cpp_01');root=Path('/tmp/scpp-edit-latency-20260920');app=Path('/tmp/scpp-edit-latency-20260919/app')
def run(args,log):
 with (root/log).open('w') as f:subprocess.run([sys.executable,*args],cwd=repo,stdout=f,stderr=subprocess.STDOUT,check=True)
sys.path.insert(0,str(repo/'tools/compile_latency'))
from expanded_layout import prepare
from run_corpus import warm,verify
prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True,isolate_adapters=True,isolate_composition_type=True,isolate_shared_carriers=True,isolate_value_traits=True,isolate_provider_traits=True)
start=time.perf_counter();warm(app,'latency-expanded.ninja');elapsed=time.perf_counter()-start
(root/'provider-conversion.json').write_text(json.dumps({'resumed_conversion_native_seconds':elapsed,'incremental_trial':False,'scope':'completion after retained failed conversion; not full cold conversion time'})+'\n')
verify(app,json.loads((app.parent/'corpus.json').read_text()),root,'provider-conversion')
print('provider_conversion_passed',elapsed,flush=True)
run(['tools/compile_latency/run_compound_value_probe.py','--app',str(app),'--out',str(root/'compound-private-provider'),'--private-provider'],'compound-private-driver.log')
print('private_compound_restored',flush=True)
