"""Whole-value transfer, destination cleanup and live-source fallback for collection providers."""
import argparse
import json
import shutil
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import invoke, service
from application_vectors_native import measure
from classes_native import fixture
from extends_native import ROOT, build, runtime
from parent_methods_native import write_app


def prove(work, flavor, libraries, quick=False):
    s = fixture(flavor)
    v, access = s['v'], s['access']
    definitions, main = '', ''
    expected = ''
    for label, typ in [('Hash', 'hash<int,string>'), ('Mixed', 'mixed'), ('Dynamic', 'dynamic')]:
        holder = label + 'Holder'
        definitions += s['record'](holder, s['field']('items', typ))
        empty = {'php': f'$items {typ} = [];', 'cpp': f'{typ} items = [];',
                 'js': f'let items:{typ} = [];'}[flavor]
        # The old destination is the sole owner of its heap-backed container.
        definitions += s['function']('old' + label, typ, [],
            empty + f'{v}items["old destination with heap-backed key"]=13;return {v}items;')
        field = f'{v}out{access}items'
        for mode in ('move', 'copy'):
            name = mode + label
            body = s['local']('out', holder) + f'{field}=old{label}();{field}={v}input;'
            if mode == 'copy':
                body += f'echo count({v}input);'
                expected += '1'
            definitions += s['function'](name, holder, [('input', typ)], body + f'return {v}out;')
            src = name + 'Src'
            dst = name + 'Dst'
            main += empty.replace('items', src) + f'{v}{src}["k"]=7;'
            main += s['infer'](dst, f'{name}({v}{src})')
            items = f'{v}{dst}{access}items'
            main += f'echo count({items});{items}["k"]=9;echo (int)({items}["k"]),(int)({v}{src}["k"]);'
            expected += '199' if label == 'Dynamic' else '197'
    # A weak observer makes destruction at assignment observable before function exit.
    definitions += s['record']('Node', s['field']('number', 'int', '1')).replace('struct ', 'class ', 1)
    for label, key_type, key in [('IntOwners', 'int', '1'), ('TextOwners', 'string', '"k"')]:
        typ = f'hash<Node,{key_type}>'
        definitions += s['record'](label, s['field']('items', typ))
        items = f'{v}out{access}items'
        body = s['local']('out', label) + f'{items}[{key}]=new Node();'
        body += s['infer']('observer', f'weak({items}[{key}])')
        body += f'{items}={v}input;if (lock({v}observer)!==null) {{echo "old owner survived";}}return {v}out;'
        definitions += s['function']('move' + label, label, [('input', typ)], body)
        name = 'input' + label
        main += {'php': f'${name} {typ} = [];', 'cpp': f'{typ} {name} = [];',
                 'js': f'let {name}:{typ} = [];'}[flavor]
        main += f'{v}{name}[{key}]=new Node();'
        main += s['infer']('result' + label, f'move{label}({v}{name})')
        main += s['infer']('held' + label, f'{v}result{label}{access}items[{key}]')
        main += f'{v}held{label}{access}number=8;'
        main += s['infer']('retained' + label, f'{v}{name}[{key}]') + f'echo {v}retained{label}{access}number;'
        expected += '8'
    app, out = work / flavor, work / (flavor + '-out')
    write_app(app, flavor, {'api': definitions, 'main': main + 'return 8;'})
    resident = service(app, out)
    try:
        row = invoke(resident)
        assert 'error' not in row, row
    finally:
        resident.stdin.close()
        resident.wait()
    manifest = json.loads((out / 'manifest.json').read_text())
    code = (out / manifest['sources'][f'{flavor}/api.{flavor}']).read_text()
    for label in ('Hash', 'Mixed', 'Dynamic'):
        for mode in ('move', 'copy'):
            function = code.split(mode + label + '_(', 1)[1].split('\n}', 1)[0]
            rhs = 'std::move(var_input_0)' if mode == 'move' else 'var_input_0'
            assert f'.items = {rhs};' in function, function
    for label in ('IntOwners', 'TextOwners'):
        function = code.split('move' + label + '_(', 1)[1].split('\n}', 1)[0]
        assert '.items = std::move(var_input_0);' in function, function
    if quick:
        measure(out, work / "native", expected.encode())
        return
    for cc, lib in libraries.items():
        executable = work / (flavor + cc)
        build(out, executable, cc, lib, expected.encode())
        if shutil.which('valgrind'):
            result = subprocess.run(['valgrind', '--quiet', '--leak-check=full',
                '--errors-for-leak-kinds=definite,indirect', '--error-exitcode=99', executable],
                capture_output=True)
            assert (result.returncode, result.stdout, result.stderr) == (8, expected.encode(), b''), result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-collection-transfer-') as directory:
        root = Path(directory)
        sources = [ROOT / 'runtime' / path for path in ('generated/src/hash.cpp', 'generated/src/boxed.cpp',
            'src/boxed/boxed_value.cpp', 'src/boxed/boxed_conversion.cpp', 'src/hash/packed_key.cpp')]
        libraries = {} if quick else {'clang++': runtime(root, 'clang++', sources)}
        for flavor in (('php',) if quick else ('cpp', 'php', 'js')):
            prove(root, flavor, libraries, quick)
