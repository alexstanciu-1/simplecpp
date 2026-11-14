"""Prefixed integer boundaries, checked destinations and resident recovery in every flavor."""
import json
import os
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from structs_native import build


def source(flavor, literal, kind='int'):
    declaration, name = {
        'php': (f'$value {kind} = {literal};', '$value'),
        'cpp': (f'{kind} value = {literal};', 'value'),
        'js': (f'let value:{kind} = {literal};', 'value'),
    }[flavor]
    return declaration + f' echo (int){name}; return 8;'


def prove(work, flavor):
    app = work / flavor
    app.mkdir()
    entry = app / ('main.' + flavor)
    (app / 'module.json').write_text(json.dumps({'entry': entry.name}))
    output = work / ('out-' + flavor)
    good = [('0x7fffffffffffffff', 'int', '9223372036854775807'),
            ('-0X8000000000000000', 'int', '-9223372036854775808'),
            ('0o777777777777777777777', 'int', '9223372036854775807'),
            ('-0O1000000000000000000000', 'int', '-9223372036854775808'),
            ('0x000eE', 'int', '238'), ('0o000755', 'int', '493'),
            ('0755', 'int', '755'), ('08', 'int', '8'), ('-0x0', 'int', '0'),
            ('+0o377', 'byte', '255'), ('0xFF', 'byte', '255'), ('-0x2 ** 2', 'int', '-4'),
            ('0x2 + 0o10', 'int', '10'), ('0x' + '0' * 100 + 'f', 'int', '15')]
    bad = [('0x8000000000000000', 'int', 'outside int64'),
           ('-0x8000000000000001', 'int', 'outside int64'),
           ('0o1000000000000000000000', 'int', 'outside int64'),
           ('-0o1000000000000000000001', 'int', 'outside int64'),
           ('0x100', 'byte', 'outside uint8'), ('-0o1', 'byte', 'outside uint8'),
           ('0x', 'int', 'digits'), ('0o', 'int', 'digits'),
           ('0o78', 'int', 'numeric literal'), ('0x1g', 'int', 'numeric literal'),
           ('0x1.0', 'int', 'numeric literal')]
    with service(app, output) as resident:
        for rev, (literal, kind, expected) in enumerate(good + bad + good[:1]):
            entry.write_text(source(flavor, literal, kind))
            os.utime(entry, (1990000000 + rev,) * 2)
            previous = artifacts(output)
            result = invoke(resident)
            baseline = work / f'fresh-{flavor}-{rev}'
            with service(app, baseline) as fresh:
                oracle = invoke(fresh)
                fresh.stdin.close()
            if rev < len(good) or rev == len(good) + len(bad):
                assert 'error' not in result and 'error' not in oracle, (literal, result, oracle)
                assert artifacts(output) == artifacts(baseline)
                build(output, work / ('native-' + flavor), expected.encode())
            else:
                assert expected in result.get('error', ''), (literal, result)
                assert result['error'] == oracle['error']
                assert artifacts(output) == previous
                assert invoke(resident)['error'] == result['error']
        resident.stdin.close()
    print(flavor, 'boundaries and incremental recovery passed', flush=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-radix-') as directory:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(directory), flavor)
