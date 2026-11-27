"Typed diagnostic identity, source location, and incremental ancestry recovery."
import argparse
from functools import partial
import json
import os
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import source_edit

ROOT = Path(__file__).resolve().parents[2]


def catch(flavor, type_name, name):
    binding = f'{name}: {type_name}' if flavor == 'js' else f'{type_name} {"$" if flavor == "php" else ""}{name}'
    return f'catch ({binding})'


def native(output, binary, expected, library, *, cxx_flags=()):
    manifest = json.loads((output / 'manifest.json').read_text())
    units = [output / name for name in manifest['translation_units']]
    subprocess.run(['clang++', '-std=c++23', *cxx_flags, '-Wall', '-Wextra', '-Werror',
                    '-I', ROOT / 'runtime/generated/public', *units,
                    library, '-o', binary], check=True)
    result = subprocess.run([binary], capture_output=True, timeout=10)
    assert (result.returncode, result.stdout, result.stderr) == expected, result


def fresh_build(app, output):
    fresh = service(app, output)
    try:
        return invoke(fresh)
    finally:
        fresh.stdin.close()
        assert fresh.wait() == 0


def prove(work, flavor, library, quick=False, run_native=native):
    s = fixture(flavor)
    v, access, field = s['v'], s['access'], s['field']

    def klass(name, body='', base=None):
        return s['record'](name, body, base).replace('struct ', 'class ', 1)

    def member(name, key):
        return f'{v}{name}{access}{key}'

    app = work / 'app'
    app.mkdir(parents=True)
    output = work / 'output'
    (app / 'module.json').write_text(json.dumps({'entry': f'main.{flavor}'}))
    base_path, main_path = (app / f'{name}.{flavor}' for name in ['base', 'main'])
    if flavor == 'php':
        core = 'use Exception as CoreFailure; ' + klass('Failure', base='CoreFailure')
    else:
        core = klass('Failure', base='Exception')
    source_edit(base_path, core)
    snapshot = 'first π\n  problem\n'
    snapshot_literal = json.dumps(snapshot, ensure_ascii=False)
    offset = snapshot.encode('utf-8').index(b'problem')
    expected = (0, f'Finput.phs:{offset}'.encode(), b'')
    catch_path = app / f'handler.{flavor}'
    catch_source = klass('UnrelatedFailure', base='Failure')
    source_edit(catch_path, catch_source)
    middle = app / f'middle.{flavor}'
    source_edit(middle, klass('DiagnosticBase', base='Failure'))
    source_edit(app / f'diagnostic.{flavor}',
                klass('Snapshot', field('path', 'string', '"input.phs"') +
                      field('content', 'string', snapshot_literal)) +
                klass('Diagnostic', field('origin', '?Snapshot') + field('offset', 'int') +
                      field('marker', 'int', '0'), 'DiagnosticBase'))
    source_edit(app / f'quiet.{flavor}', s['function']('quiet', 'int', [], 'return 42;'))
    def nullable_local(name, type_name, value):
        declaration = s['local'](name, '?' + type_name).removesuffix(';')
        return f'{declaration} = {value};'

    setup = (nullable_local('snapshot', 'Snapshot', 'new Snapshot()') +
             nullable_local('original', 'Diagnostic', 'new Diagnostic()') +
             nullable_local('retained', 'Diagnostic', 'null') +
             f'{member("original", "origin")} = {v}snapshot; {member("original", "offset")} = {offset};' +
             s['infer']('observe_diagnostic', f'weak({v}original)') +
             s['infer']('observe_snapshot', f'weak({v}snapshot)'))
    departing = s['infer']('departing', f'{v}original')
    missed = catch(flavor, 'UnrelatedFailure', 'missed')
    inner = catch(flavor, 'Diagnostic', 'inner')
    base = catch(flavor, 'Exception', 'base')
    outer = catch(flavor, 'Diagnostic', 'outer')
    good = (setup + f'''
        try {{
            try {{
                try {{
                    {departing}
                    {v}original = null;
                    {v}snapshot = null;
                    throw {v}departing;
                }} {missed} {{ return 2; }} {inner} {{
                    if ({v}inner !== lock({v}observe_diagnostic)) {{ return 3; }}
                    {member("inner", "marker")} = 91;
                    {v}inner = new Diagnostic();
                    throw;
                }} {catch(flavor, "Failure", "sibling")} {{ return 51; }} finally {{ echo "F"; }}
            }} {base} {{
                if ({member("base", "get_message")}() !== "Exception") {{ return 4; }}
                throw {v}base;
            }}
        }} {outer} {{
            if ({v}outer !== lock({v}observe_diagnostic) || {member("outer", "marker")} !== 91 ||
                {member("outer", "origin")} !== lock({v}observe_snapshot) || {member("outer", "offset")} !== {offset}) {{
                return 5;
            }}
            {v}retained = {v}outer;
        }}
        if ({v}retained === null || {v}retained !== lock({v}observe_diagnostic)) {{ return 6; }}
        if ({member("retained", "origin")}{access}path !== "input.phs" ||
            {member("retained", "origin")}{access}content !== {snapshot_literal}) {{
            return 7;
        }}
        echo {member("retained", "origin")}{access}path, ":", {member("retained", "offset")};
        {v}retained = null;
        if (lock({v}observe_diagnostic) !== null || lock({v}observe_snapshot) !== null) {{ return 8; }}
        return 0;
    ''')
    source_edit(main_path, good)
    resident = service(app, output)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        run_native(output, work / 'diagnostic', expected, library)
        before = artifacts(output)
        manifest = (output / 'manifest.json').read_bytes()

        def check_failed_edit(message, label):
            failure = invoke(resident)
            expected_failure = fresh_build(app, work / label)
            assert message in failure.get('error', ''), failure
            assert failure['error'] == expected_failure.get('error'), (failure, expected_failure)
            assert invoke(resident).get('error') == failure['error']
            assert (output / 'manifest.json').read_bytes() == manifest
            assert artifacts(output) == before

        source_edit(middle, klass('DiagnosticBase', base='UnrelatedFailure'))
        check_failed_edit('shadowed', 'fresh-shadowed')
        source_edit(middle, klass('DiagnosticBase', base='Failure'))
        restored = invoke(resident)
        assert 'error' not in restored and artifacts(output) == before, restored
        assert invoke(resident)['preparation']['prepared'] == 0

        source_edit(base_path, klass('Failure'))
        check_failed_edit('shared descendant', 'fresh-non-exception-base')

        base_path.unlink()
        check_failed_edit('Base must be a class', 'fresh-missing-base')

        source_edit(base_path, core)
        catch_path.unlink()
        check_failed_edit('shared descendant', 'fresh-missing-handler-type')
        source_edit(catch_path, catch_source)

        recovered = invoke(resident)
        assert 'error' not in recovered and recovered['preparation']['reused'] >= 1, recovered
        assert not any(path.endswith(f'/quiet.{flavor}') for path in recovered['emitted_sources']), recovered
        fresh_output = work / 'fresh'
        fresh_result = fresh_build(app, fresh_output)
        assert 'error' not in fresh_result, fresh_result
        assert artifacts(output) == artifacts(fresh_output) == before
        run_native(output, work / 'recovered', expected, library)

        source_edit(main_path, good.replace('= 91;', '= 91; echo "E";'))
        edited = invoke(resident)
        assert edited['preparation']['prepared'] == 1, edited
        assert [Path(path).name for path in edited['emitted_sources']] == [main_path.name], edited
        run_native(output, work / 'edited', (0, b'E' + expected[1], b''), library)
        source_edit(main_path, good)
        assert 'error' not in invoke(resident) and artifacts(output) == before

        invalid = [
            ('throw;', 'enclosing catch'),
            (s['function']('bad', 'void', [], 'throw;') + 'return 0;', 'enclosing catch'),
            ('try {} ' + base + ' {} finally { throw; } return 0;', 'enclosing catch'),
            ('try {} ' + inner + ' {} ' + inner + ' {} return 0;', 'shadowed'),
            ('try {} ' + base + ' {} ' + inner + ' {} return 0;', 'shadowed'),
            ('throw 3;', 'shared descendant'),
            ('try { return 0; } ' + catch(flavor, 'Snapshot', 'wrong') + ' { return 2; }', 'shared descendant'),
            ('try { return 0; } ' + base + ' {}', 'explicit value return'),
            ('try { echo "normal"; } ' + base + ' {} throw ' + v + 'base;', 'Unknown or not-yet-initialized local'),
        ]
        for text, message in invalid:
            source_edit(main_path, text)
            failed = invoke(resident)
            assert message in failed.get('error', ''), failed
            assert artifacts(output) == before

        source_edit(main_path, 'try { ' + s['infer']('zero', '0') + f'echo 1 / {v}zero; return 0; }} '
                    + base + ' { return 3; }')
        guard = invoke(resident)
        assert 'error' not in guard, guard
        run_native(output, work / 'guard', (1, b'', b'division_by_zero\n'), library)
        if quick:
            return

        factory_call = {
            'cpp': 'Exception::make',
            'php': 'RootFailure::make',
            'js': 'Exception.make',
        }[flavor]
        prefix = 'use Exception as RootFailure; ' if flavor == 'php' else ''
        # The inner finally runs during a second unwind. Its lexical catch still owns the first payload.
        source_edit(main_path, prefix + f'try {{ throw {factory_call}("first"); }} ' + base +
                    f' {{ try {{ throw {factory_call}("second"); }} finally {{ throw; }} }}')
        nested = invoke(resident)
        assert 'error' not in nested, nested
        run_native(output, work / 'nested', (1, b'', b'first\n'), library)

        source_edit(main_path, prefix + 'throw ' + factory_call + '("original\\0message");')
        factory = invoke(resident)
        assert 'error' not in factory, factory
        run_native(output, work / 'factory', (1, b'', b'original\0message\n'), library)

        constructor = 'RootFailure' if flavor == 'php' else 'Exception'
        message = s['function']('message', 'string', [], 'echo "M";return "original\\0message";')
        construction = prefix + message + f'try {{ throw new {constructor}(message()); }} ' + base + ' { throw; }'
        source_edit(main_path, construction)
        constructed = invoke(resident)
        assert 'error' not in constructed, constructed
        run_native(output, work / 'constructed', (1, b'M', b'original\0message\n'), library)
        before = artifacts(output)
        manifest = (output / 'manifest.json').read_bytes()

        for spelling, diagnostic in [
            ('', 'Argument count mismatch for Exception constructor'),
            ('"a", "b"', 'Argument count mismatch for Exception constructor'),
            ('1', 'Exception constructor requires string'),
        ]:
            source_edit(main_path, f'throw new Exception({spelling});')
            check_failed_edit(diagnostic, 'fresh-bad-constructor')
        source_edit(main_path, construction)
        recovered = invoke(resident)
        assert 'error' not in recovered and artifacts(output) == before, recovered
        clean = work / 'fresh-construction'
        result = fresh_build(app, clean)
        assert 'error' not in result and artifacts(clean) == artifacts(output), result
        assert invoke(resident)['preparation']['prepared'] == 0

        declaration = s['local']('empty_failure', 'Exception')
        source_edit(main_path, declaration + f'try {{ throw {v}empty_failure; }} ' + base + ' { return 3; }')
        absent = invoke(resident)
        assert 'error' not in absent, absent
        run_native(output, work / 'absent', (1, b'', b'absent_exception\n'), library)

        shadow = klass('Exception', field('shadow_marker', 'int', '7'))
        source_edit(main_path, shadow + s['infer']('local', 'new Exception()')
                    + f'echo {member("local", "shadow_marker")};return 0;')
        local = invoke(resident)
        assert 'error' not in local, local
        run_native(output, work / 'shadow-constructor', (0, b'7', b''), library)

        source_edit(main_path, klass('Exception') + 'throw new Exception();')
        shadowed = invoke(resident)
        assert 'shared descendant' in shadowed.get('error', ''), shadowed
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
        library = ROOT / 'build/clang/libscpp_runtime.a'
        for flavor in (['php'] if quick else ['cpp', 'php', 'js']):
            prove(work / flavor, flavor, library, quick, partial(native, cxx_flags=('-stdlib=libc++',)))
            print(f'{flavor}: diagnostic identity, original location, and ancestry recovery passed', flush=True)
