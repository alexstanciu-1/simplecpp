"""Lazy throw fallbacks preserve the successful value and transport the original diagnostic."""
import json
import os
from pathlib import Path
import argparse
import tempfile

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from exceptions_native import catch, fresh_build, native
from extends_native import runtime, source_edit


def prove(work, flavor, library, cxx_flags=()):
    form = fixture(flavor)
    v, access, function = form['v'], form['access'], form['function']
    app = work / 'app'
    app.mkdir(parents=True)
    output = work / 'output'
    entry = app / f'main.{flavor}'
    provider = app / f'failure.{flavor}'
    source_edit(app / 'module.json', json.dumps({'entry': entry.name}))
    diagnostic = form['record']('Diagnostic', form['field']('offset', 'int', '17'),
                                'Exception').replace('struct ', 'class ', 1)
    source_edit(app / f'diagnostic.{flavor}', diagnostic)
    make_failure = function('failure', 'Diagnostic', [('diag', 'Diagnostic')], f'echo "F:"; return {v}diag;')
    source_edit(provider, make_failure)
    source_edit(app / f'quiet.{flavor}', function('quiet', 'int', [], 'return 1;'))

    def local(name, kind, value):
        return form['local'](name, kind).removesuffix(';') + f' = {value};'

    thrown = f'throw failure({v}diag)'
    observed = function('read', '?int', [('n', '?int')], f'echo "L:"; return {v}n;')
    subject = function('readKey', 'int', [('n', 'int')], f'echo "K:"; return {v}n;')
    switch_pick = function('switchPick', 'int', [('n', 'int'), ('diag', 'Diagnostic')], f'''
        return match (readKey({v}n)) {{ default => {thrown}, 1 => 21 }};
    ''')
    condition_pick = function('conditionPick', 'int', [('n', 'int'), ('diag', 'Diagnostic')], f'''
        try {{
            return match (true) {{ default => {thrown}, {v}n === 2 => 22 }};
        }} finally {{ echo "cleanup:"; }}
    ''')
    checked = catch(flavor, 'Diagnostic', 'caught')
    identity = f'if ({v}caught !== {v}diag || {v}caught{access}offset !== 17) {{ return 5; }}'
    text = observed + subject + switch_pick + condition_pick + local('diag', 'Diagnostic', 'new Diagnostic()')
    text += local('zero', '?int', '0') + local('absent', '?int', 'null')
    text += local('flag', '?bool', 'false') + local('empty', '?string', '""')
    text += f'''
        if (({v}flag ?? {thrown}) !== false || ({v}empty ?? {thrown}) !== "") {{ return 2; }}
        echo read({v}zero) ?? {thrown},":";
        echo {v}absent ?? {v}zero ?? {thrown},":";
        try {{
            echo read({v}absent) ?? {thrown};
            return 3;
        }} {checked} {{ {identity} echo "coalesce:"; }}
        echo switchPick(1,{v}diag),":";
        try {{ echo switchPick(0,{v}diag); return 4; }}
        {checked} {{ {identity} echo "match:"; }}
        echo conditionPick(2,{v}diag),":";
        try {{ echo conditionPick(0,{v}diag); return 6; }}
        {checked} {{ {identity} echo "done"; }}
        return 0;
    '''
    source_edit(entry, text)
    resident = service(app, output)
    try:
        result = invoke(resident)
        assert 'error' not in result, result
        expected = (0, b'L:0:0:L:F:coalesce:K:21:K:F:match:cleanup:22:F:cleanup:done', b'')
        native(output, work / 'lazy', expected, library, cxx_flags=cxx_flags)
        before = artifacts(output)
        manifest = (output / 'manifest.json').read_bytes()

        def check_failure(fragment, label):
            failure = invoke(resident)
            assert fragment in failure.get('error', ''), failure
            comparison = fresh_build(app, work / label)
            assert failure['error'] == comparison.get('error'), (failure, comparison)
            assert invoke(resident).get('error') == failure['error']
            assert artifacts(output) == before and (output / 'manifest.json').read_bytes() == manifest

        source_edit(provider, function('failure', 'int', [('diag', 'Diagnostic')], 'return 1;'))
        check_failure('shared descendant', 'fresh-invalid-type')
        provider.unlink()
        check_failure('Unknown function: failure', 'fresh-missing-target')
        source_edit(provider, make_failure)
        recovered = invoke(resident)
        assert 'error' not in recovered and recovered['preparation']['reused'] >= 1, recovered
        assert f'app/quiet.{flavor}' not in recovered['emitted_sources']
        fresh_output = work / 'fresh-recovered'
        assert 'error' not in fresh_build(app, fresh_output)
        assert artifacts(output) == artifacts(fresh_output) == before
        native(output, work / 'recovered', expected, library, cxx_flags=cxx_flags)

        prefix = local('diag', 'Diagnostic', 'new Diagnostic()') + local('present', '?int', '1')
        invalid = [
            (f'echo {v}present ?? throw 3; return 0;', 'shared descendant'),
            (f'echo 1 ?? {thrown}; return 0;', 'published nullable carrier'),
            (f'echo match (1) {{ default => {thrown} }}; return 0;', 'requires a value arm'),
            (f'echo match (1) {{ 1 => 7, default => throw 3 }}; return 0;', 'shared descendant'),
            (f'echo match (1) {{ 1 => 7, 2 => "other", default => {thrown} }}; return 0;', 'types must be identical'),
            (f'echo match (1) {{ 1 => {thrown}, default => 7 }}; return 0;', 'requires a ?? fallback or match default'),
            (f'return ({thrown});', 'requires a ?? fallback or match default'),
            (f'{v}present ??= {thrown}; return 0;', 'requires a ?? fallback or match default'),
        ]
        for source, fragment in invalid:
            source_edit(entry, prefix + source)
            failure = invoke(resident)
            assert fragment in failure.get('error', ''), (source, failure)
            assert artifacts(output) == before and (output / 'manifest.json').read_bytes() == manifest
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    os.environ['XDEBUG_MODE'] = 'off'
    with tempfile.TemporaryDirectory() as directory:
        work = Path(directory)
        library = Path(__file__).resolve().parents[2]/'build/clang/libscpp_runtime.a' if quick else runtime(work, 'clang++')
        for flavor in (['php'] if quick else ['cpp', 'php', 'js']):
            prove(work / flavor, flavor, library, ("-stdlib=libc++",) if quick else ())
            print(f'{flavor}: lazy throw fallbacks, typed identity, and incremental recovery passed', flush=True)
