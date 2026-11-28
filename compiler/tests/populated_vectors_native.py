"""Typed vector literals reuse sequence contracts, conversions and incremental syntax ownership."""
from source_suffix import source_suffix
import argparse
from pathlib import Path
from classes_native import fixture
from collection_goal_native import revisions, guard
from app_namespace_native import service, invoke
from extends_native import source_edit, runtime, build
from nested_collections_native import sanitize


def declaration(flavor, name, kind, value):
    return {'php': f'${name} {kind} = {value};', 'cpp': f'{kind} {name} = {value};',
            'js': f'let {name}:{kind} = {value};'}[flavor]


def sources(flavor):
    s = fixture(flavor)
    v, a = s['v'], s['access']
    decl = lambda n, t, x: declaration(flavor, n, t, x)
    point = s['record']('Point', s['field']('x', 'int', '7'))
    point += s['klass']('Owner', s['field']('x', 'int', '7'))
    api = s['function']('item', 'int', [('x', 'int')], f'echo {v}x; return {v}x;')
    api += s['function']('make', 'vector<int>', [], decl('result', 'vector<int>', '[4, 5]') + f'return {v}result;')
    main = decl('values', 'vector<int>', '[item(1), item(2), 3,]')
    main += s['infer']('copy', v+'values') + f'{v}copy[0]=9;echo {v}values[0],{v}copy[0];'
    main += decl('text', 'vector<string>', '["A\\0B", "heap backed string payload long enough", (string)true]')
    main += f'echo strlen({v}text[0]),{v}text[2];'
    main += decl('nested', 'vector<vector<vector<int>>>', '[[[6]], [], [make()]]')
    main += f'echo {v}nested[0][0][0],{v}nested[2][0][1];'
    main += s['local']('p', 'Point') + decl('points', 'vector<Point>', f'[{v}p, {v}p]')
    main += f'{v}p{a}x=99;' + s['infer']('first', f'{v}points[0]') + f'echo {v}first{a}x;'
    main += s['infer']('owner', 'new Owner()') + decl('owners', 'vector<Owner>', f'[{v}owner, {v}owner]')
    main += f'{v}owner{a}x=8;' + s['infer']('held', f'{v}owners[1]') + f'echo {v}held{a}x;'
    main += decl('h', 'hash<int,string>', '[]') + f'{v}h["a"]=4;' + decl('hashes', 'vector<hash<int,string>>', f'[{v}h, []]')
    main += f'echo {v}hashes[0]["a"],count({v}hashes[1]);'
    api += s['function']('producer', 'Iterator<int>', [], decl('items', 'vector<int>', '[6, 7]') + f'yield {v}items[0];yield {v}items[1];')
    main += s['infer']('cursor', 'producer()') + f'foreach ({v}cursor as {v}n) {{echo {v}n;}}return 8;'
    return {'point': point, 'api': api, 'main': main}


def rejection(work, flavor, src):
    app = work/flavor
    p = service(app, work/(flavor+'-reject-out'))
    s = fixture(flavor)
    try:
        invalid = [
            (declaration(flavor, 'x', 'hash<int,string>', '[1]'), 'sequence-initializable'),
            (declaration(flavor, 'x', 'vector<int>', '["bad"]'), None),
            (declaration(flavor, 'x', 'vector<int>', '['+s['v']+'x]'), None),
            (declaration(flavor, 'x', 'vector<int>', '["key" => 1]'), None),
        ]
        if flavor == 'cpp':
            invalid.append((s['infer']('x', '[1]'), 'explicitly typed local'))
        for text, message in invalid:
            source_edit(app/('main.'+source_suffix(flavor)), text+'return 8;')
            got = invoke(p)
            assert 'error' in got, got
            if message: assert message in got['error'], got
        source_edit(app/('main.'+source_suffix(flavor)), src['main'])
        assert 'error' not in invoke(p)
    finally:
        p.stdin.close(); p.wait()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--sanitize', action='store_true')
    parser.add_argument('--portable', action='store_true', help='Also compile every successful revision and failure probe on Clang and GCC')
    args = parser.parse_args(); args.output.mkdir(parents=True, exist_ok=True)
    libraries = {}
    if args.portable:
        root = Path(__file__).resolve().parents[2]
        libraries = {cc: runtime(args.output, cc, [root / 'runtime/generated/src/hash.cpp']) for cc in ('clang++', 'g++')}
    expected = b'12193true65784067'
    for flavor in ['php', 'cpp', 'js']:
        src = sources(flavor)
        revisions(args.output, flavor, src, [(None, None, expected), (None, None, expected),
            ('point', src['point'].replace('=7', '=6'), expected.replace(b'657', b'656')),
            ('point', None, None), (None, None, None), ('point', src['point'], expected),
            ('api', src['api'].replace('return '+fixture(flavor)['v']+'x;', 'return "bad";'), None),
            ('api', src['api'], expected)])
        # One common syntax supports inner bulk arrays and outer application append.
        emitted = ''.join(p.read_text() for p in (args.output/(flavor+'-out')).rglob('*.cpp'))
        assert '::from_owned(' in emitted and '.append(' in emitted
        for cc, library in libraries.items():
            for revision in (0, 1, 2, 5, 7):
                expected_revision = expected.replace(b'657', b'656') if revision == 2 else expected
                build(args.output/f'{flavor}-fresh-{revision}', args.output/f'{flavor}-{cc}-{revision}', cc, library, expected_revision)
        rejection(args.output, flavor, src)
        s = fixture(flavor); v = s['v']
        # Failure after a heap string was copied must unwind the partially filled vector.
        fail = s['function']('fail', 'string', [], s['infer']('text', '""') + s['infer']('bad', v+'text[0]') + 'return "";')
        source = fail + declaration(flavor, 'items', 'vector<string>', '["heap-backed payload copied before failure", fail(), "skipped"]') + 'return 8;'
        guard(args.output, flavor, 'cleanup', source, 'sequence_index_out_of_range')
        number = s['function']('number', 'float', [], 'return 1e100;')
        guard(args.output, flavor, 'conversion', number + declaration(flavor, 'items', 'vector<int>', '[1, number()]') + 'return 8;', 'integer_cast_out_of_range')
        for cc, library in libraries.items():
            for label in ('cleanup', 'conversion'):
                build(args.output/(flavor+'-'+label+'-out'), args.output/f'{flavor}-{cc}-{label}', cc, library, b'')
        if args.sanitize:
            sanitize(args.output/(flavor+'-out'), args.output/(flavor+'-sanitized'), expected)
            sanitize(args.output/(flavor+'-cleanup-out'), args.output/(flavor+'-cleanup-sanitized'), b'', static_abi=True)

    print('PASS populated vectors: all flavors, incremental/fresh recovery, nested bulk/append and failure cleanup'
          + ('; every successful revision checked on Clang/GCC' if args.portable else ''))


if __name__ == '__main__':
    main()
