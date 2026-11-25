#!/usr/bin/env python3
"""Fixed-output history coverage screen; retains slow and incompatible cases."""
import argparse,hashlib,json,os,subprocess,time
from pathlib import Path
from coverage_sample import SAMPLE,reverse_sources
from expanded_layout import prepare
from experiment import require_scratch,measure
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--commit',required=True);p.add_argument('--uniform-helpers',action='store_true');p.add_argument('--isolate-adapters',action='store_true');p.add_argument('--isolate-composition-type',action='store_true');p.add_argument('--output-root',type=Path);a=p.parse_args();app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];out=(a.output_root or app.parent/'coverage-study')/a.commit;out.mkdir(parents=True,exist_ok=True)
if (out.parent/'STOP').exists():
 raise SystemExit('Study stopped between restored cases: ' + str(out.parent/'STOP'))
old=reverse_sources(app,(SAMPLE/(a.commit+'.patch')).read_text());original={k:(app/k).read_text() for k in old};corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja';mirror=app/'.prism/expanded/generated';status={'commit':a.commit,'sources':list(old),'protocol':'exact hunks in current surroundings; fixed output strategy; 3 trials unless first native build exceeds20s; no optimization during this study','phase':'baseline','result':'running'}
status['uniform_helpers']=a.uniform_helpers
status['isolate_adapters']=a.isolate_adapters
status['isolate_composition_type']=a.isolate_composition_type
def save(): (out/'status.json').write_text(json.dumps(status,indent=2)+'\n')
def generate():prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True,uniform_helpers=a.uniform_helpers,isolate_adapters=a.isolate_adapters,isolate_composition_type=a.isolate_composition_type)
def check(label):
 verify(app,corpus,out,label)
 fixture=out/'fixtures/literal';fixture.mkdir(parents=True,exist_ok=True);(fixture/'main.phs').write_text('function run(): int32 { return 42; }\n');(fixture/'project.manifest').write_text('entry=run\nexpected_result=42\nsource=main.phs\n')
 dest=out/(label+'-fixture');env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS='literal',SCPP_V2_BUILD_ROOT=str(dest))
 r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,text=True,capture_output=True,timeout=30);(out/(label+'.pipeline.log')).write_text(r.stdout+r.stderr);assert r.returncode==0 and 'compiler_project_runner_completed=1' in r.stdout.splitlines()
 llvm=dest/'phs_literal_native/compiler.ll';assert 'ret i32 42' in llvm.read_text()
 r=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(dest/'fixture')],text=True,capture_output=True);assert r.returncode==0,r.stderr
 assert subprocess.run([str(dest/'fixture')],capture_output=True).returncode==42
save()
try:
 for k,text in old.items():(app/k).write_text(text);regen(repo,app,k)
 generate();started=time.perf_counter();warm(app,config);status['before_hunk_setup_seconds']=time.perf_counter()-started;check('before');status['phase']='forward_edit';save()
 old_objects=set(next(l for l in (app/'.prism/build'/config).read_text().splitlines() if l.startswith('build main: link ')).split()[3:])
 before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
 for k,text in original.items():(app/k).write_text(text);regen(repo,app,k)
 generate();changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
 new_objects=set(next(l for l in (app/'.prism/build'/config).read_text().splitlines() if l.startswith('build main: link ')).split()[3:])-old_objects
 status['forced_new_objects']=sorted(new_objects)
 status['changed_generated_inputs']=[str(p.relative_to(mirror)) for p in changed];outputs=None
 for trial in range(3):
  for p in changed:p.touch()
  for obj in new_objects:
   if obj.endswith('.o'):(app/'.prism/build'/obj).unlink(missing_ok=True)
  label=f'edit-{trial}';measure(app,config,label,out,[],jobs=12);check(label)
  row=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current=sorted(x['output'] for x in row['native_steps'])
  if outputs is None:outputs=current
  assert outputs==current
  if row['native_wall_seconds']>20:status['early_stop']='one successful screen above20s; not a median';break
 status['result']='measured';status['validation']='full link, before/after smoke and literal LLVM exit42; not exhaustive changed-feature validation';save()
except (Exception,SystemExit) as e:
 status['result']='replay_incompatible';status['error']=str(e);output=getattr(e,'output',None)
 if output:(out/'failure.log').write_text(str(output))
 save()
finally:
 for k,text in original.items():(app/k).write_text(text);regen(repo,app,k)
 generate();warm(app,config);verify(app,corpus,out,'restored');status['restored']=True;save()
