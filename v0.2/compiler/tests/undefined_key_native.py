"""Contextual boxed presence and same-instance rebinding of an unchanged indexed comparison."""
from pathlib import Path
from tempfile import TemporaryDirectory

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from exceptions_native import fresh_build
from extends_native import source_edit
from parent_methods_native import write_app
from classes_native import fixture
from collection_goal_native import revisions


def prove(work, flavor):
    syntax = fixture(flavor)
    fn = syntax['function']
    var = syntax['v']
    provider = fn('key', 'string', [], 'echo "K"; return "absent";')
    body = ''
    for family in ('mixed', 'dynamic'):
        local = var + 'data'
        declaration = {'php': f'$data {family}=[];', 'js': f'let data:{family}=[];',
                       'cpp': f'{family} data=[];'}[flavor]
        fields = ['null', 'false', '0', '""', '7']
        setup = declaration + ''.join(f'{local}[{i}]={item};' for i, item in enumerate(fields))
        provider += fn('make_' + family, family, [], setup + 'echo "R"; return ' + local + ';')
        body += fn('check_' + family, 'void', [('data', family)],
                   ''.join(f'echo (int)({local}["{i}"] !== undefined);' for i in range(5))
                   + f'echo (int)({local}["absent"] === undefined), count({local});'
                   + f'if (make_{family}()[key()] === undefined) {{echo "M";}}')
    main = 'check_mixed(make_mixed());check_dynamic(make_dynamic());return 8;'
    good = b'R1111115RKM' * 2
    # A bound integer takes ordinary boxed operator admission.
    bound = 'Unsupported boxed binary operands or operator'
    constant = 'const undefined:int=7;' if flavor == 'js' else 'const int undefined=7;'
    edits = [
        (None, None, good), (None, None, good), ('binding', constant, bound),
        ('binding', None, good),
        ('main', 'return undefined;', 'Unknown'),
        ('main', main, good),
    ]
    if flavor == 'php':
        edits.extend([
            ('main', '$data mixed=[]; if ($data[0] === $undefined) {} return 8;', 'Unknown'),
            ('main', '$data mixed=[]; if ($data[true] === undefined) {} return 8;', 'Boxed container key'),
            ('main', '$v vector<int> = []; if ($v[0] === undefined) {} return 8;', 'Missing-key test'),
            ('main', 'function replace(mixed &$v):int {$empty mixed=[]; $v=$empty; return 0;} '
             '$v mixed=[]; $v[0]=null; if ($v[replace($v)] === undefined) {return 1;}'
             'if ($v[0] !== undefined) {return 2;} return 8;', b''),
        ])
    else:
        local = syntax['infer']('undefined', '7')
        edits.append(('main', local + 'if (make_mixed()[0] === undefined) {} return 8;', bound))
    revisions(work, flavor, {'main': main, 'provider': provider, 'consumer': body}, edits)


def python_identity(work):
    app = work / 'python'
    write_app(app, 'py', {'main': """m: mixed = make_mixed()
d: dynamic = make_dynamic()
if m["x"] is undefined and m["0"] is not undefined:
    if d["x"] is undefined and d["0"] is not undefined:
        return 8
return 1
"""})
    provider = ''.join(f'function make_{t}():{t} {{ $v {t}=[]; $v[0]=null; return $v; }}'
                       for t in ('mixed', 'dynamic'))
    source_edit(app / 'provider.php', provider)
    out = work / 'python-out'
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        measure(out, work / 'python-native', b'')
        original = artifacts(out)
        source_edit(app / 'binding.php', 'const int undefined=7;')
        failed = invoke(proc)
        assert 'Unsupported boxed binary operands or operator' in failed.get('error', ''), failed
        assert failed['error'] == fresh_build(app, work / 'python-fresh-bound')['error']
        assert artifacts(out) == original
        (app / 'binding.php').unlink()
        repaired = invoke(proc)
        assert 'error' not in repaired, repaired
        fresh = work / 'python-fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(out) == artifacts(fresh) == original
    finally:
        proc.stdin.close()
        assert proc.wait() == 0


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-undefined-') as tmp:
        python_identity(Path(tmp))
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)
