"""Tokenizer: retained unit cases plus byte-boundary differential proofs."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/02_tokenize/structures.php', 'src/02_tokenize/store.php', 'src/02_tokenize/tokenize.php', 'src/02_tokenize/main_tokenize.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php']


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

    import base64
    run('retained-unit',['php',Path(__file__).parent/'reuse_tests.php',out/'reused.json'])
    reused=json.loads((out/'reused.json').read_text())
    cases=reused['cases']
    # Extend the old suite with the full vocabulary and every single byte.
    reference=ROOT/'compiler/reference/pre-rewrite/src/02_tokenize'
    oracle=out/'extra_oracle.php'
    oracle.write_text("<?php\nrequire "+json.dumps(str(Path(__file__).parent/'reference_support.php'))+";\nrequire "+json.dumps(str(reference/'structures.php'))+";\nrequire "+json.dumps(str(reference/'tokenize.php'))+";\n"+
      "$out=[]; foreach(json_decode(file_get_contents($argv[1]),true) as $encoded) { $text=base64_decode($encoded); try { $b=\\tokenize\\File_Tokenizer::tokenize(new \\read_sources\\Source_Buffer(7,'case',0,$text)); $out[]=['text'=>$encoded,'rows'=>array_map(fn($t)=>[$t->kind->name,$t->start,$t->length],$b->rows)]; } catch (\\diagnostics\\Source_Error $e) { $out[]=['text'=>$encoded,'error'=>[$e->start,$e->length]]; } } echo json_encode($out);")
    extra=[bytes([i]) for i in range(256)]
    extra += [b'template typename constexpr consteval const true false function return if else while echo new struct public -> & [] <> +',
              b'"binary\x00\xff"', b'"escape\\', b'/*end*', b'//end\rreturn']
    (out/'extra-inputs.json').write_text(json.dumps([base64.b64encode(x).decode() for x in extra]))
    cases += json.loads(run('extra-oracle',['php',oracle,out/'extra-inputs.json']))
    expected=[];calls=[]
    for index,case in enumerate(cases):
        path=inputs/(str(index)+'.phs');path.write_bytes(base64.b64decode(case['text']))
        calls.append('\\tokenizer_test\\Probe::run('+json.dumps(str(path))+');')
        if 'error' in case:expected.append({'error':case['error'],'path':str(path),'rows':0})
        else:expected.append({'rows':case['rows'],'pure':True})
    good=inputs/'batch-good.phs';good.write_text('return 1;')
    bad=inputs/'batch-bad.phs';bad.write_text('$9')
    for right, valid in [(good,True),(bad,False)]:
        calls.append('\\tokenizer_test\\Probe::batch('+json.dumps(str(good))+','+json.dumps(str(right))+');')
        expected.append({'batch_valid':valid,'entry':1,'buffers':2,'first_valid':True})
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
    assert json.loads(run('convert',conversion))['converted']==7
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':7,'removed':0}
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
    report.update(passed=True,native=bool(binary),cases=len(expected),reused_unit_cases=len(reused['cases']) - len(extra),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Tokenizer: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
