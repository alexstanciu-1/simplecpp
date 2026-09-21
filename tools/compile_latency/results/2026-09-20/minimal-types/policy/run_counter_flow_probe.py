#!/usr/bin/env python3
"""Shared row field plus actual profile writer and gate-summary reader."""
import argparse,hashlib,json
from pathlib import Path
from expanded_layout import prepare
from experiment import require_scratch,measure
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);a=p.parse_args()
app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];config='latency-expanded.ninja'
mirror=app/'.prism/expanded/generated';out=app.parent/'counter-flow-results';out.mkdir(exist_ok=True)
corpus=json.loads((app.parent/'corpus.json').read_text())
row='structures/artifacts/compiler_profile_event_row.phs';writer='compile/support/compiler_profile_events.phs';reader='profile/gate_profile_helpers.phs'
original={k:(app/k).read_text() for k in [row,writer,reader,'main.phs']}
def snapshot():return {p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
try:
 prepare(app);warm(app,config);before=snapshot()
 (app/row).write_text(original[row].replace('struct CompilerProfileEventRow {','struct CompilerProfileEventRow {\n\tpublic uint32 $processed_count = 0;',1))
 needle='$row->row_count = $rowCount;';assert original[writer].count(needle)==1
 (app/writer).write_text(original[writer].replace(needle,needle+'\n\t\t$row->processed_count = structure_row_ids::uint32_from_int((int)$rowCount + 1);'))
 needle='$event->row_count,\n\t\t\t$event->row_count,';assert original[reader].count(needle)==1
 (app/reader).write_text(original[reader].replace(needle,'$event->row_count,\n\t\t\t$event->processed_count,'))
 (app/'main.phs').write_text(original['main.phs']+'''
$counter_report CompilerProjectRunReport = new CompilerProjectRunReport();
compiler_profile_events::append_event($counter_report, structure_row_ids::uint32_from_int(1), structure_row_ids::uint16_from_int(1), structure_row_ids::uint32_from_int(7), structure_row_ids::uint32_from_int(10), structure_row_ids::uint16_from_int(1));
$counter_event CompilerProfileEventRow = $counter_report->profile_events[0];
$counter_summary DebugProfileStageTimingSummary = gate_profile_helpers::from_profile_event(structure_row_ids::uint32_from_int(1), $counter_event);
echo "counter_flow=", (int)$counter_event->processed_count, ":", (int)$counter_summary->input_count, ":", (int)$counter_summary->output_count, "\\n";
''')
 for k in original:regen(repo,app,k)
 prepare(app);changed=[p for p,h in snapshot().items() if before.get(p)!=h]
 (out/'manifest.json').write_text(json.dumps({'sources':list(original),'changed_generated_inputs':[str(p.relative_to(mirror)) for p in changed],'scope':'real by-value field and existing writer/reader; observed data flows through profile event vector into gate summary','jobs':12,'cache':'disabled'},indent=2))
 outputs=None
 for trial in range(3):
  for p in changed:p.touch()
  label=f'counter-flow-r{trial}';measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label)
  assert 'counter_flow=8:7:8' in (out/(label+'.run.log')).read_text().splitlines()
  r=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current={s['output'] for s in r['native_steps']}
  if outputs is None:outputs=current
  assert current==outputs
 (out/'verified-work-set.json').write_text(json.dumps({'outputs':sorted(outputs)},indent=2))
finally:
 for k,s in original.items():(app/k).write_text(s);regen(repo,app,k)
 prepare(app);warm(app,config);verify(app,corpus,out,'restored')
