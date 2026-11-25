"""Exercise exact mixed observations through the project compiler and generated ABI."""
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
    (app/'predicates.php').write_text('''
function classify(mixed $value): int {
    if (is_bool($value)) {return 1;}
    if (is_int($value)) {return 2;}
    if (is_float($value)) {return 3;}
    return 0;
}
function once(int &$calls): mixed { $calls += 1; return false; }
''')
    main = app/'main.php'
    source = '''
$x mixed=false; if(classify($x)!==1 || is_int($x) || is_float($x)){return 1;}
$x=0; if(classify($x)!==2 || is_bool($x) || is_float($x)){return 2;}
$x=1.0; if(classify($x)!==3 || is_int($x) || is_bool($x)){return 3;}
$x="1"; if(classify($x)!==0){return 4;}
$x=null; if(classify($x)!==0){return 5;}
$x=[1]; if(classify($x)!==0 || is_float([]) || !is_int($x[0])){return 6;}
$calls=0; if(!is_bool(once($calls)) || $calls!==1){return 7;}
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
        for predicate in ('is_bool', 'is_int', 'is_float'):
            assert f'::scpp::{predicate}(' in lowered
        subprocess.run([clang, '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                        *units, *manifest['link_inputs'], '-o', work/'run'], check=True)
        subprocess.run([work/'run'], check=True)
        for expression in ['is_bool(false)', 'is_int(0)', 'is_float(1.0)', 'is_int($d)',
                           'is_bool($wrapped)', 'is_int()', 'is_int($x,$x)']:
            main.write_text('$x mixed=0; $d dynamic=1; $wrapped ?int=1; '+expression+'; return 0;')
            result = invoke(proc)
            reason = 'Argument count mismatch' if expression in ('is_int()', 'is_int($x,$x)') else 'Unsupported first argument type'
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
        (folder/f'main.{flavor}').write_text(code)
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
