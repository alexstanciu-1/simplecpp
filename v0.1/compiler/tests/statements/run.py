"""File grammar: retained inputs, invariants and differential PHP/native proofs."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/03_parse/data/result.php','src/03_parse/handlers/statements.php','src/03_parse/handlers/control_statements.php','src/03_parse/handlers/declarations.php','src/03_parse/handlers/metaprogramming.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php','src/02_tokenize/structures.php','src/02_tokenize/store.php','src/02_tokenize/tokenize.php','src/03_parse/data/nodes.php','src/03_parse/data/tree.php','src/03_parse/utilities/binary_syntax.php','src/03_parse/data/expression_state.php','src/03_parse/handlers/expressions.php','src/03_parse/parse_file.php']
LOAD_ORDER = DEPENDENCIES[:-1] + FILES + DEPENDENCIES[-1:]


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

    run('lifecycle',['php',Path(__file__).parent/'lifecycle.php'])
    from cases import cases
    cases, provenance = cases(ROOT)
    (out/'retained-cases.json').write_text(json.dumps(provenance,indent=2)+'\n')
    (out/'oracle-inputs.json').write_text(json.dumps(cases))
    expected=json.loads(run('reference-oracle',['php',Path(__file__).parent/'oracle.php',out/'oracle-inputs.json']))
    # Independently specified empty/return/function layouts supplement the old parser oracle.
    assert expected[0] == {'rows':[[9,0,0,1],[3,0,0,0]],'definitions':0}
    assert expected[1] == {'rows':[[9,0,7,1],[3,0,7,1],[4,0,7,0]],'definitions':0}
    assert expected[2] == {'rows':[[9,0,10,1],[3,0,10,1],[4,0,10,1],[7,7,2,0]],'definitions':0}
    assert expected[3]['definitions']==1 and expected[4]['definitions']==2
    calls=[]
    for index,text in enumerate(cases):
        path=inputs/(str(index)+'.phs');path.write_text(text)
        calls.append('\\statement_test\\Probe::run('+json.dumps(str(path))+');')
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
    report.update(passed=True,native=bool(binary),cases=len(expected),grammar_cases=len(cases),retained_literal_cases=len(provenance),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Statements/declarations: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
