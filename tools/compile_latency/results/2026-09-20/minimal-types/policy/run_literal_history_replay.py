#!/usr/bin/env python3
"""Replay the exact 1610af1a source hunk in the current surrounding program."""
import argparse,hashlib,json,os,subprocess
from pathlib import Path
from expanded_layout import prepare
from experiment import measure,require_scratch
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);a=p.parse_args();app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];out=app.parent/'literal-history-results';out.mkdir(exist_ok=True)
key='compile/backend/compiler_entry_backend.phs';path=app/key;original=path.read_text();before_code='\t\t$capabilityCoverage = type_capability_readiness::coverage_from_type_refs_and_literal($typeRefs, $literalNode->node_id, $literal->type_ref_id);'
after_code='\t\t$consumerPlan CapabilityConsumerPlan = capability_consumer_plans::new_plan();\n\t\tsemantic_body_capability_consumers::append_literal_return($consumerPlan, $literalNode->node_id, $literal->type_ref_id);\n\t\t$capabilityCoverage = type_capability_readiness::coverage_from_type_refs_and_consumer_plan($typeRefs, $consumerPlan);'
assert original.count(after_code)==1
corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja';mirror=app/'.prism/expanded/generated'
fixture=out/'fixtures/literal';fixture.mkdir(parents=True,exist_ok=True);(fixture/'main.phs').write_text('function run(): int32 { return 42; }\n');(fixture/'project.manifest').write_text('entry=run\nexpected_result=42\nsource=main.phs\n')
def generate():prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True)
def pipeline(label):
 dest=out/(label+'-fixture-build');env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS='literal',SCPP_V2_BUILD_ROOT=str(dest))
 r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,text=True,capture_output=True,timeout=30);(out/(label+'.pipeline.log')).write_text(r.stdout+r.stderr);assert r.returncode==0 and 'compiler_project_runner_completed=1' in r.stdout.splitlines()
 llvm=dest/'phs_literal_native/compiler.ll';assert 'ret i32 42' in llvm.read_text()
 r=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(dest/'fixture')],capture_output=True,text=True);assert r.returncode==0,r.stderr
 r=subprocess.run([str(dest/'fixture')],capture_output=True);assert r.returncode==42
 (out/(label+'.execution.json')).write_text(json.dumps({'exit_code':42,'llvm':str(llvm)}))
try:
 path.write_text(original.replace(after_code,before_code));regen(repo,app,key);generate();warm(app,config);pipeline('before-historical-hunk')
 before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
 path.write_text(original);regen(repo,app,key);generate();changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
 outputs=None
 for n in range(3):
  for p in changed:p.touch()
  label=f'literal-hunk-r{n}';measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label);pipeline(label)
  r=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current=sorted(x['output'] for x in r['native_steps'])
  if outputs is None:outputs=current
  assert current==outputs and not any(x.endswith(('.pch','.gch')) for x in current)
 (out/'manifest.json').write_text(json.dumps({'commit':'1610af1a689a183f7b8fc5ff795b0dde2dacc276','scope':'exact source hunk replay in current surrounding program, NOT historical toolchain replay','selection':'one of last 15 source-changing non-merge commits before workload revision; no historical latency criterion','source':key,'before':before_code,'after':after_code,'outputs':outputs,'changed_generated_inputs':[str(p.relative_to(mirror)) for p in changed],'proof':'both old and new paths emit int32 literal-return LLVM exiting42'},indent=2))
finally:
 path.write_text(original);regen(repo,app,key);generate();warm(app,config);verify(app,corpus,out,'restored')
