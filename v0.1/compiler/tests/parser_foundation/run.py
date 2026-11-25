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
FILES = ['src/03_parse/data/nodes.php','src/03_parse/data/tree.php','src/03_parse/utilities/binary_syntax.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php','src/02_tokenize/structures.php','src/02_tokenize/store.php','src/02_tokenize/tokenize.php']


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

    run('retained-unit',['php',Path(__file__).parent/'reuse_unit.php'])
    import random
    randomizer=random.Random(231)
    inputs_text=['Name<Other<int>>', 'x < (y > z)', 'x < [y > z]', 'x < y; z > t',
                 '<{>}', '<[<]>','a<b,c>(d)', '<'*1000+'>'*1000]
    inputs_text += [' '.join(randomizer.choices(['<','>','(',')','[',']','{','}',';',',','name'],k=randomizer.randrange(1,80))) for _ in range(400)]
    reference=ROOT/'compiler/reference/pre-rewrite'
    oracle=out/'oracle.php'
    oracle.write_text('<?php\nrequire '+json.dumps(str(ROOT/'tools/php_portability/runtime/bootstrap.php'))+';\nrequire '+json.dumps(str(ROOT/'compiler/tests/tokenizer/reference_support.php'))+';\n'+
       ''.join('require '+json.dumps(str(reference/p))+';\n' for p in ['src/02_tokenize/structures.php','src/02_tokenize/tokenize.php','src/03_parse/data/nodes.php','src/03_parse/utilities/binary_syntax.php'])+
       '$results=[]; foreach(json_decode(file_get_contents($argv[1]),true) as $text) { $tokens=\\tokenize\\File_Tokenizer::tokenize(new \\read_sources\\Source_Buffer(7,"case",0,$text)); $pairs=[]; foreach(\\parse\\Binary_Syntax::angle_ends($tokens) as $a=>$b) { $pairs[]=[$a,$b]; } $results[]=$pairs; } echo json_encode($results);')
    (out/'oracle-inputs.json').write_text(json.dumps(inputs_text))
    expected=json.loads(run('reference-oracle',['php',oracle,out/'oracle-inputs.json']))
    # Independently specified scope boundaries, alongside differential random streams.
    assert expected[:5]==[[[3,5],[1,6]],[],[],[],[]],expected[:5]
    calls=[]
    for index,text in enumerate(inputs_text):
        path=inputs/(str(index)+'.phs');path.write_text(text)
        calls.append('\\parser_test\\Probe::angles('+json.dumps(str(path))+');')
    calls.append('\\parser_test\\Probe::arena();')
    expected += [{'size':3,'first':2,'last':3,'next':3,'length':8,'old_length':0,'stored_start':2},True,True,True,True,[14,2,1,0]]
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
    assert json.loads(run('convert',conversion))['converted']==9
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':9,'removed':0}
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
    report.update(passed=True,native=bool(binary),cases=len(expected),reference_streams=len(inputs_text),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Parser foundation: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
