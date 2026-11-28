"""Exercise exact mixed observations through the project compiler and generated ABI."""
from source_suffix import source_suffix
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import invoke, service

root = Path(__file__).resolve().parents[2]
clang = sys.argv[1]
with tempfile.TemporaryDirectory(prefix='mixed-predicates-') as temp:
    work = Path(temp)
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'predicates.phs').write_text('''
function classify(mixed $value): int {
    if (is_bool($value)) {return 1;}
    if (is_int($value)) {return 2;}
    if (is_float($value)) {return 3;}
    return 0;
}
function vectorOnce(int &$calls):vector<int> { $calls+=1; return []; }
function integerOnce(int &$calls):int { $calls+=1; return 0; }
function once(int &$calls): mixed { $calls += 1; return false; }
''')
    main = app/'main.phs'
    source = '''
$x mixed=false; if(classify($x)!==1 || is_int($x) || is_float($x)){return 1;}
$x=0; if(classify($x)!==2 || is_bool($x) || is_float($x)){return 2;}
$x=1.0; if(classify($x)!==3 || is_int($x) || is_bool($x)){return 3;}
$x="1"; if(classify($x)!==0){return 4;}
$x=null; if(classify($x)!==0){return 5;}
$x=[1]; if(classify($x)!==0 || is_float([]) || !is_int($x[0])){return 6;}
$calls=0; if(!is_bool(once($calls)) || $calls!==1){return 7;}
$d dynamic="text"; if(!is_string($d) || is_array($d) || is_null($d)){return 8;}
$d=null; if(!is_null($d) || !is_array([1])){return 9;}
if(!is_bool(false) || !is_float(1.0) || !is_int(integerOnce($calls)) || $calls!==2){return 10;}
$fixed fixed_array<byte,2>=[1,2]; $hash hash<int,string>=[];
if(!is_array($fixed) || is_int($fixed) || !is_array($hash) || !is_array(vectorOnce($calls)) || $calls!==3){return 11;}
if(is_array(once($calls)) || $calls!==4 || !is_array($x)){return 12;}
$d=[]; if(!is_array($d) || is_string($d)){return 13;}
return 0;
'''
    main.write_text(source)
    proc = service(app, out)
    try:
        response = invoke(proc)
        assert 'error' not in response, response
        manifest = json.loads((out/'manifest.json').read_text())
        units = [out/u for u in manifest['translation_units']]
        lowered = '\n'.join(u.read_text() for u in units)
        for kind in ('boolean', 'integer', 'floating', 'container'):
            assert f'boxed_kind::{kind}' in lowered
        assert 'static_cast<void>(integerOnce_' in lowered
        assert 'static_cast<void>(vectorOnce_' in lowered
        subprocess.run([clang, '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                        *units, *manifest['link_inputs'], '-o', work/'run'], check=True)
        subprocess.run([work/'run'], check=True)
        for expression in ['is_bool($wrapped)', 'is_int()', 'is_int($x,$x)']:
            main.write_text('$x mixed=0; $d dynamic=1; $wrapped ?int=1; '+expression+'; return 0;')
            result = invoke(proc)
            reason = 'Argument count mismatch' if expression in ('is_int()', 'is_int($x,$x)') else 'Unsupported first argument type'
            if expression.startswith('is_bool('):
                reason = 'requires a type with a PHP kind'
            assert reason in result.get('error', ''), (expression, result)
        main.write_text(source)
        assert 'error' not in invoke(proc)
    finally:
        proc.stdin.close()
        proc.wait()

    for flavor, code, general, accepted in [
        ('cpp', 'mixed x=0; return is_int(x) ? 0 : 1;', False, True),
        ('js', 'let x: mixed=0; return is_int(x) ? 0 : 1;', False, False),
        ('js', 'let x: mixed=0; return is_int(x) ? 0 : 1;', True, True),
    ]:
        folder = work/f'{flavor}-{general}'
        folder.mkdir()
        (folder/'module.json').write_text(json.dumps({'general_api': general}))
        (folder/f'main.{source_suffix(flavor)}').write_text(code)
        proc = service(folder, folder.parent/(folder.name+'-out'))
        try:
            result = invoke(proc)
            if accepted:
                assert 'error' not in result, result
            else:
                assert 'Unknown function: is_int' in result.get('error', ''), result
        finally:
            proc.stdin.close()
            proc.wait()
