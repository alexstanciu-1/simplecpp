"""Source export capability association, pointer ABI and stable symbol validation."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import time
import importlib.util

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/05_generate_code/prepare_backend/utilities/callable_contract.php', 'src/05_generate_code/prepare_backend/source_export_preparation.php']
DEPENDENCIES = ['src/04_analyze/type_model/data/semantic_modes.php', 'src/04_analyze/type_model/data/representations.php', 'src/04_analyze/type_model/data/lifecycle_roles.php', 'src/04_analyze/type_model/data/lifecycle.php', 'src/04_analyze/type_model/data/lifetime_contract.php', 'src/04_analyze/type_model/data/resources.php', 'src/04_analyze/type_model/data/type_references.php', 'src/04_analyze/type_model/data/semantic_calls.php', 'src/04_analyze/type_model/data/callable_modes.php', 'src/04_analyze/type_model/data/callables.php', 'src/04_analyze/type_model/data/native_record_layout.php', 'src/04_analyze/type_model/data/definitions.php', 'src/04_analyze/type_model/data/storage.php', 'src/04_analyze/type_model/data/records.php', 'src/01_prepare_inputs/load_runtime/data/runtime.php', 'src/01_prepare_inputs/load_runtime/handlers/package_syntax.php', 'src/04_analyze/type_model/data/context.php', 'src/compile/data/native_project.php', 'src/04_analyze/resolve_types/data/export_identity.php', 'src/05_generate_code/prepare_backend/data/configuration.php', 'src/05_generate_code/prepare_backend/data/layout.php', 'src/05_generate_code/prepare_backend/data/abi.php', 'src/05_generate_code/prepare_backend/data/source_exports.php']
LOAD_ORDER = DEPENDENCIES + FILES


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--candidate-revision')
    args = parser.parse_args()
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    inputs = out / 'inputs'; inputs.mkdir()
    report = {'passed': False, 'commands': [], 'native_build_attempts': 0}

    def run(label, command, cwd=ROOT):
        start = time.monotonic()
        result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        for kind, value in [('stdout', result.stdout), ('stderr', result.stderr)]:
            (out / f'{label}.{kind}.log').write_text(value)
        if label == 'native-build': report['native_build_attempts'] += 1
        report['commands'].append({'label': label, 'command': list(map(str, command)),
                                  'seconds': round(time.monotonic() - start, 3), 'exit': result.returncode})
        (out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert result.returncode == 0, (label, result.stdout, result.stderr)
        return result.stdout

    spec=importlib.util.spec_from_file_location('source_export_validation_cases',Path(__file__).parent/'cases.py')
    module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
    cases=module.build()
    (out/'cases.json').write_text(json.dumps(cases,indent=2)+'\n')
    expected=[True]*len(cases)
    payload=json.dumps(cases,separators=(',',':')).replace('\\','\\\\').replace("'","\\'")
    calls=["\\source_export_validation_test\\Probe::run('"+payload+"');"]
    for relative in DEPENDENCIES + FILES:
        dest=source/relative;dest.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(ROOT/'compiler'/relative,dest)
    shutil.copy2(Path(__file__).parent/'probe.php',source/'probe.php')
    (source/'main.php').write_text('<?php\n'+'\n'.join(calls)+'\n')
    run('imports',['php',ROOT/'tools/php_portability/sync_imports.php',source])
    run('check',['php',ROOT/'tools/php_portability/check.php',source])
    php=['php','-r','foreach(array_slice($argv,1) as $p) { require $p; }',
         ROOT/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in LOAD_ORDER],
         source/'probe.php',source/'main.php']
    def prove(label, command, want):
        actual=[json.loads(line) for line in run(label,command,out).splitlines()]
        assert actual==want,(label,actual,want)
    prove('php',php,expected)
    report['php_ready_epoch'] = time.time()
    report['php_ready_sha256'] = {f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in FILES}
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    retained=json.loads(run('retained-symbols',['php',Path(__file__).parent/'oracle.php',out/'cases.json']))
    assert retained==expected
    report['retained_symbol_comparisons']=len(cases)
    generated=out/'phpp';conversion=['php',ROOT/'tools/php_portability/convert.php',source,generated]
    assert json.loads(run('convert',conversion))['converted']==len(DEPENDENCIES+FILES)+2
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':len(DEPENDENCIES+FILES)+2,'removed':0}
    run('runtime',['php',ROOT/'tools/php_portability/install_native_runtime.php',generated,'--json','--filesystem'])
    binary=None
    if args.target_checkout:
        target=json.loads((ROOT/'compiler/tools/portability_target.json').read_text())
        revision=args.candidate_revision or target['verified_commit'];checkout=args.target_checkout.resolve()
        assert len(revision)==40 and all(c in '0123456789abcdef' for c in revision)
        assert run('revision',['git','-C',checkout,'rev-parse','HEAD']).strip()==revision
        assert run('clean',['git','-C',checkout,'status','--porcelain']).strip()==''
        cli=checkout/'bin/scpp.php';run('init',['php',cli,'init','--php-profile=strict'],generated)
        config=json.loads((generated/'prism.json').read_text());config['build']['cxx']='clang++-18'
        config['runtime']['modules']=['json','filesystem'];(generated/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
        run('native-build',['php',cli,'build','--build-runtime'],generated)
        binary=[generated/'.prism/build/main'];prove('native',binary,expected)
        report['target_revision']=revision
        assert run('clean-after',['git','-C',checkout,'status','--porcelain']).strip()==''
    (out/'expected.json').write_text(json.dumps(expected,indent=2)+'\n')
    report.update(passed=True,native=bool(binary),cases=len(expected),source_export_validation_outcomes=len(expected),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Source export validation: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
