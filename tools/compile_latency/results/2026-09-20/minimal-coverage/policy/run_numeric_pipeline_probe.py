#!/usr/bin/env python3
"""Public numeric spelling + coordinated lowering edit, validated through LLVM execution."""
import argparse,hashlib,json,os,subprocess
from pathlib import Path
from expanded_layout import prepare
from experiment import require_scratch,measure
from run_corpus import regen,warm,verify
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);a=p.parse_args()
app=a.app.resolve();require_scratch(app);repo=Path(__file__).resolve().parents[2];mirror=app/'.prism/expanded/generated';out=app.parent/'numeric-pipeline-results';out.mkdir(exist_ok=True)
fixture=out/'fixtures/alias';fixture.mkdir(parents=True,exist_ok=True)
(fixture/'main.phs').write_text('function run(): latency_i32 {\n\t$x latency_i32 = 42;\n\treturn $x;\n}\n')
(fixture/'project.manifest').write_text('entry=run\nexpected_result=42\nsource=main.phs\n')
corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja'
front='compile/frontend_adapter/phs/frontend_model_builder.phs';back='compile/backend/llvm_text_from_plan.phs';original={k:(app/k).read_text() for k in [front,back]}
def pipeline(label,accepted):
    env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')}
    build=out/(label+'-fixture-build')
    env.update(SCPP_V2_PROJECT_ROOT=str(fixture.parent),SCPP_V2_RUN_LABELS='alias',SCPP_V2_BUILD_ROOT=str(build))
    r=subprocess.run([str(app/'.prism/build/main')],cwd=app,env=env,text=True,capture_output=True,timeout=30)
    (out/(label+'.pipeline.log')).write_text(r.stdout+r.stderr)
    assert r.returncode==0
    if not accepted:
        assert 'compiler_project_runner_blocked=1' in r.stdout.splitlines(),r.stdout
        return
    assert 'compiler_project_runner_completed=1' in r.stdout.splitlines(),r.stdout
    assert 'compiler_project_runner_blocked=0' in r.stdout.splitlines(),r.stdout
    llvm=build/'phs_alias_native/compiler.ll';text=llvm.read_text()
    assert 'define i32 @scpp_run()' in text and 'align 4' in text,text
    result=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(build/'alias-executable')],text=True,capture_output=True)
    (out/(label+'.llvm-link.log')).write_text(result.stdout+result.stderr);assert result.returncode==0
    r=subprocess.run([str(build/'alias-executable')],capture_output=True)
    assert r.returncode==42,r.returncode
    (out/(label+'.execution.json')).write_text(json.dumps({'exit_code':r.returncode,'expected':42,'llvm':str(llvm)},indent=2))
try:
    prepare(app);warm(app,config);pipeline('baseline-rejected',False)
    before={p:hashlib.sha256(p.read_bytes()).hexdigest() for p in mirror.rglob('*') if p.is_file()}
    s=original[front].replace('final class frontend_model_builder {','final class frontend_model_builder {\n\tpublic static function latency_i32_alias_id(): uint32 { return type_refs::int32_id(); }',1)
    for name in ['primitive_type_ref_id_for_name','source_type_ref_id_for_name']:
        needle=f'public static function {name}(string $typeName): uint32 {{';assert s.count(needle)==1
        s=s.replace(needle,needle+'\n\t\tif ($typeName === "latency_i32") { return frontend_model_builder::latency_i32_alias_id(); }')
    (app/front).write_text(s);s=original[back]
    for needle,ret in [('public static function llvm_align_for_type_ref(uint32 $typeRefId): int {','4'),('public static function llvm_type_for_type_ref(uint32 $typeRefId): string {','"i32"')]:
        assert s.count(needle)==1;s=s.replace(needle,needle+'\n\t\tif ((int)$typeRefId === (int)type_refs::int32_id()) { return '+ret+'; }')
    (app/back).write_text(s)
    for k in original:regen(repo,app,k)
    prepare(app);changed=[p for p in mirror.rglob('*') if p.is_file() and hashlib.sha256(p.read_bytes()).hexdigest()!=before.get(p)]
    outputs=None
    for trial in range(3):
        for p in changed:p.touch()
        label=f'numeric-pipeline-r{trial}';measure(app,config,label,out,[],jobs=12);verify(app,corpus,out,label);pipeline(label,True)
        r=json.loads((out/'measurements.jsonl').read_text().splitlines()[-1]);current={x['output'] for x in r['native_steps']}
        assert not any(x.endswith(('.pch','.gch')) for x in current)
        if outputs is None:outputs=current
        assert outputs==current
    (out/'manifest.json').write_text(json.dumps({'sources':list(original),'scope':'new numeric source alias using existing int32 semantics, plus historical-shaped explicit backend mapping branches; not a new integer representation','baseline_alias_rejected':True,'edited_alias_exit_code':42,'outputs':sorted(outputs),'timing':'Ninja through compiler executable; fixture pipeline/LLVM compile/execution excluded as correctness checks'},indent=2))
finally:
    for k,s in original.items():(app/k).write_text(s);regen(repo,app,k)
    prepare(app);warm(app,config);verify(app,corpus,out,'restored')
