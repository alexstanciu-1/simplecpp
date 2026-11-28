"""Raw byte arrays, aggregate frame budget and bounds, through all three source flavors."""
from source_suffix import source_suffix
import json
import subprocess
import argparse
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service, write_build
from application_vectors_native import measure
from collection_goal_native import revisions
from extends_native import source_edit
from extends_regressions import forms
from structs_native import build


def prove(work, flavor, quick=False):
    v, access, record, field, _, local, infer = forms(flavor)
    app = work / flavor
    app.mkdir()
    main = app / ('main.' + source_suffix(flavor))
    provider = app / ('packet.' + source_suffix(flavor))
    source_edit(app / 'module.json', json.dumps({'entry': main.name}))
    output = work / ('out-' + flavor)

    def model(n):
        return record('Packet', field('grid', f'fixed_array<fixed_array<byte,4>,{n}>')) + record('Envelope', field('packet', 'Packet'))

    path = f'{v}box{access}packet{access}grid'
    body = local('box', 'Envelope') + local('other', 'Envelope')
    body += local('pad', 'fixed_array<byte,4>').removesuffix(';') + ' = [1,2,3,4];'
    body += f'{path}[1][2]=7;' + infer('row', path + '[1]') + f'{path}[1][2]=9;'
    body += f'{v}pad={v}row; {v}row[2]=11; echo count({v}pad),(int){v}pad[2],(int){v}row[2],(int){path}[1][2]; return 8;'
    source_edit(main, body)
    with service(app, output) as resident:
        for rev, (n, expected) in enumerate([(8191, None), (8192, '64 KiB'), (9223372036854775807, 'uint32 inline payload capacity'), (8191, None)]):
            source_edit(provider, model(n))
            result = invoke(resident)
            fresh_dir = work / f'fresh-{flavor}-{rev}'
            with service(app, fresh_dir) as fresh:
                oracle = invoke(fresh)
                fresh.stdin.close()
            if expected:
                assert expected in result.get('error', ''), result
                assert result['error'] == oracle['error']
            else:
                assert 'error' not in result and 'error' not in oracle, (result, oracle)
                assert artifacts(output) == artifacts(fresh_dir)
                print(flavor, measure(output, work / f'native-{flavor}-{rev}', b'47119'), flush=True)
        for invalid, message in [
            (body.replace('return 8;', local('extra', 'fixed_array<byte,1>') + 'return 8;'), '64 KiB'),
            (body.replace('[1,2,3,4]', '[1,2,3]'), 'match extent'),
            (body.replace('[1,2,3,4]', '[1,2,3,256]'), 'outside uint8'),
        ]:
            source_edit(main, invalid)
            failure = invoke(resident)
            with service(app, work / ('invalid-fresh-' + flavor)) as oracle:
                assert message in failure.get('error', '') and failure['error'] == invoke(oracle)['error'], failure
                oracle.stdin.close()
        # Exercise the same large record with heap and inline storage in one session.
        source_edit(provider, model(32768))
        heap = infer('box', 'new Envelope()')
        heap += f'echo (int){path}[32767][3]; {path}[32767][3]=231; echo (int){path}[32767][3]; return 8;'
        inline = local('box', 'Envelope') + 'return 8;'
        for revision, program in enumerate((heap, inline, heap)):
            source_edit(main, program)
            result = invoke(resident)
            fresh_dir = work / f'heap-fresh-{flavor}-{revision}'
            with service(app, fresh_dir) as fresh:
                oracle = invoke(fresh)
                fresh.stdin.close()
            if program == heap:
                assert 'error' not in result and 'error' not in oracle, (result, oracle)
                assert artifacts(output) == artifacts(fresh_dir)
                print(flavor, 'heap record', measure(output, work / f'heap-{flavor}-{revision}', b'0231'), flush=True)
            else:
                assert 'Function fixed-array storage exceeds 64 KiB' in result.get('error', ''), result
                assert result['error'] == oracle['error']
        replace = {
            'php': 'function replace(shared<Envelope> &$box): int',
            'cpp': 'int replace(shared<Envelope>& box)',
            'js': 'function replace(&box: shared<Envelope>): int',
        }[flavor]
        replace += f'{{ {v}box = new Envelope(); return 231; }}'
        mutation = replace + infer('box', 'new Envelope()') + infer('old', f'{v}box')
        mutation += f'{path}[32767][3]=replace({v}box);'
        old_path = f'{v}old{access}packet{access}grid'
        mutation += f'echo (int){path}[32767][3],(int){old_path}[32767][3]; return 8;'
        source_edit(main, mutation)
        assert 'error' not in (result := invoke(resident)), result
        build(output, work / f'heap-receiver-replacement-{flavor}', b'0231')
        source_edit(provider, model(8191))
        signature = {'php': 'function accept(Envelope $packet):void', 'cpp': 'void accept(Envelope packet)',
                     'js': 'function accept(packet:Envelope):void'}[flavor]
        source_edit(main, signature + '{' + local('scratch', 'fixed_array<byte,32772>') + '} return 8;')
        for rev, n in enumerate((8191, 8192, 8191)):
            source_edit(provider, model(n))
            result = invoke(resident)
            fresh_dir = work / f'parameter-{flavor}-{rev}'
            with service(app, fresh_dir) as fresh:
                oracle = invoke(fresh)
                fresh.stdin.close()
            if n == 8192:
                assert '64 KiB' in result.get('error', '') and result['error'] == oracle.get('error'), (result, oracle)
            else:
                assert 'error' not in result and 'error' not in oracle, (result, oracle)
                assert artifacts(output) == artifacts(fresh_dir)
        zero = local('z', 'fixed_array<byte,0>').removesuffix(';') + ' = [];'
        zero += f'echo count({v}z),(int)empty({v}z); return 8;'
        source_edit(main, zero)
        assert 'error' not in (result := invoke(resident)), result
        build(output, work / ('zero-' + flavor), b'01')
        rhs = {'php': 'function tick():int', 'js': 'function tick():int', 'cpp': 'int tick()'}[flavor] + ' { echo "R"; return 7; }'
        for index in (('-1', '4') if quick else ('-1', '4', '4294967296', '9223372036854775807', '(-9223372036854775807-1)')):
            access_statement = (f'{path}[0][{index}]=tick();' if index in ('-1', '4294967296') else f'echo (int){path}[0][{index}];')
            source_edit(main, rhs + local('box', 'Envelope') + access_statement + ' return 8;')
            assert 'error' not in (result := invoke(resident)), result
            native = work / ('bounds-' + flavor)
            native.mkdir(exist_ok=True)
            units = json.loads((output / 'manifest.json').read_text())['translation_units']
            command = write_build(native, [output / unit for unit in units])
            subprocess.run(command, cwd=native, check=True, capture_output=True)
            run = subprocess.run([native / 'program'], capture_output=True)
            assert (run.returncode, run.stdout, run.stderr) == (1, b'', b'sequence_index_out_of_range\n'), run
        resident.stdin.close()


