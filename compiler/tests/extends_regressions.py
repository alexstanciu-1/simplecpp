"""Review regressions: transitive receiver watches and source-owned inherited factories."""
import json

from app_namespace_native import artifacts, invoke, service


def forms(flavor):
    variable = '$' if flavor == 'php' else ''
    access = '.' if flavor == 'js' else '->'

    def record(name, body='', base=None):
        relation = ((' : ' if flavor == 'cpp' else ' extends ') + base) if base else ''
        return f'struct {name}{relation} {{ {body} }}' + (';' if flavor == 'cpp' else '')

    def field(name, type_name, default=None, protected=False):
        visibility = ('protected: ' if protected else '') if flavor == 'cpp' else ('protected ' if protected else 'public ' if flavor == 'php' else '')
        declaration = f'{name}:{type_name}' if flavor == 'js' else f'{type_name} {variable}{name}'
        return visibility + declaration + (('=' + default) if default is not None else '') + ';'

    def getter():
        signature = {'php': 'public function get():int', 'cpp': 'int get()', 'js': 'get():int'}[flavor]
        return signature + ' { return ' + variable + 'this' + access + 'x; }'

    def local(name, type_name):
        return {'php': f'${name} {type_name};', 'cpp': f'{type_name} {name};', 'js': f'let {name}:{type_name};'}[flavor]

    def infer(name, value):
        return {'php': f'${name}={value};', 'cpp': f'auto {name}={value};', 'js': f'let {name}={value};'}[flavor]

    return variable, access, record, field, getter, local, infer


def incremental_receivers(work, flavor, libraries, build, source_edit):
    v, access, record, field, getter, local, infer = forms(flavor)
    app = work / (flavor + '-receiver-watch')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    files = {name: app / (name + '.' + flavor) for name in ['base', 'middle', 'child', 'other', 'main', 'unrelated']}
    base = record('Base', field('x', 'int', '3', True))
    other = record('Other', field('x', 'int', '9', True))
    middle = record('Middle', base='Base')
    unrelated = {'php': 'function unrelated():int { return 17; }', 'cpp': 'int unrelated() { return 17; }',
                 'js': 'function unrelated():int { return 17; }'}[flavor]
    initial = {'base': base, 'middle': middle, 'other': other, 'child': record('Child', getter(), 'Middle'),
               'main': infer('x', 'new Child()') + f'echo {v}x{access}get(); return 8;', 'unrelated': unrelated}
    for name, text in initial.items():
        source_edit(files[name], text)
    output = work / (flavor + '-receiver-out')
    edits = [(None, None, None, b'3'), (None, None, None, b'3'),
             ('base', base.replace('protected', 'private'), 'Cannot access private field', None),
             (None, None, 'Cannot access private field', None), ('base', base, None, b'3'),
             ('base', record('Base', field('x', 'string', '"s"', True)), 'Unsupported explicit conversion', None),
             ('base', base, None, b'3'), ('base', record('Base'), 'Unknown field', None),
             ('base', base, None, b'3'), ('base', None, 'Base must be a struct', None),
             (None, None, 'Base must be a struct', None), ('base', base, None, b'3'),
             ('middle', record('Middle', base='Other'), None, b'9'),
             ('other', record('Other', field('x', 'string', '"s"', True)), 'Unsupported explicit conversion', None),
             ('middle', middle, None, b'3'), ('other', other, None, b'3')]
    resident = service(app, output)
    try:
        for revision, (name, text, error, expected) in enumerate(edits):
            if name:
                if text is None:
                    files[name].unlink()
                else:
                    source_edit(files[name], text)
            before = artifacts(output)
            result = invoke(resident)
            fresh_out = work / f'{flavor}-receiver-fresh-{revision}'
            fresh = service(app, fresh_out)
            try:
                comparison = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if error:
                assert error in result.get('error', ''), (flavor, revision, result)
                assert result['error'] == comparison.get('error'), (result, comparison)
                assert artifacts(output) == before
                continue
            assert 'error' not in result and 'error' not in comparison, (result, comparison)
            assert artifacts(output) == artifacts(fresh_out), (flavor, revision)
            if revision:
                assert result['preparation']['reused'] >= 1, result
                assert not any(path.endswith('/unrelated.' + flavor) for path in result['emitted_sources']), result
            if revision in [1, 15]:
                assert result['preparation']['prepared'] == 0, result
                if revision == 1:
                    assert result['emitted_sources'] == [], result
                else:
                    assert not any(path.endswith('/child.' + flavor) for path in result['emitted_sources']), result
            if revision in [0, 4, 6, 8, 11, 12, 14, 15]:
                for compiler, library in libraries.items():
                    build(output, work / f'{flavor}-receiver-{compiler}', compiler, library, expected)
    finally:
        resident.stdin.close()
        resident.wait()


def constructor_publication(work, flavor, libraries, build, source_edit):
    v, access, record, field, getter, local, infer = forms(flavor)
    app = work / (flavor + '-factory-publication')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    params = 't:Token,h:shared<Token>' if flavor == 'js' else f'Token {v}t,shared<Token> {v}h'
    signature = {'php': 'public function __construct', 'cpp': 'Base', 'js': 'constructor'}[flavor]
    base = record('Base', field('x', 'int') + signature + '(' + params + ') { ' + v + 'this' + access + 'x=6; }')
    changed_params = 't:shared<Token>,h:Token' if flavor == 'js' else f'shared<Token> {v}t,Token {v}h'
    changed_base = base.replace(params, changed_params)
    files = {name: app / (name + '.' + flavor) for name in ['base', 'middle', 'child', 'token', 'holder', 'main']}
    models = {'base': base, 'middle': record('Middle', base='Base'), 'child': record('Child', base='Middle'),
              'token': record('Token', field('v', 'int')), 'holder': record('Holder', field('child', 'shared<Child>') + field('observer', 'weak<Child>'))}
    empty = local('x', 'shared<Child>') + local('w', 'weak<Child>') + local('holder', 'Holder') + 'return 8;'
    constructed = local('t', 'Token') + infer('h', 'new Token()') + infer('x', f'new Child({v}t,{v}h)') + f'echo {v}x{access}x; return 8;'
    for name, text in models.items():
        source_edit(files[name], text)
    source_edit(files['main'], empty)
    output = work / (flavor + '-factory-out')
    resident = service(app, output)
    try:
        for revision, (name, text, expected) in enumerate([
            (None, None, b''), ('main', constructed, b'6'), ('main', empty, b''),
            ('base', changed_base, b''),
            ('main', constructed.replace(f'new Child({v}t,{v}h)', f'new Child({v}h,{v}t)'), b'6'),
            ('main', empty, b''), ('base', base, b''), ('main', constructed, b'6'),
        ]):
            if name:
                source_edit(files[name], text)
            result = invoke(resident)
            assert 'error' not in result, result
            fresh_out = work / f'{flavor}-factory-fresh-{revision}'
            fresh = service(app, fresh_out)
            try:
                comparison = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            assert 'error' not in comparison and artifacts(output) == artifacts(fresh_out), comparison
            # Factories have one source owner, not inline definitions in arbitrary consumers/headers.
            matches = [(path, text) for path, text in artifacts(output).items() if b'owner_Child owner_Child::make(' in text]
            assert len(matches) == 1 and matches[0][0].suffix == '.cpp', matches
            assert b'inline owner_Child owner_Child::make(' not in matches[0][1]
            for compiler, library in libraries.items():
                build(output, work / f'{flavor}-factory-{compiler}', compiler, library, expected)
    finally:
        resident.stdin.close()
        resident.wait()
