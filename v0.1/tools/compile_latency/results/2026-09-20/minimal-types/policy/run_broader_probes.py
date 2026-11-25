#!/usr/bin/env python3
"""Bounded numeric mapping edits and new-native-unit helper extraction."""
import argparse, hashlib, json, re
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as prepare_parts
from layout_isolation import prepare as prepare_layout
from experiment import measure, require_scratch, write_changed
from run_corpus import regen, warm, verify

p=argparse.ArgumentParser()
p.add_argument('--app',type=Path,required=True)
p.add_argument('--jobs',type=int,default=12)
args=p.parse_args(); app=args.app.resolve(); require_scratch(app)
repo=Path(__file__).resolve().parents[2]; build=app/'.prism/build'
mirror=app/'.prism/layout/generated'; out=app.parent/'broader-edit-results';out.mkdir(exist_ok=True)
corpus=json.loads((app.parent/'corpus.json').read_text())
config='latency-layout.ninja'
def generate():
    prepare(app);prepare_parts(app);prepare_layout(app)
def snapshot():
    return {p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
def run(label, changed, expected, graph=config):
    work=None
    for trial in range(3):
        for path in changed:path.touch()
        name=f'{label}-r{trial}'
        measure(app,graph,name,out,[],jobs=args.jobs)
        row=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1])
        outputs={s['output'] for s in row['native_steps']}
        if work is None:work=outputs
        assert outputs==work
        verify(app,corpus,out,name)
        assert expected in (out/(name+'.run.log')).read_text().splitlines()
    return sorted(work)

cases=[('compile/model/type_ref_identity.phs','public static function name(uint32 $typeRefId): string {','$typeRefId','int24_probe'),
('compile/capabilities/type_traits.phs','public static function numeric_kind_name(uint16 $numericKindId): string {','$numericKindId','signed_integer_probe'),
('compile/backend/primitive_abi_adapter_matrix.phs','public static function llvm_type_for_abi_shape(uint16 $abiShapeId): string {','$abiShapeId','i24')]
originals={key:(app/key).read_text() for key,_,_,_ in cases};originals['main.phs']=(app/'main.phs').read_text()
manifest={}
try:
    generate();warm(app,config);before=snapshot()
    for key,signature,var,value in cases:
        text=originals[key]; assert text.count(signature)==1
        (app/key).write_text(text.replace(signature,signature+f'\n\t\tif ((int){var} === 32760) {{ return "{value}"; }}'))
        regen(repo,app,key)
    (app/'main.phs').write_text(originals['main.phs']+'''\necho "numeric_probe=", type_ref_identity::name(structure_row_ids::uint32_from_int(32760)), ":", type_traits::numeric_kind_name(structure_row_ids::uint16_from_int(32760)), ":", primitive_abi_adapter_matrix::llvm_type_for_abi_shape(structure_row_ids::uint16_from_int(32760)), "\\n";\n''')
    regen(repo,app,'main.phs');generate()
    changed=[p for p,h in snapshot().items() if before.get(p)!=h]
    manifest['numeric_mapping']={'scope':'Three real numeric mapping bodies plus main witness; analogous edit, not complete new numeric-type implementation','source_files':list(originals),'generated_inputs':[str(p.relative_to(mirror)) for p in changed],'outputs':run('numeric-mapping',changed,'numeric_probe=int24_probe:signed_integer_probe:i24')}
finally:
    for key,text in originals.items():(app/key).write_text(text);regen(repo,app,key)
    generate();warm(app,config)

# Native extraction tests a future emitter adding a TU without perturbing stable
# type discovery/PCH. It does not exercise today's new-PHS-file discovery process.
try:
    (app/'main.phs').write_text(originals['main.phs']+'\necho "extraction_probe=", production_mt_heartbeat::key("probe", "rows"), "\\n";\n')
    regen(repo,app,'main.phs');generate();warm(app,config)
    source=mirror/'__partitions/compile/support/production_mt_heartbeat/part_0.cpp'
    text=source.read_text()
    signature='string_t production_mt_heartbeat::key(const string_t& stage, const string_t& suffix) {'
    start=text.index(signature);end=text.index('\n}',start)+2
    method=text[start:end];body=method[method.index('\n\treturn '):method.rindex('\n}')]
    decl='string_t __latency_extracted_heartbeat_key(const string_t& stage, const string_t& suffix);'
    rewritten=method.replace(body,'\n\treturn __latency_extracted_heartbeat_key(stage, suffix);')
    write_changed(source,text[:start]+decl+'\n'+rewritten+text[end:])
    helper=mirror/'__latency_extracted_heartbeat.cpp'
    write_changed(helper,'namespace scpp {\n'+decl[:-1]+' {'+body+'\n}\n}\n')
    graph=(build/config).read_text()
    graph=re.sub(r'^build main: link ', 'build main: link layout_parts/extracted_heartbeat.o ',graph,flags=re.M)
    graph+='\nbuild layout_parts/extracted_heartbeat.o: compile_callable_part '+str(helper)+' | layout-project.pch runtime_signature.txt\n'
    write_changed(build/'latency-extraction.ninja',graph)
    manifest['native_helper_extraction']={'scope':'Extract real MT heartbeat key construction to a new native implementation file; existing public class and PCH unchanged; witness prepared before timing','outputs':run('native-helper-extraction',[source,helper],'extraction_probe=production_mt_heartbeat_probe_rows','latency-extraction.ninja')}
finally:
    (app/'main.phs').write_text(originals['main.phs']);regen(repo,app,'main.phs')
    (mirror/'__latency_extracted_heartbeat.cpp').unlink(missing_ok=True)
    generate();warm(app,config);verify(app,corpus,out,'restored')
(out/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