def named_extents(work, flavor, quick=False):
    v, access, record, field, _, local, _ = forms(flavor)
    sep = {'php': '\\', 'cpp': '::', 'js': '.'}[flavor]

    def constant(name, value, kind='int'):
        return f'const {name}: {kind} = {value};' if flavor == 'js' else f'const {kind} {name} = {value};'

    def units(value):
        return 'namespace Units {' + constant('UNIT', value) + '}'

    size = constant('PAGE_SIZE', '2 * N')
    prefix = f'use const Units{sep}UNIT as N;'
    packet = record('Packet', field('bytes', 'fixed_array<byte,CAP>'))
    envelope = record('Envelope', field('packet', 'Packet'))
    signature = 'int localSize()' if flavor == 'cpp' else 'function localSize():int'
    local_size = signature + '{' + local('pad', f'fixed_array<byte,Sizes{sep}PAGE_SIZE>') + f'return count({v}pad);}}'
    main = local('box', 'Envelope') + f'echo count({v}box{access}packet{access}bytes),localSize(); return 8;'
    sources = dict(main=main, local_size=local_size, units=units(2), sizes='namespace Sizes {' + prefix + size + '}',
                   packet=f'use const Sizes{sep}PAGE_SIZE as CAP;' + packet, envelope=envelope)
    edits = [
        (None, None, b'44'),
        (None, None, b'44'),
        ('units', units(3), b'66'),
        ('units', units(-1), 'extent must be non-negative'),
        ('units', units(32769), '64 KiB'),
        ('units', units(0), b'00'),
        ('sizes', 'namespace Sizes {' + constant('PAGE_SIZE', 'true', 'bool') + '}', 'requires a namespace integer constant'),
        ('sizes', None, 'requires a namespace integer constant'),
        ('sizes', sources['sizes'], b'00'),
    ]
    revisions(work, flavor, sources, [edits[i] for i in (0, 2, 3, 5)] if quick else edits)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-fixed-array-') as tmp:
        named = Path(tmp) / 'named'
        named.mkdir()
        for language in (('php',) if quick else ('php', 'cpp', 'js')):
            named_extents(named, language, quick)
            prove(Path(tmp), language, quick)
