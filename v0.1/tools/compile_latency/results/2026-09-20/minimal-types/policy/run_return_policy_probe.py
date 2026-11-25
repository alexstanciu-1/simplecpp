#!/usr/bin/env python3
"""Historical-shaped parser signature propagation, not a historical patch replay."""
import argparse,difflib,hashlib,json,os,re,subprocess
from pathlib import Path
from expanded_layout import prepare
from experiment import require_scratch,measure
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);a=p.parse_args()
app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];out=app.parent/'return-policy-results';out.mkdir(exist_ok=True)
front='compile/frontend_adapter/phs/frontend_model_builder.phs'
methods={'parse_function_body','parse_if_statement','parse_while_statement','parse_for_statement','parse_return_expression','parse_return_statement','parse_body_statement','parse_statement_list_until_close','parse_statement_list_until_close_with_implicit_return'}
pattern=re.compile(r'frontend_model_builder::('+ '|'.join(sorted(methods,key=len,reverse=True))+r')\(')
original={};edits={};calls=[]
for path in app.rglob('*.phs'):
 if '.prism' in path.parts:continue
 text=path.read_text()
 if not pattern.search(text) and str(path.relative_to(app))!=front:continue
 key=str(path.relative_to(app));owner='';lines=[]
 for line in text.splitlines(True):
  decl=re.search(r'public static function (\w+)\(',line)
  if decl:owner=decl[1]
  if key==front and decl and owner in methods:
   assert '): uint32 {' in line or '): void {' in line,line
   line=line.replace('):',', bool $latencyAllowReturns):',1)
   if owner=='parse_return_expression':
    line+='\t\tif (!$latencyAllowReturns) { parser_state::diagnostic($state); }\n'
  match=pattern.search(line)
  if match:
   assert len(pattern.findall(line))==1 and line.rstrip().endswith(');'),line
   end=line.rfind(')');args=line[match.end():end];source=args.split(',')[1].strip();assert source in ('$source','$sourceText'),line
   arg='$latencyAllowReturns' if key==front and owner in methods else '!str_starts_with('+source+', "// latency deny returns")'
   line=line[:end]+', '+arg+line[end:];calls.append({'source':key,'owner':owner,'callee':match[1],'argument':arg})
  lines.append(line)
 changed=''.join(lines)
 if changed!=text:original[key]=text;edits[key]=changed
assert len(original)==2,original.keys()
fixtures=out/'fixtures'
# Supported current local-return fixture; historical int64-plus is blocked by the current backend.
for label,prefix in [('allowed',''),('denied','// latency deny returns\n')]:
 d=fixtures/label;d.mkdir(parents=True,exist_ok=True)
 (d/'main.phs').write_text(prefix+'function run(): int32 {\n $x int32 = 42;\n return $x;\n}\n')
 (d/'project.manifest').write_text('entry=run\nexpected_result=42\nsource=main.phs\n')
corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja';mirror=app/'.prism/expanded/generated'
def generate():prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True)
def pipeline(label,fixture,accepted):
 env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};dest=out/(label+'-fixture-build')
 env.update(SCPP_V2_PROJECT_ROOT=str(fixtures),SCPP_V2_RUN_LABELS=fixture,SCPP_V2_BUILD_ROOT=str(dest),SCPP_V2_PROJECT_RUN_ROWS_TSV=str(out/(label+'.rows.tsv')))
 r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,text=True,capture_output=True,timeout=30);(out/(label+'.pipeline.log')).write_text(r.stdout+r.stderr);assert r.returncode==0
 if not accepted:
  assert 'compiler_project_runner_blocked=1' in r.stdout.splitlines(),r.stdout
  return
 assert 'compiler_project_runner_completed=1' in r.stdout.splitlines(),r.stdout
 llvm=dest/f'phs_{fixture}_native/compiler.ll';text=llvm.read_text();assert 'define i32 @scpp_run()' in text and 'ret i32' in text,text
 r=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(dest/'fixture')],capture_output=True,text=True);(out/(label+'.llvm-link.log')).write_text(r.stdout+r.stderr);assert r.returncode==0
 r=subprocess.run([str(dest/'fixture')],capture_output=True);assert r.returncode==42
 (out/(label+'.execution.json')).write_text(json.dumps({'exit_code':42,'llvm':str(llvm)},indent=2))
try:
 generate();warm(app,config);pipeline('baseline-allowed','allowed',True);pipeline('baseline-denied-marker-accepted','denied',True)
 before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
 for key,text in edits.items():(app/key).write_text(text);regen(repo,app,key)
 generate();changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
 (out/'source-edit.patch').write_text(''.join(''.join(difflib.unified_diff(original[k].splitlines(True),edits[k].splitlines(True),fromfile='before/'+k,tofile='after/'+k)) for k in original))
 outputs=None
 for n in range(3):
  for p in changed:p.touch()
  label=f'return-policy-r{n}';measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label);pipeline(label+'-allowed','allowed',True);pipeline(label+'-denied','denied',False)
  row=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current=sorted(x['output'] for x in row['native_steps']);assert not any(x.endswith(('.pch','.gch')) for x in current)
  if outputs is None:outputs=current
  assert current==outputs
 (out/'manifest.json').write_text(json.dumps({'scope':'current-workload analogue of e1b50b4e signature propagation; NOT the full historical patch','sources':list(original),'changed_signatures':sorted(methods),'changed_calls':calls,'native_outputs':outputs,'changed_generated_inputs':[str(p.relative_to(mirror)) for p in changed],'behavior':'baseline accepts both fixtures; edited compiler accepts ordinary int32 local-return fixture (LLVM exit42), rejects marked fixture via propagated return policy','timing':'native compiler rebuild only; fixture compilation/execution are separate correctness checks'},indent=2))
finally:
 for key,text in original.items():(app/key).write_text(text);regen(repo,app,key)
 generate();warm(app,config);verify(app,corpus,out,'restored')
