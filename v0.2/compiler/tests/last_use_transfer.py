"""Prove scanner publication and fallback through resident and fresh compiler sessions."""
import argparse
import json
import shutil
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import runtime, source_edit
from inline_adapters_native import incremental_native_build
from tokenizer_scanner_native import prepare


def signature_edits(root, flavor):
    s = fixture(flavor)
    app = root / (flavor + '-contracts')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    def unit(name):
        return app / (name + '.' + flavor)
    def record(namespace, field):
        body = s['record']('Payload', s['field'](field, 'int', '1'))
        return f'namespace {namespace}; {body}' if flavor == 'php' else f'namespace {namespace} {{ {body} }}'
    sep = {'cpp': '::', 'php': '\\', 'js': '.'}[flavor]
    factory = s['function']('produce', 'Alias', [], s['local']('r', 'Alias') + f'return {s["v"]}r;')
    def producer(namespace):
        return f'use {namespace}{sep}Payload as Alias; ' + factory
    source_edit(unit('a'), record('First', 'x'))
    source_edit(unit('b'), record('Second', 'x'))
    # This caller only watches the function. Accessing the field in main also watches its record.
    source_edit(unit('observer'), s['function']('observe', 'void', [], 'produce();'))
    source_edit(unit('main'), 'observe();' + s['infer']('r', 'produce()') + f'echo {s["v"]}r{s["access"]}x; return 8;')
    out = root / (app.name + '-out')
    resident = service(app, out)
    try:
        for label, namespace, field in [('initial', 'First', 'x'), ('import', 'Second', 'x'),
                                        ('type-and-body', 'Second', 'y'), ('recovery', 'Second', 'x'),
                                        ('type-only', 'Second', 'x'), ('restore-import', 'First', 'x')]:
            source_edit(unit('factory'), producer(namespace) + (' ' if label == 'type-and-body' else ''))
            source_edit(unit('b'), record('Second', field) + (' ' if label == 'type-only' else ''))
            before = artifacts(out)
            row = invoke(resident)
            fresh_out = root / (app.name + '-fresh')
            fresh = service(app, fresh_out)
            try:
                clean = invoke(fresh)
            finally:
                fresh.stdin.close()
                fresh.wait()
            if label == 'type-and-body':
                assert row.get('error') and row['error'] == clean.get('error'), (label, row, clean)
                assert artifacts(out) == before
            else:
                assert 'error' not in row and 'error' not in clean, (label, row, clean)
                for caller in ('main', 'observer'):
                    assert f'{app.name}/{caller}.{flavor}' in row['emitted_sources'], (label, row)
                assert artifacts(out) == artifacts(fresh_out)
    finally:
        resident.stdin.close()
        resident.wait()


def parameter_modes(root, flavor, libraries):
    s = fixture(flavor)
    app = root / (flavor + '-modes')
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    v, access = s['v'], s['access']
    field = v + 'out' + access + 'source'
    body = s['local']('out', 'Packet') + field + '="previous destination with heap storage";'
    body += field + '=' + v + 'text; return ' + v + 'out;'
    publish = s['function']('publish', 'Packet', [('text', 'string')], body)
    by_ref = {'cpp': ('string text', 'string& text'), 'php': ('string $text', 'string &$text'),
              'js': ('text:string', '&text:string')}[flavor]
    definitions = s['record']('Packet', s['field']('source', 'string'))
    definitions += publish + publish.replace('publish', 'borrow').replace(*by_ref)
    source_edit(app / ('packet.' + flavor), definitions)
    message = 'original heap-backed caller bytes'
    main = s['infer']('input', '"' + message + '"')
    for name in ('publish', 'borrow'):
        main += s['infer'](name + 'd', name + '(' + v + 'input)')
        main += 'echo ' + v + name + 'd' + access + 'source,' + v + 'input;'
    source_edit(app / ('main.' + flavor), main + 'return 8;')
    out = root / (app.name + '-out')
    proc = service(app, out)
    try:
        row = invoke(proc)
        assert 'error' not in row, row
        path = json.loads((out / 'manifest.json').read_text())['sources'][app.name + '/packet.' + flavor]
        code = (out / path).read_text()
        assert code.count('.source = std::move(var_text_') == code.count('.source = var_text_') == 1
        for cc, lib in libraries.items():
            incremental_native_build(out, root / (app.name + cc), cc, lib, message.encode() * 4)
    finally:
        proc.stdin.close()
        proc.wait()


