"""Raw byte arrays, aggregate frame budget and bounds, through all three source flavors."""
import json
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service, write_build
from application_vectors_native import measure
from extends_native import source_edit
from extends_regressions import forms
from structs_native import build


def prove(work, flavor):
    v, access, record, field, _, local, infer = forms(flavor)
    app = work / flavor
    app.mkdir()
    main = app / ('main.' + flavor)
    provider = app / ('packet.' + flavor)
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
        zero = local('z', 'fixed_array<byte,0>').removesuffix(';') + ' = [];'
        zero += f'echo count({v}z),(int)empty({v}z); return 8;'
        source_edit(main, zero)
        assert 'error' not in (result := invoke(resident)), result
        build(output, work / ('zero-' + flavor), b'01')
        rhs = {'php': 'function tick():int', 'js': 'function tick():int', 'cpp': 'int tick()'}[flavor] + ' { echo "R"; return 7; }'
        for index in ('-1', '4', '4294967296', '9223372036854775807', '(-9223372036854775807-1)'):
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


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-fixed-array-') as tmp:
        for language in ('php', 'cpp', 'js'):
            prove(Path(tmp), language)
