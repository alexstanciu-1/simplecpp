from pathlib import Path
import argparse,hashlib,json,shutil,sys
parser=argparse.ArgumentParser(description='Focused native compiler rebuild and closure join parity')
parser.add_argument('--results',type=Path,required=True)
parser.add_argument('--baseline',type=Path,help='Optional pre-change closure artifact directory')
parser.add_argument('--resume',action='store_true')
args=parser.parse_args()
root=Path(__file__).resolve().parents[2]
app=root/'compiler/my-try'; out=args.results.resolve(); out.mkdir(parents=True,exist_ok=args.resume)
(out/'summary.json').unlink(missing_ok=True)
sys.path.insert(0,str(app/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
from cpp_artifacts import probe_text
from native_validate import php_literal
logs=out/'logs'
index=1
while logs.exists():
    index+=1;logs=out/f'logs-{index}'
with CommandRunner(logs,root) as r:
    def host(suite):
        folder=out/suite; folder.mkdir(exist_ok=True)
        r.run(suite,['php',app/'tests'/f'{suite}.php',folder])
    run_tasks([Task(s,lambda s=s:host(s)) for s in ['closures','closure_model']],jobs=12)
    baseline=args.baseline.resolve() if args.baseline else None
    generated=list((out/'closures').glob('*.cpp'))
    if baseline:
        for p in generated:
            assert p.read_bytes()==(baseline/p.name).read_bytes(),p.name
    print(f'Closure artifacts: {len(generated)}; baseline comparison: {baseline is not None}',flush=True)
    source=out/'source';source.mkdir(exist_ok=True)
    for stage in ['01_prepare_inputs','02_tokenize','03_parse','04_analyze','05_backend','06_native','compiler']:
        shutil.copytree(app/stage,source/stage,dirs_exist_ok=True)
    # The existing tested native-driver protocol, with current compiler/proof sources.
    driver=(app/'tests/native_driver.php.in').read_text()
    for key,value in {'__REQUEST_FILE__':"'request.txt'",'__RUNTIME_CATALOG_DIR__':php_literal(root/'runtime/metadata/modules'),
                      '__RECOVERY_DIR__':php_literal(out/'recovery'),'__PIPELINE_DIR__':php_literal(out/'pipeline'),
                      '__RUN_LLVM_PROOFS__':'false'}.items():
        driver=driver.replace(key,value)
    (source/'main.php').write_text(driver)
    for name in ['s2s_proof.php','cpp_probe.php']:shutil.copyfile(app/'tests'/name,source/name)
    (out/'source_hashes.json').write_text(json.dumps({str(p.relative_to(source)):hashlib.sha256(p.read_bytes()).hexdigest() for p in source.rglob('*.php')},indent=2))
    project=out/'phpp'; tools=root/'tools/php_portability';cli=root/'bin/scpp.php'
    print('Converting and rebuilding native compiler...',flush=True)
    r.run('convert',['php',tools/'convert.php',source,project])
    r.run('framework',['php',tools/'install_native_runtime.php',project,'--filesystem','--os','--json'])
    if not (project/'prism.json').exists():
        r.run('init',['php',cli,'init','--php-profile=strict'],project,cli_reason='CLI project setup')
    config=json.loads((project/'prism.json').read_text());config['build']['cxx']='clang++-18'
    config['runtime']['modules']=['compiler','filesystem','tasks','process','datetime','json']
    (project/'prism.json').write_text(json.dumps(config,indent=2))
    r.run('native-build',['php',cli,'build','--build-runtime','--no-stan'],project,timeout=900,cli_reason='CLI native compiler build')
    executable=project/'.prism/build/main'
    print('Native compiler rebuilt; comparing closure artifacts...',flush=True)
    cases={
        'empty':('function make(): function<int()> { return function (): int { return 8; }; } $f = make(); return $f();',8,'typed_factory_empty'),
        'capture':('$n = 3; $f = function (int $x) use ($n): int { return $x + $n; }; $n = 8; return $f(4);',7,'capture_snapshot'),
        'multi':('$a = 2; $b = 3; $f = function (int $x, int $y) use ($a, $b): int { return $a + $b + $x + $y; }; return $f(4, 5);',14,None),
    }
    probe=out/'emit.php';probe.write_text('<?php\nnamespace scpp\\compiler;\nrequire '+php_literal(app/'boot.php')+';\n$c = new Compiler(); $c->init([$argv[1]]); $c->exec_cpp(); echo CPP_Output::encode(Model::$cpp_files);\n')
    compiler=CppFixtureCompiler(r,root,out,pch=False,runtime=True,cxx='clang++-18')
    def check(name,data):
        text,expected,fixture=data;folder=out/name;folder.mkdir(exist_ok=True);(folder/'main.phs').write_text(text)
        (folder/'request.txt').write_text('s2s:'+str(folder))
        host_output=r.run(name+'-php',['php',probe,folder])
        native_output=r.run(name+'-native',[executable],folder)
        assert native_output==host_output,name
        cpp=folder/'program.cpp';cpp.write_text(probe_text(native_output))
        if fixture and baseline: assert cpp.read_bytes()==(baseline/(fixture+'.cpp')).read_bytes(),name
        program=folder/'program';compiler.compile(name+'-compile',cpp,program)
        r.run(name+'-execute',[program],expected=expected)
    run_tasks([Task(n,lambda n=n,d=d:check(n,d)) for n,d in cases.items()],jobs=12)
    (out/'summary.json').write_text(json.dumps(dict(passed=True,closure_artifacts=len(generated),baseline_compared=baseline is not None,
        native_compiler_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(),native_parity_cases=len(cases),
        generated_executions=len(cases),stan='skipped',llvm='skipped'),indent=2))