def provider_changes(root, libraries):
    project = Path(__file__).resolve().parents[2]
    isolated = root / 'provider'
    compiler = isolated / 'compiler'
    shutil.copytree(project / 'compiler', compiler, ignore=shutil.ignore_patterns('tests', '__pycache__'))
    driver = compiler / 'tests/incremental_executable.php'
    driver.parent.mkdir()
    shutil.copyfile(project / 'compiler/tests' / driver.name, driver)
    for name in ('runtime', 'build', 'tools'):
        (isolated / name).symlink_to(project / name, target_is_directory=True)
    metadata = json.loads((project / 'runtime/metadata/runtime.json').read_text())
    capability = metadata['vector']['transfer_assignment']
    layout = project / 'build/clang/native-layout'
    first = {}
    for label, vector, string in [('move', True, True), ('string-copy', True, False),
                                  ('vector-copy', False, True), ('copy', False, False), ('restore', True, True)]:
        for family, enabled in [('vector', vector), ('string', string), ('hash', vector), ('boxed', string)]:
            if enabled:
                metadata[family]['transfer_assignment'] = capability
            else:
                metadata[family].pop('transfer_assignment', None)
        config = isolated / 'metadata.json'
        config.write_text(json.dumps(metadata))
        subprocess.run(['php', project / 'tools/api_generator/generate.php', 'emit-compiler', config,
                        project / 'runtime/metadata/publication.json', layout / 'facts.json', layout / 'context.json',
                        compiler / 'generated'], check=True)
        # Both application hash recipes, every published hash, and both boxed families opt in by metadata.
        for filename, enabled in [('hashes.php', vector), ('boxed.php', string)]:
            declarations = [line for line in (compiler / 'generated/types' / filename).read_text().splitlines()
                            if line.startswith('final class ')]
            assert declarations and all(('private_value_transfer_type' in line) == enabled for line in declarations)
        for flavor in ('cpp', 'php', 'js'):
            out = isolated / (flavor + '-out')
            run = subprocess.run(['php', driver, root / flavor, out], input='"run"\n', text=True,
                                 capture_output=True, check=True)
            row = json.loads(run.stdout)
            assert 'error' not in row, row
            unit = json.loads((out / 'manifest.json').read_text())['sources'][f'{flavor}/scanner.{flavor}']
            code = (out / unit).read_text()
            assert ('.tokens = std::move(var_items_' in code) == vector
            assert ('.tokens = var_items_' in code) == (not vector)
            assert ('.source = std::move(var_text_' in code) == string
            assert ('.source = var_text_' in code) != string
            assert ('#include <utility>' in code) == (vector or string)
            for cc, lib in libraries.items():
                incremental_native_build(out, isolated / (flavor + cc), cc, lib, b'1-1')
            if label == 'move':
                first[flavor] = artifacts(out)
            elif label == 'restore':
                assert first[flavor] == artifacts(out)


