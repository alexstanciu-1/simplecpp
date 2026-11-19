"""Recursive vector/hash value specialization, copies, mutation and incremental/fresh recovery."""
import argparse
import json
import os
import subprocess
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions


def sources(flavor):
    s = fixture(flavor)
    v, a = s['v'], s['access']
    def decl(n, t, value='[]'):
        return {'php': f'${n} {t} = {value};', 'cpp': f'{t} {n} = {value};', 'js': f'let {n}:{t} = {value};'}[flavor]
    point = s['record']('Point', s['field']('value', 'int', '7'))
    # Homogeneous vectors, homogeneous hashes with both key types, and alternating families.
    shapes = [('vector', 'vector', 'vector'), ('hash', 'hash', 'hash'), ('vector', 'hash', 'vector'), ('hash', 'vector', 'hash')]
    api = ''
    main = ''
    fields = ''
    for i, shape in enumerate(shapes):
        for payload in ['int', 'Point']:
            tag = str(i) + payload
            types = [payload]
            keys = []
            for depth, family in enumerate(shape):
                key = 'int' if depth == 1 else 'string'
                types.append(f'vector<{types[-1]}>' if family == 'vector' else f'hash<{types[-1]},{key}>')
                keys.append('0' if family == 'vector' else '2' if key == 'int' else '"k"')
            fields += s['field']('f'+tag, types[3])
            setup = s['local']('p', 'Point') if payload == 'Point' else ''
            setup += decl('inner', types[1]) + decl('middle', types[2]) + decl('outer', types[3])
            for depth, name, value in [(0, 'inner', v+'p' if payload == 'Point' else '7'), (1, 'middle', v+'inner'), (2, 'outer', v+'middle')]:
                index = '' if shape[depth] == 'vector' else keys[depth]
                setup += f'{v}{name}[{index}]={value};'
            setup += f'return {v}outer;'
            api += s['function']('make'+tag, types[3], [], setup)
            api += s['function']('copy'+tag, types[3], [('value', types[3])], f'return {v}value;')
            main += s['infer']('original'+tag, 'make'+tag+'()')
            main += s['infer']('copy'+tag, f'copy{tag}({v}original{tag})')
            main += f'{v}bag{a}f{tag}={v}copy{tag};'
            main += s['infer']('mid'+tag, f'{v}copy{tag}[{keys[2]}]')
            main += s['infer']('leaf'+tag, f'{v}mid{tag}[{keys[1]}]')
            if payload == 'Point':
                main += s['infer']('p'+tag, f'{v}leaf{tag}[{keys[0]}]') + f'{v}p{tag}{a}value=9;'
            main += f'{v}leaf{tag}[{keys[0]}]=' + (v+'p'+tag if payload == 'Point' else '9') + ';'
            main += f'{v}mid{tag}[{keys[1]}]={v}leaf{tag};{v}copy{tag}[{keys[2]}]={v}mid{tag};'
            # Read chains copy values. Mutations explicitly write each changed value back.
            for label, expr in [('before', v+'original'+tag), ('after', v+'copy'+tag), ('field', v+'bag'+a+'f'+tag)]:
                read = expr + ''.join('['+k+']' for k in reversed(keys))
                main += s['infer'](label+tag, read)
                main += f'echo {v}{label}{tag}' + (a+'value' if payload == 'Point' else '') + ',":";'
            main += f'{v}copy{tag}{a}clear();echo count({v}original{tag}),";";'
            # Traversal copies nested collection outputs using the same construction-aware slot.
            main += f'foreach ({v}original{tag} as {v}entry{tag}) {{echo count({v}entry{tag});}}'
    bag = s['record']('Bag', fields)
    main = s['local']('bag', 'Bag') + main + 'return 8;'
    return dict(point=point, bag=bag, api=api, main=main)


def sanitize(output, destination, expected, static_abi=False):
    """Instrument generated nested storage; reuse the prebuilt runtime (not instrumented here)."""
    root = Path(__file__).resolve().parents[2]
    units = [output / name for name in json.loads((output / 'manifest.json').read_text())['translation_units']]
    command = ['clang++', '-std=c++23', '-stdlib=libc++', '-O1', '-g0',
        '-fsanitize=address,undefined', '-fno-omit-frame-pointer',
        '-I', str(root / 'runtime/generated/public'), *map(str, units),
        str(root / 'build/clang/libscpp_runtime.a'), '-o', str(destination)]
    if static_abi:
        command.insert(1, '-static-libstdc++')
    subprocess.run(command, check=True, capture_output=True)
    result = subprocess.run([str(destination)], capture_output=True,
        env={**os.environ, 'ASAN_OPTIONS': 'detect_leaks=1:halt_on_error=1', 'UBSAN_OPTIONS': 'halt_on_error=1'})
    assert (result.returncode, result.stdout, result.stderr) == (8, expected, b''), result
    print(output.name, 'generated-storage ASan/UBSan passed', flush=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--sanitize', action='store_true')
    args = parser.parse_args(); args.output.mkdir(parents=True, exist_ok=True)
    expected = b'7:9:7:1;1' * 8
    for flavor in ['php', 'cpp', 'js']:
        src = sources(flavor)
        changed = src['point'].replace('=7', '=8')
        changed_output = (b'7:9:7:1;1' + b'8:9:8:1;1') * 4
        revisions(args.output, flavor, src, [(None,None,expected),(None,None,expected),
            ('point',changed,changed_output),('point',None,None),(None,None,None),
            ('point',src['point'],expected),('point',src['point'].replace('int','string'),None),
            ('point',src['point'],expected),
            ('bad',fixture(flavor)['record']('Recursive',fixture(flavor)['field']('children','vector<hash<vector<Recursive>,string>>')),None),
            ('bad',None,expected),
            ('api',src['api'].replace('vector<vector<vector<Point>>>','vector<vector<Point>>'),None),
            ('api',src['api'],expected)])
        if args.sanitize:
            sanitize(args.output / (flavor+'-out'), args.output / (flavor+'-sanitized'), expected)


if __name__ == '__main__':
    main()
