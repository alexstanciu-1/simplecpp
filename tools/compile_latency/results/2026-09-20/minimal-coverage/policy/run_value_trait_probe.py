#!/usr/bin/env python3
"""Screen actual by-value trait layout propagation on the solutions layout."""
import argparse,difflib,json,hashlib
from pathlib import Path
from expanded_layout import prepare
from experiment import require_scratch,measure
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--isolate-value-traits',action='store_true');p.add_argument('--output-root',type=Path);a=p.parse_args();app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];out=a.output_root or app.parent/'value-trait-results';out.mkdir(exist_ok=True)
row='structures/capabilities/type_trait_row.phs';writer='compile/capabilities/type_traits.phs';keys=[row,writer,'main.phs'];original={k:(app/k).read_text() for k in keys};corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja'
def generate():prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True,isolate_adapters=a.isolate_value_traits,isolate_composition_type=a.isolate_value_traits,isolate_shared_carriers=a.isolate_value_traits,isolate_value_traits=a.isolate_value_traits)
try:
 generate();warm(app,config)
 mirror=app/'.prism/expanded/generated'
 before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
 text=original[row];needle='struct TypeTraitRow {';assert text.count(needle)==1
 (app/row).write_text(text.replace(needle,needle+'\n\tpublic uint32 $latency_integer_width = 0;',1))
 text=original[writer];needle='$row->integer_width_bits = $descriptor->integer_width_bits;';assert text.count(needle)==1
 (app/writer).write_text(text.replace(needle,needle+'\n\t\t$row->latency_integer_width = structure_row_ids::uint32_from_int((int)$descriptor->integer_width_bits);',1))
 witness='''
$latency_trait TypeTraitRow = type_traits::row_from_type_ref_id(type_refs::int32_id());
$latency_copy TypeTraitRow = $latency_trait;
$latency_copy->latency_integer_width = structure_row_ids::uint32_from_int(99);
$latency_table TypeTraitTable = [];
$latency_table->traits[] = $latency_trait;
$latency_stored TypeTraitRow = $latency_table->traits[0];
$latency_table_copy TypeTraitTable = $latency_table;
$latency_table_copy->traits[] = $latency_copy;
echo "value_table=", count($latency_table->traits), ":", count($latency_table_copy->traits), "\\n";
echo "value_trait=", (int)$latency_trait->latency_integer_width, ":", (int)$latency_copy->latency_integer_width, ":", (int)$latency_stored->latency_integer_width, "\\n";
'''
 (app/'main.phs').write_text(original['main.phs']+witness)
 (out/'source-edit.patch').write_text(''.join(''.join(difflib.unified_diff(original[k].splitlines(True),(app/k).read_text().splitlines(True),fromfile='before/'+k,tofile='after/'+k)) for k in keys))
 for key in keys:regen(repo,app,key)
 generate()
 changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
 outputs=None
 for trial in range(3 if a.isolate_value_traits else 1):
  for path in changed:path.touch()
  label='value-trait-layout-'+str(trial)
  measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label)
  lines=(out/(label+'.run.log')).read_text().splitlines()
  assert 'value_trait=32:99:32' in lines and 'value_table=1:2' in lines
  result=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current=sorted(x['output'] for x in result['native_steps'])
  if outputs is None:outputs=current
  assert outputs==current
  if result['native_wall_seconds']>20:break
 (out/'manifest.json').write_text(json.dumps({'sources':keys,'scope':'single screening of one by-value record field plus real writer/copy/vector readers; narrower than historical 7d58f273 three-record layout change','expected_witness':'value_trait=32:99:32','representation':'original by-value struct and vector; no shared-pointer conversion or accessor boundary'},indent=2))
finally:
 for key,text in original.items():(app/key).write_text(text);regen(repo,app,key)
 generate();warm(app,config);verify(app,corpus,out,'restored')
