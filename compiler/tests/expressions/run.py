"""Parser foundation: retained unit cases plus byte-boundary differential proofs."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/03_parse/data/expression_state.php','src/03_parse/handlers/expressions.php','src/03_parse/parse_file.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php','src/02_tokenize/structures.php','src/02_tokenize/store.php','src/02_tokenize/tokenize.php','src/03_parse/data/nodes.php','src/03_parse/data/tree.php','src/03_parse/utilities/binary_syntax.php']


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

    import random
    # Reused grammar forms from parsing, struct and metaprogramming units; full unit bodies
    # depend on statements/declarations and are not claimed as migrated here.
    values=['42','name','$value','true','"text"','first()','other(10,22)','1+2+3','1<2+3',
            '(1+2)<3','call(1,other($x))','new Box()','new Box<int>()','$box->field',
            '$box->method(1)','$a[1+2]','Box<int,4>','Box<Other<int>>','f<int>(1)',
            'a<(b>c)','a+(b+c)','new Box()->field','$a[0][1]','f(1)->x',
            '', '()', 'f(,)', 'f(1,)', 'new Box(1)', 'Box<>', '$a[]', '$a[1,2]',
            '1+', '(1', '$a->', '1 2', 'true()', '1>2','f(1','Box<int', '$9',
            'f(1<2,3)','f<(1+2)>()']
    cases=[[x,False] for x in values]+[[x,True] for x in ['int','Box<int>','Box<Other<int>,4>','1','int()','Box<>']]
    cases += [['('*1000+'1'+')'*1000,False],['f('*1000+'1'+')'*1000,False],['+'.join(['1']*1500),False]]
    rng=random.Random(23)
    def expr(depth):
        if depth==0:return rng.choice(['1','2','$x','name','true'])
        return rng.choice(['('+expr(depth-1)+')','f('+expr(depth-1)+','+expr(depth-1)+')',expr(depth-1)+'+'+expr(depth-1)])
    cases += [[expr(3),False] for _ in range(80)]
    (out/'oracle-inputs.json').write_text(json.dumps(cases))
    expected=json.loads(run('reference-oracle',['php',Path(__file__).parent/'oracle.php',out/'oracle-inputs.json']))
    assert expected[0]=={'rows':[[7,0,2,0]]}
    assert expected[7]=={'rows':[[14,0,5,2],[14,0,3,2],[7,4,1,0],[7,0,1,0],[7,2,1,0]]}
    calls=[]
    for index,(text,type_mode) in enumerate(cases):
        path=inputs/(str(index)+'.phs');path.write_text(text)
        calls.append('\\expression_test\\Probe::run('+json.dumps(str(path))+','+('true' if type_mode else 'false')+');')
    for relative in DEPENDENCIES + FILES:
        dest=source/relative;dest.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(ROOT/'compiler'/relative,dest)
    shutil.copy2(Path(__file__).parent/'probe.php',source/'probe.php')
    (source/'main.php').write_text('<?php\n'+'\n'.join(calls)+'\n')
    run('imports',['php',ROOT/'tools/php_portability/sync_imports.php',source])
    run('check',['php',ROOT/'tools/php_portability/check.php',source])
    php=['php','-r','foreach(array_slice($argv,1) as $p) { require $p; }',
         ROOT/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in DEPENDENCIES+FILES],
         source/'probe.php',source/'main.php']
    def prove(label, command, want):
        actual=[json.loads(line) for line in run(label,command,out).splitlines()]
        assert actual==want,(label,actual,want)
    prove('php',php,expected)
    generated=out/'phpp';conversion=['php',ROOT/'tools/php_portability/convert.php',source,generated]
    assert json.loads(run('convert',conversion))['converted']==12
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':12,'removed':0}
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
    report.update(passed=True,native=bool(binary),cases=len(expected),expression_cases=len(cases),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Expressions: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
