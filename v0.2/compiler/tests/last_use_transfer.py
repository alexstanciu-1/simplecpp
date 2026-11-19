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
    for label in ('move', 'copy', 'restore'):
        if label == 'copy':
            del metadata['vector']['transfer_assignment']
        else:
            metadata['vector']['transfer_assignment'] = capability
        config = isolated / 'metadata.json'
        config.write_text(json.dumps(metadata))
        subprocess.run(['php', project / 'tools/api_generator/generate.php', 'emit-compiler', config,
                        project / 'runtime/metadata/publication.json', layout / 'facts.json', layout / 'context.json',
                        compiler / 'generated'], check=True)
        for flavor in ('cpp', 'php', 'js'):
            out = isolated / (flavor + '-out')
            run = subprocess.run(['php', driver, root / flavor, out], input='"run"\n', text=True,
                                 capture_output=True, check=True)
            row = json.loads(run.stdout)
            assert 'error' not in row, row
            unit = json.loads((out / 'manifest.json').read_text())['sources'][f'{flavor}/scanner.{flavor}']
            code = (out / unit).read_text()
            assert ('.tokens = std::move(var_items_' in code) == (label != 'copy')
            assert ('.tokens = var_items_' in code) == (label == 'copy')
            assert ('#include <utility>' in code) == (label != 'copy')
            for cc, lib in libraries.items():
                incremental_native_build(out, isolated / (flavor + cc), cc, lib, b'1-1')
            if label == 'move':
                first[flavor] = artifacts(out)
            elif label == 'restore':
                assert first[flavor] == artifacts(out)


def prove(root):
    prepare(root)
    libraries = {cc: runtime(root, cc) for cc in ('clang++', 'g++')}
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
                for cc, lib in libraries.items():
                    changed = incremental_native_build(out, root / (flavor + '-' + cc), cc, lib, b'0-1' if label == 'reference' else b'1-1')
                    if label != 'move':
                        paths = json.loads((out / 'manifest.json').read_text())['sources']
                        for name in ('main', 'records'):
                            assert paths[f'{flavor}/{name}.{flavor}'] not in changed, (label, changed)
                code = (out / unit).read_text()
                assert ('.tokens = std::move(var_items_' in code) == move, (flavor, label)
                assert ('#include <utility>' in code) == move
                if label == 'conversion':
                    assert ').value()' in code
                idle = invoke(resident)
                assert idle['preparation']['prepared'] == 0 and not idle['emitted_sources']
                timings[label] = [row['php_ms'], idle['php_ms']]
        finally:
            resident.stdin.close()
            resident.wait()
            source_edit(path, original)
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
