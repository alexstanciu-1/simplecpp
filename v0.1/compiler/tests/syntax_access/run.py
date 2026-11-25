"""Read-only syntax roles, cursor lifecycle and logical subtree comparison proofs."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/03_parse/data/role_views.php', 'src/03_parse/utilities/metaprogramming_syntax.php', 'src/03_parse/utilities/struct_member_cursor.php', 'src/03_parse/utilities/syntax_access.php', 'src/03_parse/utilities/syntax_comparer.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_sources/data/buffer.php', 'src/02_tokenize/structures.php', 'src/02_tokenize/store.php', 'src/02_tokenize/tokenize.php', 'src/03_parse/data/nodes.php', 'src/03_parse/data/tree.php', 'src/03_parse/utilities/binary_syntax.php', 'src/03_parse/data/expression_state.php', 'src/03_parse/handlers/expressions.php', 'src/03_parse/data/result.php', 'src/03_parse/handlers/statements.php', 'src/03_parse/handlers/control_statements.php', 'src/03_parse/handlers/declarations.php', 'src/03_parse/handlers/metaprogramming.php', 'src/03_parse/parse_file.php']
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

    # Reuse accepted grammar inputs from the preceding retained-unit corpus.
    import importlib.util
    spec=importlib.util.spec_from_file_location('statement_cases', ROOT/'compiler/tests/statements/cases.py')
    module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
    candidates, provenance=module.cases(ROOT)
    (out/'candidate-inputs.json').write_text(json.dumps(candidates))
    parsed=json.loads(run('retained-parse',['php',ROOT/'compiler/tests/statements/oracle.php',out/'candidate-inputs.json']))
    texts=[text for text,result in zip(candidates,parsed) if 'rows' in result]
    pairs=[]
    for text in texts:
        pairs += [[text,'/* moved */ '+text+'\n',False],[text,text+'\nreturn 99;',False]]
    pairs += [['function f(): int {} function g(): int {}','echo 0; function f(): int {} function changed(): int {}',True],
              ['const N=1;','const N=2;',True],['echo "a";','echo "b";',False]]
    (out/'oracle-inputs.json').write_text(json.dumps(texts))
    (out/'comparison-inputs.json').write_text(json.dumps(pairs))
    expected=json.loads(run('reference-oracle',['php',Path(__file__).parent/'oracle.php',out/'oracle-inputs.json',out/'comparison-inputs.json']))
    assert expected[0]==[[],[]] # empty file has only root and entry, with no role views
    assert expected[len(texts):]==[x for _ in texts for x in [True,False]]+[True,False,False]
    calls=[]
    def path_for(text):
        path=inputs/(hashlib.sha256(text.encode()).hexdigest()+'.phs');path.write_text(text);return json.dumps(str(path))
    for text in texts:calls.append('\\syntax_test\\Probe::roles('+path_for(text)+');')
    for a,b,definition in pairs:calls.append('\\syntax_test\\Probe::compare('+path_for(a)+','+path_for(b)+','+('true' if definition else 'false')+');')
    calls.append('\\syntax_test\\Probe::checks();')
    expected += [True]*26
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
    report.update(passed=True,native=bool(binary),cases=len(expected),role_inputs=len(texts),comparison_cases=len(pairs),boundary_checks=26,
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Syntax access/comparison: {len(expected)} outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