def prove(root):
    prepare(root)
    libraries = {'clang++': runtime(root, 'clang++')}
    for flavor in ('cpp', 'php', 'js'):
        app = root / flavor
        path = app / ('scanner.' + flavor)
        scanner = path.read_text()
        signature = 'Scan scan(' if flavor == 'cpp' else 'function scan('
        start = scanner.index(signature)
        source_edit(app / ('records.' + flavor), scanner[:start])
        original = scanner[start:]
        source_edit(path, original)
        var = '$' if flavor == 'php' else ''
        member = '.' if flavor == 'js' else '->'
        main = app / ('main.' + flavor)
        source_edit(main, main.read_text().replace('return 8;', f'echo count({var}r{member}tokens), {var}r{member}error; return 8;'))
        tail = f'{var}out{member}tokens={var}items;'
        infer = fixture(flavor)['infer']
        owner = var + 'out'
        source = owner + member + 'source'
        length = 'strlen(' + source + ');'
        replace_field = original.replace(length, length + source + '="@!";')
        replace_owner = original.replace(length, length + fixture(flavor)['local']('other', 'Scan')
                                         + var + 'other' + member + 'source="@!";' + owner + '=' + var + 'other;')
        shadow_owner = original.replace(length, length + f'if ({var}size > 0) {{'
                                        + fixture(flavor)['local']('out', 'Scan')
                                        + source + '="@";' + f'if ({source}[0] != 64) {{return {owner};}} }}')
        optional = {'php': '$number nullable<int> = -1;', 'js': 'let number:nullable<int> = -1;',
                    'cpp': 'nullable<int> number = -1;'}[flavor]
        decl = {'php': '$copy vector<Token> = $items;', 'js': 'let copy:vector<Token> = items;',
                'cpp': 'vector<Token> copy = items;'}[flavor]
        shadow = {'php': '$items vector<Token> = [];', 'js': 'let items:vector<Token> = [];',
                  'cpp': 'vector<Token> items = [];'}[flavor]
        touch = {'php': 'function touch(vector<Token> &$v):void {}', 'cpp': 'void touch(vector<Token>& v) {}',
                 'js': 'function touch(&v:vector<Token>):void {}'}[flavor]
        source_edit(app / ('touch.' + flavor), touch.replace('{}', '{' + var + 'v' + member + 'clear();}'))
        out = root / (flavor + '-out')
        resident = service(app, out)
        timings = {}
        try:
            key = flavor + '/scanner.' + flavor
            for label, text, move in [
                ('move', original, True),
                ('text-later', original.replace(tail, f'if ({var}size > 0) {{echo {var}text;}}' + tail), True),
                ('block-read', original.replace(tail, f'{{ echo {var}text; }}' + tail), True),
                ('try-read', original.replace(tail, f'try {{ echo {var}text; }} finally {{ {var}out{member}source="changed"; }}' + tail), False),
                ('text-restored', original, True),
                ('field-replaced', replace_field, True),
                ('owner-replaced', replace_owner, True),
                ('shadow-owner', shadow_owner, True),
                ('view-restored', original, True),
                ('later', original.replace(tail, tail + f'if (count({var}items) != count({var}out{member}tokens)) {{ {var}out{member}error=99; }}'), False),
                ('restore', original, True),
                ('reference', original.replace(tail, f'if ({var}size > 0) {{ touch({var}items); }}' + tail), False),
                ('invalid', original.replace(tail, 'unknown_function();' + tail), None),
                ('recovered', original, True),
                ('shadow', original.replace(tail, f'if ({var}size == 0) {{ {shadow} touch({var}items); }}' + tail), True),
                ('copy', original.replace(tail, decl + tail), True),
                ('replace', original.replace(tail, decl + f'{var}out{member}tokens={var}copy;' + tail), True),
                ('overlap', original.replace(tail, tail + f'{var}out{member}tokens={var}out{member}tokens;'), False),
                ('nonvector', original.replace(tail, tail + infer('label', f'{var}text') +
                                              f'{var}out{member}source={var}label;'), False),
                ('conversion', original.replace(tail, tail + optional + f'{var}out{member}error={var}number;'), False),
                ('original', original, True),
            ]:
                source_edit(path, text)
                before = artifacts(out)
                row = invoke(resident)
                clean_out = root / (flavor + '-fresh')
                fresh = service(app, clean_out)
                try:
                    clean = invoke(fresh)
                finally:
                    fresh.stdin.close()
                    fresh.wait()
                if move is None:
                    assert row.get('error') and row['error'] == clean.get('error'), (label, row, clean)
                    assert artifacts(out) == before
                    continue
                assert 'error' not in row and 'error' not in clean, (label, row, clean)
                assert artifacts(out) == artifacts(clean_out)
                if label != 'move':
                    assert key in row['emitted_sources'] and flavor + '/records.' + flavor not in row['emitted_sources'], (label, row)
                    assert flavor + '/main.' + flavor not in row['emitted_sources'], (label, row)
                    assert row['preparation'] == {'prepared': 1, 'reused': 2}, (label, row)
                unit = json.loads((out / 'manifest.json').read_text())['sources'][key]
                expected = {'text-later': b'if1-1', 'block-read': b'if1-1', 'try-read': b'if1-1', 'reference': b'0-1', 'field-replaced': b'00', 'owner-replaced': b'00'}.get(label, b'1-1')
                for cc, lib in libraries.items():
                    changed = incremental_native_build(out, root / (flavor + '-' + cc), cc, lib, expected)
                    if label != 'move':
                        paths = json.loads((out / 'manifest.json').read_text())['sources']
                        for name in ('main', 'records'):
                            assert paths[f'{flavor}/{name}.{flavor}'] not in changed, (label, changed)
                code = (out / unit).read_text()
                assert ('.tokens = std::move(var_items_' in code) == move, (flavor, label)
                assert ('#include <utility>' in code) == ('std::move(' in code)
                string_move = label not in ('text-later', 'block-read', 'try-read', 'nonvector')
                assert ('.source = std::move(var_text_' in code) == string_move, (flavor, label)
                assert ('.source = var_text_' in code) != string_move, (flavor, label)
                if label in ('move', 'try-read', 'field-replaced', 'owner-replaced', 'view-restored', 'original'):
                    view = label not in ('try-read', 'field-replaced', 'owner-replaced')
                    assert code.count('.checked_bytes()') == int(view), (flavor, label)
                    assert ('scpp_byte_view.read(' in code) == view
                    assert ('.source).byte_at(' in code) != view
                if label == 'shadow-owner':
                    assert '.source).byte_at(' in code and 'scpp_byte_view.read(' in code
                if label == 'conversion':
                    assert ').value()' in code
                idle = invoke(resident)
                assert idle['preparation']['prepared'] == 0 and not idle['emitted_sources']
                timings[label] = [row['php_ms'], idle['php_ms']]
        finally:
            resident.stdin.close()
            resident.wait()
            source_edit(path, original)
        parameter_modes(root, flavor, libraries)
        signature_edits(root, flavor)
        print(flavor, timings, flush=True)
    provider_changes(root, libraries)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('work', type=Path, nargs='?')
    work = parser.parse_args().work
    if work:
        prove(work.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-transfer-') as directory:
            prove(Path(directory))
