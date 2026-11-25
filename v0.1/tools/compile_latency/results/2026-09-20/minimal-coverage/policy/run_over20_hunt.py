#!/usr/bin/env python3
"""Screen central public-surface and shared-layout edits on the final layout."""
import argparse,hashlib,json
from pathlib import Path
from expanded_layout import prepare as expanded
from experiment import measure,require_scratch
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--case',choices=['model-table-helper','frontend-counter-layout'],required=True);p.add_argument('--solutions',action='store_true');p.add_argument('--trial',type=int);a=p.parse_args()
app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];out=app.parent/('over20-solutions' if a.solutions else 'over20-hunt')/a.case;out.mkdir(parents=True,exist_ok=True)
if a.trial is not None:out=out/f'trial-{a.trial}';out.mkdir(parents=True,exist_ok=True)
config='latency-expanded.ninja';corpus=json.loads((app.parent/'corpus.json').read_text());mirror=app/'.prism/expanded/generated'
table='structure_kernel/frontend_model_tables.phs';front='compile/frontend_adapter/phs/frontend_model_builder.phs';counter='structure_kernel/frontend_model_kernel_counters.phs'
# Resolve the actual owning source rather than assuming its directory.
if not (app/counter).exists():
 matches=list(app.glob('**/frontend_model_kernel_counters.phs'));matches=[x for x in matches if '.prism' not in x.parts];assert len(matches)==1;counter=str(matches[0].relative_to(app))
keys=[table,'main.phs',front] if a.case=='model-table-helper' else [table,'main.phs',counter]
original={k:(app/k).read_text() for k in keys}
def prepare():expanded(app,partition_consumers=True,isolate_tables=a.solutions,isolate_counters=a.solutions)
try:
 prepare();warm(app,config)
 before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
 if a.case=='model-table-helper':
  text=original[table];needle='final class frontend_model_tables {';assert text.count(needle)==1
  method='\n\tpublic static function latency_update_expression_payload(FrontendModel $model, FrontendExpressionPayloadRow $row, FrontendModelKernelCounters $counters): bool { return frontend_model_tables::update_expression_payload($model, $row, $counters); }\n'
  (app/table).write_text(text.replace(needle,needle+method,1))
  text=original[front];assert text.count('frontend_model_tables::update_expression_payload(')==2
  (app/front).write_text(text.replace('frontend_model_tables::update_expression_payload(','frontend_model_tables::latency_update_expression_payload('))
  call='latency_update_expression_payload';expected='hunt_flow=1:1'
 else:
  text=original[counter];needle='class FrontendModelKernelCounters {';assert text.count(needle)==1
  (app/counter).write_text(text.replace(needle,needle+'\n\tpublic uint32 $latency_expression_updates = 0;',1))
  text=original[table];start=text.index('public static function update_expression_payload(');needle='$counters->update_call_count = $counters->update_call_count + 1;';pos=text.index(needle,start)
  (app/table).write_text(text[:pos]+needle+'\n\t\t$counters->latency_expression_updates = structure_row_ids::uint32_from_int((int)$counters->latency_expression_updates + 1);'+text[pos+len(needle):])
  call='update_expression_payload';expected='hunt_flow=1:1:1'
 witness='''
$hunt_model FrontendModel = new FrontendModel();
$hunt_counters FrontendModelKernelCounters = new FrontendModelKernelCounters();
$hunt_row FrontendExpressionPayloadRow = [];
$hunt_row->payload_id = structure_row_ids::uint32_from_int(1);
$hunt_model->expressions[] = $hunt_row;
$hunt_ok bool = frontend_model_tables::CALL($hunt_model, $hunt_row, $hunt_counters);
echo "hunt_flow=", (int)$hunt_ok, ":", (int)$hunt_counters->update_call_count EXTRA, "\\n";
'''.replace('CALL',call).replace(' EXTRA',', ":", (int)$hunt_counters->latency_expression_updates' if a.case=='frontend-counter-layout' else '')
 (app/'main.phs').write_text(original['main.phs']+witness)
 for key in keys:regen(repo,app,key)
 prepare();changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
 # Repeated addition trials must compile the newly introduced callable rather
 # than reuse its orphaned object from an earlier add/remove cycle.
 if a.solutions and a.case=='model-table-helper':
  (app/'.prism/build/expanded_parts/__callable/frontend_model_tables_latency_update_expression_payload.o').unlink(missing_ok=True)
 (out/'manifest.json').write_text(json.dumps({'case':a.case,'solutions':a.solutions,'trial':a.trial,'new_callable_object_forced_missing':a.solutions and a.case=='model-table-helper','source_files':keys,'changed_generated_inputs':[str(x.relative_to(mirror)) for x in changed],'jobs':12,'cache':'disabled','scope':'one screening measurement on final partitioned layout; analogous central helper or genuine shared-layout change, not historical checkout replay'},indent=2))
 measure(app,config,a.case,out,[],jobs=12);verify(app,corpus,out,a.case)
 assert expected in (out/(a.case+'.run.log')).read_text().splitlines()
finally:
 for key,text in original.items():(app/key).write_text(text);regen(repo,app,key)
 prepare();warm(app,config);verify(app,corpus,out,'restored')
