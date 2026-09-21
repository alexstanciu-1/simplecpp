#!/usr/bin/env python3
"""Move real MT gate implementation into a new PHS owner and redirect all callers."""
import argparse,hashlib,json,re
from pathlib import Path
from expanded_layout import prepare as prepare_expanded
from experiment import measure,require_scratch,write_changed
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--partition-consumers',action='store_true');a=p.parse_args()
app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];build=app/'.prism/build';mirror=app/'.prism/expanded/generated'
out=app.parent/('multiconsumer-partitioned-results' if a.partition_consumers else 'multiconsumer-extraction-results');out.mkdir(exist_ok=True);corpus=json.loads((app.parent/'corpus.json').read_text())
owner='compile/support/mt_publication_metrics.phs';new='compile/support/latency_publication_gate.phs'
needle='mt_publication_metrics::record_worker_gate('
originals={str(p.relative_to(app)):p.read_text() for p in app.rglob('*.phs') if '.prism' not in p.parts and needle in p.read_text()}
originals[owner]=(app/owner).read_text();originals['main.phs']=(app/'main.phs').read_text()
assert not (app/new).exists()
def prepare(app):prepare_expanded(app,partition_consumers=a.partition_consumers)
try:
 prepare(app);warm(app,'latency-expanded.ninja')
 before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
 text=originals[owner];start=text.index('\tpublic static function record_worker_gate(')
 # This bounded source's final method has no code after it except the class close.
 end=text.rfind('\n\t}')+3;method=text[start:end];assert text[end:].strip()=='}'
 signature,body=method.split('): void {',1);args=re.findall(r'\$\w+',signature)
 assert len(args)==12
 wrapper=signature+'): void {\n\t\tlatency_publication_gate::record_worker_gate('+', '.join(args)+');\n\t}'
 (app/owner).write_text(text[:start]+wrapper+text[end:])
 (app/new).write_text('final class latency_publication_gate {\n'+method+'\n}\n')
 consumers=[]
 for key,text in originals.items():
  if key in [owner,'main.phs']:continue
  (app/key).write_text(text.replace(needle,'latency_publication_gate::record_worker_gate('));consumers.append(key)
 witness='''
$publication_report CompilerProjectRunReport = new CompilerProjectRunReport();
latency_publication_gate::record_worker_gate($publication_report, "direct", "blocked", "upstream", "coordinator", "ready", "o3", "speedup", "bytes", true, true, true);
mt_publication_metrics::record_worker_gate($publication_report, "wrapper", "blocked", "upstream", "coordinator", "ready", "o3", "speedup", "bytes", true, false, false);
echo "publication_extraction=", (int)proof_metrics::report_value($publication_report, "direct_ready"), ":", (int)proof_metrics::report_value($publication_report, "direct_blocked"), ":", (int)proof_metrics::report_value($publication_report, "wrapper_blocked"), "\\n";
'''
 (app/'main.phs').write_text(originals['main.phs']+witness)
 for key in [*originals,new]:regen(repo,app,key)
 prepare(app)
 includes=[];header=new.replace('.phs','.hpp')
 for path in mirror.rglob('*.cpp'):
  if 'latency_publication_gate::' in path.read_text():
   write_changed(path,'#include "'+header+'"\n'+path.read_text());includes.append(str(path.relative_to(mirror)))
 graph=(build/'latency-expanded.ninja').read_text()
 graph=re.sub(r'^build main: link ','build main: link expanded_parts/latency_publication_gate.o ',graph,flags=re.M)
 graph+='\nbuild expanded_parts/latency_publication_gate.o: compile_callable_part '+str(mirror/new.replace('.phs','.cpp'))+' | expanded-project.pch runtime_signature.txt\n'
 config='latency-multiconsumer.ninja';write_changed(build/config,graph)
 changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
 (out/'manifest.json').write_text(json.dumps({'owner':owner,'new_source':new,'consumers':consumers,'narrow_includes':includes,'jobs':12,'cache':'disabled','partition_consumers':a.partition_consumers,'scope':'Actual final MT gate body relocated; compatibility wrapper retained; all eight production call sites in seven files redirected; explicit future discovery metadata'},indent=2))
 outputs=None
 for trial in range(3):
  for path in changed:path.touch()
  label=f'multiconsumer-r{trial}';measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label)
  assert 'publication_extraction=1:0:1' in (out/(label+'.run.log')).read_text().splitlines()
  r=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current={x['output'] for x in r['native_steps']}
  if outputs is None:outputs=current
  assert current==outputs
 (out/'verified-work-set.json').write_text(json.dumps({'outputs':sorted(outputs)},indent=2))
finally:
 for key,text in originals.items():(app/key).write_text(text);regen(repo,app,key)
 (app/new).unlink(missing_ok=True)
 for tree in ['generated','callables/generated','layout/generated','expanded/generated']:
  for ext in ['hpp','cpp']:(app/'.prism'/tree/new.replace('.phs','.'+ext)).unlink(missing_ok=True)
 prepare(app);warm(app,'latency-expanded.ninja');verify(app,corpus,out,'restored')
