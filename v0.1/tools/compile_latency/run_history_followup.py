#!/usr/bin/env python3
"""Historical public frontend edit and actual new-PHS helper extraction."""
import argparse,hashlib,json,re
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as parts
from layout_isolation import prepare as layout
from experiment import measure,require_scratch,write_changed
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--case',choices=['frontend','new-source'],required=True)
p.add_argument('--expanded',action='store_true')
a=p.parse_args();app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2]
build=app/'.prism/build';mirror=app/('.prism/expanded/generated' if a.expanded else '.prism/layout/generated');out=app.parent/('history-'+a.case+('-expanded' if a.expanded else '')+'-results');out.mkdir(exist_ok=True)
corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja' if a.expanded else 'latency-layout.ninja'
def generate():
 if a.expanded:
  from expanded_layout import prepare as expanded
  expanded(app)
 else:prepare(app);parts(app);layout(app)
def snapshot():return {x:hashlib.sha256(x.read_bytes()).hexdigest() for x in mirror.rglob('*') if x.is_file()}
keys=['main.phs']+(['compile/frontend_adapter/phs/frontend_model_builder.phs','compile/backend/llvm_text_from_plan.phs'] if a.case=='frontend' else ['compile/support/production_mt_heartbeat.phs'])
originals={k:(app/k).read_text() for k in keys};new='compile/support/latency_heartbeat_format.phs'
try:
 generate();warm(app,config);before=snapshot()
 if a.case=='frontend':
  key=keys[1];s=originals[key];signature='public static function primitive_type_ref_id_for_name(string $typeName): uint32 {'
  helper='\n\tpublic static function latency_primitive_i32_id(): uint32 { return type_refs::int32_id(); }\n'
  s=s.replace('final class frontend_model_builder {','final class frontend_model_builder {'+helper,1)
  assert s.count(signature)==1;s=s.replace(signature,signature+'\n\t\tif ($typeName === "latency_int32") { return frontend_model_builder::latency_primitive_i32_id(); }')
  (app/key).write_text(s)
  key=keys[2];s=originals[key]
  for signature,ret in [('public static function llvm_align_for_type_ref(uint32 $typeRefId): int {','4'),('public static function llvm_type_for_type_ref(uint32 $typeRefId): string {','"i32"')]:
   assert s.count(signature)==1;s=s.replace(signature,signature+'\n\t\tif ((int)$typeRefId === (int)type_refs::int32_id()) { return '+ret+'; }')
  (app/key).write_text(s)
  witness='\n$latency_type uint32 = frontend_model_builder::primitive_type_ref_id_for_name("latency_int32");\necho "history_frontend=", (int)$latency_type, ":", llvm_text_from_plan::llvm_align_for_type_ref($latency_type), ":", llvm_text_from_plan::llvm_type_for_type_ref($latency_type), "\\n";\n'
  expected='history_frontend=6:4:i32';rounds=3 if a.expanded else 1
 else:
  assert not (app/new).exists()
  (app/new).write_text('final class latency_heartbeat_format {\n\tpublic static function key(string $stage, string $suffix): string { return "production_mt_heartbeat_" . $stage . "_" . $suffix; }\n}\n')
  key=keys[1];old='return "production_mt_heartbeat_" . $stage . "_" . $suffix;';assert originals[key].count(old)==1
  (app/key).write_text(originals[key].replace(old,'return latency_heartbeat_format::key($stage, $suffix);'))
  witness='\necho "history_extraction=", production_mt_heartbeat::key("probe", "rows"), ":", latency_heartbeat_format::key("direct", "rows"), "\\n";\n'
  expected='history_extraction=production_mt_heartbeat_probe_rows:production_mt_heartbeat_direct_rows';rounds=3
  regen(repo,app,new)
 (app/'main.phs').write_text(originals['main.phs']+witness)
 for key in keys:regen(repo,app,key)
 generate()
 if a.case=='new-source':
  # Explicit future symbol-to-header metadata. No global umbrella/PCH change.
  # Today's discovery pipeline is not under test; new PHS lowering really runs.
  header='compile/support/latency_heartbeat_format.hpp'
  for rel in ['main.cpp','__partitions/compile/support/production_mt_heartbeat/part_0.cpp']:
   path=mirror/rel;write_changed(path,'#include "'+header+'"\n'+path.read_text())
  cpp=mirror/new.replace('.phs','.cpp');write_changed(cpp,'#include "'+header+'"\n'+cpp.read_text())
  graph=(build/config).read_text()
  graph=re.sub(r'^build main: link ','build main: link layout_parts/latency_heartbeat_format.o ',graph,flags=re.M)
  graph+='\nbuild layout_parts/latency_heartbeat_format.o: compile_callable_part '+str(cpp)+' | layout-project.pch runtime_signature.txt\n'
  config='latency-new-source.ninja';write_changed(build/config,graph)
 changed=[path for path,h in snapshot().items() if before.get(path)!=h]
 (out/'manifest.json').write_text(json.dumps({'case':a.case,'historical_commit':'60011d4c' if a.case=='frontend' else '4840c64f','scope':'contemporary analogue; not full historical checkout','source_files':keys+([new] if a.case=='new-source' else []),'changed_generated_inputs':[str(x.relative_to(mirror)) for x in changed],'rounds':rounds,'jobs':12,'cache':'disabled'},indent=2))
 for trial in range(rounds):
  for path in changed:path.touch()
  label=a.case+f'-r{trial}';measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label)
  assert expected in (out/(label+'.run.log')).read_text().splitlines(),expected
  if a.expanded and a.case=='frontend':
   result=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1])
   assert not any(step['output'].endswith(('.pch','.gch')) for step in result['native_steps']), 'Public helper invalidated expanded PCH'
   outputs={step['output'] for step in result['native_steps']}
   if trial==0: expected_outputs=outputs
   assert outputs==expected_outputs, 'Different native work sets across edit trials'
finally:
 for key,s in originals.items():(app/key).write_text(s);regen(repo,app,key)
 if a.case=='new-source':
  (app/new).unlink(missing_ok=True)
  for tree in ['generated','callables/generated','layout/generated']:
   for ext in ['hpp','cpp']:(app/'.prism'/tree/new.replace('.phs','.'+ext)).unlink(missing_ok=True)
 generate();warm(app,'latency-expanded.ninja' if a.expanded else 'latency-layout.ninja');verify(app,corpus,out,'restored')
