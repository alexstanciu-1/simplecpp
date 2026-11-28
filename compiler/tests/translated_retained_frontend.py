"""Drive one native frontend process through edits and compare its publication with a cold process."""
from contextlib import contextmanager
import fcntl
import json
from pathlib import Path
import select
import shutil
import subprocess
import sys
import tempfile

from translated_vector_recipe import ROOT, build, fresh


@contextmanager
def service(exe, app):
    proc = subprocess.Popen([exe, app], stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True)

    def request(command):
        proc.stdin.write(command + '\n')
        proc.stdin.flush()
        assert select.select([proc.stdout], [], [], 10)[0], 'frontend response timed out'
        line = proc.stdout.readline()
        assert line.endswith('\n'), (proc.poll(), line)
        return line.rstrip('\n')

    try:
        yield request
    finally:
        proc.stdin.close()
        try:
            assert proc.wait(timeout=5) == 0
        finally:
            if proc.poll() is None:
                proc.kill()
                proc.wait()
            proc.stdout.close()


def frontend_project(work, *, socket_service=False):
    examples = ROOT/'compiler/examples'
    tool = work/'tool'
    tool.mkdir()
    modules = ['filesystem'] if socket_service else ['filesystem', 'io']
    (tool/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {
        'runtime': {'modules': modules}}}))
    frontend = ['frontend', 'function_ast']
    session = ['session', 'frontend_requests']
    if socket_service:
        shutil.copyfile(examples/'retained_frontend/socket/main.phs', tool/'main.phs')
    else:
        frontend.append('compiler')
        session.extend(['main', 'lowering'])
    for name in frontend:
        shutil.copyfile(examples/f'translated_path/{name}.phs', tool/f'{name}.phs')
    for name in session:
        shutil.copyfile(examples/f'retained_frontend/{name}.phs', tool/f'{name}.phs')
    shutil.copyfile(examples/'tokenizer/utf8.phs', tool/'utf8.phs')
    with (tool/'punctuation.phs').open('wb') as output:
        subprocess.run(['php', examples/'tokenizer/emit_punctuation.php'], stdout=output, check=True)
    return tool


def verify_independent_callers(work, exe, cxx, linker, libraries):
    app = work/'two-callers'
    app.mkdir()
    sources = {'left.phs': 'function left(): int { return 1; }',
               'right.phs': 'function right(): int { return 7; }',
               'call_left.phs': 'function call_left(): int { return left(); }',
               'call_right.phs': 'function call_right(): int { return right(); }',
               'main.phs': 'return call_left();'}
    for name, text in sources.items():
        (app/name).write_text(text)
    output = app/'generated'

    def files(directory):
        return {p.name: (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns)
                for p in directory.glob('*.cpp')}

    def compare_fresh(label, expected):
        fresh = work/label
        fresh.mkdir()
        for path in app.glob('*.phs'):
            shutil.copyfile(path, fresh/path.name)
        with service(exe, fresh) as other:
            assert other('lower').startswith('ok|')
        assert {k: v[0] for k, v in files(output).items()} == {k: v[0] for k, v in files(fresh/'generated').items()}
        binary = work/(label+'-program')
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                        ROOT/'runtime/generated/public', *sorted(output.glob('*.cpp')), *libraries, '-o', binary], check=True)
        assert subprocess.run([binary], timeout=10).returncode == expected

    with service(exe, app) as run:
        def step(label, status, prepared, emitted, written, revision, right_revision=0):
            before = files(output)
            row = run('lower')
            assert row.split('|') == [status, prepared, emitted, written], (label, row)
            after = files(output)
            actual = {name for name in before.keys() | after.keys() if before.get(name) != after.get(name)}
            assert actual == set(filter(None, written.split(','))), (label, actual)
            assert run('binding-revisions') == f'call_left.phs={revision},call_right.phs={right_revision},'
            print(f'{label}: {row}')

        cold = run('lower')
        assert cold.startswith('ok|') and len(files(output)) == 6, cold
        assert run('binding-revisions') == 'call_left.phs=0,call_right.phs=0,'
        left = app/'left.phs'
        left.write_text(sources['left.phs'].replace('1', '2'))
        step('left body edit', 'ok', 'left.phs,', 'left.phs,', 'unit-left.phs.cpp,', 0)
        left.unlink()
        offset = sources['call_left.phs'].index('left();')
        error = f'call_left.phs:{offset}: Unknown function: left'
        step('left removed', error, 'call_left.phs,', '', '', 1)
        step('missing target retry', error, 'call_left.phs,', '', '', 1)
        left.write_text(sources['left.phs'].replace('1', '3'))
        step('left restored', 'ok', 'left.phs,call_left.phs,', 'left.phs,', 'unit-left.phs.cpp,', 2)
        step('settled repair', 'ok', '', '', '', 2)

        compare_fresh('fresh-independent', 3)

        switched = sources['call_left.phs'].replace('return left()', 'return right()')
        (app/'call_left.phs').write_text(switched)
        step('retarget left caller', 'ok', 'call_left.phs,', 'call_left.phs,', 'definition.cpp,', 0)
        left.unlink()
        step('old target removed', 'ok', '', '', 'unit-left.phs.cpp,', 0)

        right = app/'right.phs'
        right.unlink()
        error = f"call_left.phs:{switched.index('right();')}: Unknown function: right"
        step('shared target removed', error, 'call_left.phs,', '', '', 1, 1)
        step('shared target retry', error, 'call_left.phs,', '', '', 1, 1)
        right.write_text(sources['right.phs'].replace('7', '9'))
        step('shared target restored', 'ok', 'right.phs,call_left.phs,call_right.phs,',
             'right.phs,', 'unit-right.phs.cpp,', 2, 2)
        step('shared target settled', 'ok', '', '', '', 2, 2)
        compare_fresh('fresh-shared', 9)

    # A restarted publisher retires only its own obsolete units.
    external = output/'unit-external.phs.cpp'
    external.write_text('// externally owned')
    (app/'call_left.phs').write_text(sources['call_left.phs'])
    left.write_text(sources['left.phs'])
    (app/'call_right.phs').unlink()
    right.unlink()
    with service(exe, app) as restarted:
        assert restarted('lower').startswith('ok|')
    assert external.read_text() == '// externally owned'
    assert not (output/'unit-call_right.phs.cpp').exists() and not (output/'unit-right.phs.cpp').exists()



def verify_decimal_returns(work, exe, cxx, linker):
    app = work/'decimal-returns'
    app.mkdir()
    (app/'main.phs').write_text('return unrelated();')
    (app/'integer.phs').write_text('function unrelated(): int { return 7; }')
    (app/'forward.phs').write_text('function forwarded(): float64 { return sample(); }')
    source = app/'decimal.phs'
    generated = app/'generated'
    def write(literal):
        text = f'function sample(): float {{\n  return {literal};\n}}'
        source.write_text(text)
        return text.index(literal)

    def snapshot(path):
        return {p.name: (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns)
                for p in path.glob('*.cpp')}

    unit = 'unit-decimal.phs.cpp'
    forward_unit = 'unit-forward.phs.cpp'
    def bits(oracle=None):
        if oracle is None:
            units = [generated/unit, generated/forward_unit]
            name = 'source_' + 'forwarded'.encode().hex()
            declaration = f'double {name}();'
        else:
            bundle, manifest = oracle
            units = [bundle/path for source, path in manifest['sources'].items()
                     if Path(source).name in ('decimal.phs', 'forward.phs')]
            name = 'App::forwarded_'
            declaration = 'namespace App { double forwarded_(); }'
        probe = work/'observe.cpp'
        probe.write_text('#include <bit>\n#include <cstdio>\n' + declaration +
                         '\nint main() { std::printf("%016llx", std::bit_cast<unsigned long long>(' + name + '())); }')
        binary = work/'observe'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                        ROOT/'runtime/generated/public', *units, probe, '-o', binary], check=True)
        return subprocess.check_output([binary], text=True)

    write('- /* unary sign */ 1e-400')
    with service(exe, app) as request:
        assert request('lower').startswith('ok|')
        assert bits() == '8000000000000000'
        before = snapshot(generated)
        offset = write('1e400')
        error = f'decimal.phs:{offset}: Floating literal is outside float64 finite range|decimal.phs,||'
        assert request('lower') == error
        assert snapshot(generated) == before
        write('5e-324')
        assert request('lower') == f'ok|decimal.phs,|decimal.phs,|{unit},'
        after = snapshot(generated)
        assert {name for name in after if before[name] != after[name]} == {unit}
        assert bits() == '0000000000000001'
        assert request('lower') == 'ok|||'

        write('-1e-400')
        assert request('lower') == f'ok|decimal.phs,|decimal.phs,|{unit},'
        assert bits() == '8000000000000000'
        before_signature = snapshot(generated)
        assert {n for n in after if before_signature[n] != after[n]} == {unit}

        source.write_text('function sample(): int { return 9007199254740993; }')
        rewrites = f'ok|decimal.phs,forward.phs,|decimal.phs,forward.phs,|{unit},{forward_unit},'
        assert request('lower') == rewrites
        converted = snapshot(generated)
        assert {n for n in after if converted[n] != before_signature[n]} == {unit, forward_unit}
        (app/'module.json').write_text('{}')
        bundle = work/'php-decimal'
        oracle = fresh(app, bundle)
        assert bits() == bits((bundle, oracle)) == '4340000000000000'

        write('5e-324')
        assert request('lower') == rewrites
        after = snapshot(generated)
        assert {n for n in after if converted[n] != after[n]} == {unit, forward_unit}
        assert bits() == '0000000000000001'
        assert request('lower') == 'ok|||'

        # A live integer entry watches its callee's signature, including after failure.
        integer = app/'integer.phs'
        original = integer.read_text()
        integer.write_text('function unrelated(): float64 { return .5; }')
        for attempt in range(2):
            failure = request('lower')
            assert failure.startswith('main.phs:7: Expected int return from unrelated|'), failure
            assert snapshot(generated) == after
        integer.write_text(original)
        assert request('lower').startswith('ok|')
        assert snapshot(generated) == after

    fresh_app = work/'fresh-decimal'
    fresh_app.mkdir()
    for path in app.glob('*.phs'):
        shutil.copyfile(path, fresh_app/path.name)
    with service(exe, fresh_app) as request:
        assert request('lower').startswith('ok|')
    assert {k: v[0] for k, v in after.items()} == {
        k: v[0] for k, v in snapshot(fresh_app/'generated').items()}


def verify_checked_return(work, exe, cxx, linker):
    app = work/'checked-return'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return whole();')
    (app/'whole.phs').write_text('function whole(): int { return sample(); }')
    (app/'quiet.phs').write_text('function quiet(): int { return 7; }')
    sample = app/'sample.phs'
    generated = app/'generated'
    sample_unit = 'unit-sample.phs.cpp'

    def observe(units, production=False):
        name = 'App::whole_' if production else 'source_' + 'whole'.encode().hex()
        declaration = 'namespace App { scpp::int64 whole_(); }' if production else f'scpp::int64 {name}();'
        probe = work/'observe-int.cpp'
        probe.write_text('#include <scpp/core/scalars.hpp>\n#include <cstdio>\n#include <stdexcept>\n' +
                         declaration + '\nint main() { try { std::printf("%lld", static_cast<long long>(' +
                         name + '())); } catch (const std::out_of_range& e) { std::fputs(e.what(), stdout); } }')
        binary = work/'observe-int'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                        ROOT/'runtime/generated/public', *units, probe, *manifest['link_inputs'], '-o', binary], check=True)
        return subprocess.check_output([binary], text=True)

    def snapshot():
        return {p.name: (p.stat().st_ino, p.stat().st_mtime_ns, p.read_bytes())
                for p in generated.glob('*.cpp')}

    with service(exe, app) as request:
        sample.write_text('function sample(): float { return -3.75; }')
        assert request('lower').startswith('ok|')
        units = [generated/'definition.cpp', generated/sample_unit]
        bundle = work/'production-int'
        manifest = fresh(app, bundle)
        php_units = [bundle/unit for path, unit in manifest['sources'].items()
                     if Path(path).name in ('whole.phs', 'sample.phs')]
        assert observe(units) == observe(php_units, production=True) == '-3'
        for literal, expected in [('9223372036854775808.0', 'integer_cast_out_of_range'),
                                  ('-9223372036854775808.0', '-9223372036854775808')]:
            before = snapshot()
            sample.write_text(f'function sample(): float {{ return {literal}; }}')
            assert request('lower') == f'ok|sample.phs,|sample.phs,|{sample_unit},'
            assert {n for n, v in snapshot().items() if v != before[n]} == {sample_unit}
            assert observe(units) == expected

        before = snapshot()
        sample.write_text('function sample(): int { return 19; }')
        assert request('lower') == f'ok|sample.phs,whole.phs,|whole.phs,sample.phs,|definition.cpp,{sample_unit},'
        after = snapshot()
        assert {n for n in after if after[n] != before[n]} == {'definition.cpp', sample_unit}
        assert 'checked_float64_to_int64' not in units[0].read_text()
        assert observe(units) == '19'
        assert request('lower') == 'ok|||'

    cold = work/'fresh-checked'
    cold.mkdir()
    for source in app.glob('*.phs'):
        shutil.copyfile(source, cold/source.name)
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|')
    assert {p.name: p.read_bytes() for p in (cold/'generated').glob('*.cpp')} == {n: v[2] for n, v in after.items()}


def verify_literal_returns(work, exe, cxx, linker, adapter, radix):
    app = work/'literal-returns'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    (app/'caller.phs').write_text('function caller(): int { return whole(); }')
    (app/'rounded.phs').write_text('function rounded(): float { return 9007199254740993; }')
    prefix = 'function whole(): int {\n    return '
    whole = app/'whole.phs'
    def write(literal):
        whole.write_text(prefix + literal + ';\n}')

    out = app/'generated'
    unit = 'unit-whole.phs.cpp'
    def snapshot():
        return {p.name: (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns) for p in out.glob('*.cpp')}

    def observe(production=False):
        if production:
            bundle = work/'php-output'
            manifest = fresh(app, bundle)
            units = [bundle/v for k, v in manifest['sources'].items() if Path(k).stem in ('caller', 'whole', 'rounded')]
            declarations = 'namespace App { scpp::int64 caller_(); double rounded_(); }'
            caller, rounded = 'App::caller_', 'App::rounded_'
        else:
            units = [out/'definition.cpp', out/unit, out/'unit-rounded.phs.cpp']
            caller, rounded = ['source_' + s.encode().hex() for s in ('caller', 'rounded')]
            declarations = f'scpp::int64 {caller}(); double {rounded}();'
        probe = work/'probe.cpp'
        probe.write_text('#include <scpp/core/scalars.hpp>\n#include <bit>\n#include <cstdio>\n' +
                         declarations + '\nint main() { std::printf("%lld %016llx", static_cast<long long>(' +
                         caller + '()), std::bit_cast<unsigned long long>(' + rounded + '())); }')
        binary = work/'probe'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', '-Werror', f'--ld-path={linker}', '-I',
                        ROOT/'runtime/generated/public', *units, probe, '-o', binary], check=True)
        return subprocess.check_output([binary], text=True)

    def decimal_edits(request):
        assert request('lower').startswith('ok|')
        assert observe() == observe(True) == '-3 4340000000000000'
        before = snapshot()
        write('9223372036854775808.0')
        message = 'Floating conversion is outside int64 range'
        assert request('lower') == f'whole.phs:{len(prefix)}: {message}|whole.phs,||'
        assert snapshot() == before
        facts = Path(adapter).parent/'native-layout/facts.json'
        rejected = subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, facts, work/'overflow'],
                                  capture_output=True, text=True)
        assert rejected.returncode and f'whole.phs:2:12: {message}' in rejected.stderr, rejected
        write('-9223372036854775808.0')
        assert request('lower') == f'ok|whole.phs,|whole.phs,|{unit},'
        repaired = snapshot()
        assert {n for n in before if before[n] != repaired[n]} == {unit}
        assert observe() == observe(True) == '-9223372036854775808 4340000000000000'
        assert request('lower') == 'ok|||'
        rounded = app/'rounded.phs'
        rounded.write_text('function rounded(): float { return -9223372036854775809; }')
        assert request('lower') == (f'rounded.phs:{rounded.read_text().index("-")}: '
                                    'Integer literal is outside int64 range|rounded.phs,||')
        assert snapshot() == repaired
        rounded.write_text('function rounded(): float { return -9223372036854775808; }')
        assert request('lower') == 'ok|rounded.phs,|rounded.phs,|unit-rounded.phs.cpp,'
        assert observe() == '-9223372036854775808 c3e0000000000000'
        rounded.write_text('function rounded(): float { return 9007199254740993; }')
        assert request('lower') == 'ok|rounded.phs,|rounded.phs,|unit-rounded.phs.cpp,'
        return repaired

    def radix_edits(request):
        for literal, emitted in [('0X7FFFFFFFFFFFFFFF', '9223372036854775807LL'),
                                 ('-0O1000000000000000000000', '(-9223372036854775807LL - 1LL)')]:
            write(literal)
            assert request('lower') == f'ok|whole.phs,|whole.phs,|{unit},'
            assert emitted in (out/unit).read_text()
        write('0x1e')
        assert request('lower') == f'ok|whole.phs,|whole.phs,|{unit},'
        assert observe() == observe(True) == '30 4340000000000000'
        before = snapshot()
        write('0x')
        error = f'whole.phs:{len(prefix)}: Expected integer digits after radix prefix|||'
        assert request('lower') == error
        assert request('lower') == error
        assert snapshot() == before
        write('0o17')
        assert request('lower') == f'ok|whole.phs,|whole.phs,|{unit},'
        repaired = snapshot()
        assert {n for n in before if before[n] != repaired[n]} == {unit}
        assert observe() == observe(True) == '15 4340000000000000'
        assert request('lower') == 'ok|||'
        for literal in ('0x8000000000000000', '-0O1000000000000000000001'):
            write(literal)
            assert request('lower') == (f'whole.phs:{len(prefix)}: Integer literal is outside int64 range'
                                        '|whole.phs,||')
            assert snapshot() == repaired
        write('0o17')
        return repaired

    write('1' if radix else '-3.75')
    with service(exe, app) as request:
        if radix:
            assert request('lower').startswith('ok|')
            repaired = radix_edits(request)
        else:
            repaired = decimal_edits(request)
    cold = work/'cold'
    cold.mkdir()
    for path in app.glob('*.phs'):
        shutil.copyfile(path, cold/path.name)
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|')
    assert {p.name: p.read_bytes() for p in (cold/'generated').glob('*.cpp')} == {n: v[0] for n, v in repaired.items()}


def verify(work, cxx, linker, adapter, selection=None):
    tool = frontend_project(work)
    config = tool/'module.json'
    module = json.loads(config.read_text())
    module['dependencies']['compiler-private-frontend'] = {'path': adapter}
    config.write_text(json.dumps(module))
    bundle, exe = work/'bundle', work/'frontend'
    manifest = fresh(tool, bundle)
    build(bundle, exe, cxx, linker, ['-I', ROOT/'compiler/examples/retained_frontend/socket', *manifest['link_inputs']])

    if selection and selection.startswith("--edit-sequence="):
        from retained_edit_sequences import verify as verify_edits
        verify_edits(work, exe, cxx, linker, adapter, service, selection.split("=", 1)[1])
        return
    if selection == "--numeric-defaults":
        from retained_parameter_incremental import verify_defaults
        verify_defaults(work, exe, cxx, linker, adapter, service)
        return
    if selection == "--default-rebinding":
        from retained_parameter_incremental import verify_default_rebinding
        verify_default_rebinding(work, exe, cxx, linker, adapter, service)
        return
    if selection == "--call-retargeting":
        from retained_parameter_incremental import verify_call_retargeting
        verify_call_retargeting(work, exe, cxx, linker, adapter, service)
        return
    if selection == "--missing-call-repair":
        from retained_parameter_incremental import verify_missing_call_repair
        verify_missing_call_repair(work, exe, cxx, linker, adapter, service)
        return
    if selection == "--named-arguments":
        from retained_parameter_incremental import verify_named
        verify_named(work, exe, cxx, linker, adapter, service)
        return
    if selection == "--multiple-parameters":
        from retained_parameter_incremental import verify_multiple
        verify_multiple(work, exe, cxx, linker, adapter, service)
        return
    if selection in ("--parameter-incremental", "--parameter-forwarding"):
        from retained_parameter_incremental import verify as verify_parameter
        verify_parameter(work, exe, cxx, linker, adapter, service, forwarding=selection == "--parameter-forwarding")
        return
    if selection in ("--literal-returns", "--radix-returns"):
        verify_literal_returns(work, exe, cxx, linker, adapter, radix=selection == "--radix-returns")
        return
    if selection == "--checked-return":
        verify_checked_return(work, exe, cxx, linker)
        return
    verify_decimal_returns(work, exe, cxx, linker)
    verify_independent_callers(work, exe, cxx, linker, manifest['link_inputs'])

    app = work/'app'
    app.mkdir()
    definition = app/'definition.phs'
    main = app/'main.phs'
    main.write_text('return answer();')
    first = 'function answer(): int { return 1; }'
    definition.write_text(first)
    both, one, caller = 'definition.phs,main.phs,', 'definition.phs,', 'main.phs,'
    with service(exe, app) as run:
        def step(status, generation, attempted, reused):
            row = run('run')
            assert row.split('|') == [status, str(generation), attempted, attempted, reused], row
            print(row)
        step('ok', 1, both, '')
        initial = run('snapshot')
        step('ok', 2, '', both)
        assert run('snapshot') == initial
        definition.write_text(first.replace('1', '2'))
        step('ok', 3, one, caller)
        published = run('snapshot')
        assert published != initial
        invalid = first.replace('1;', '3')
        definition.write_text(invalid)
        step(f'definition.phs:{invalid.index("}")}: Expected ;', 3, one, both)
        assert run('snapshot') == published
        definition.write_text(first.replace('1', '3'))
        step('ok', 4, one, caller)
        repaired = run('snapshot')
        assert repaired != published
        with service(exe, app) as cold:
            assert cold('run').split('|') == ['ok', '1', both, both, '']
            assert cold('snapshot') == repaired
        # A valid first candidate must also roll back if parsing the second file fails.
        definition.write_text(first)
        main.write_text('return answer()')
        step('main.phs:15: Expected ;', 4, both, both)
        assert run('snapshot') == repaired
        definition.unlink()
        step('definition.phs:0: Cannot read source', 4, '', both)
        definition.write_bytes(b'\xff')
        assert run('run').split('|') == ['definition.phs:0: Invalid UTF-8 source', '4', one, '', both]
        assert run('snapshot') == repaired

    definition.write_text(first)
    main.write_text('return answer();')
    with service(exe, app) as run:
        def prepare(status, frontend, prepared, parsed, work_done, reused):
            row = run('prepare')
            assert row.split('|') == [status, str(frontend), str(prepared), parsed, work_done, reused], row
            print(row)

        prepare('ok', 1, 1, both, both, '')
        prepare('ok', 2, 2, '', '', both)
        definition.write_text(first.replace('1', '2'))
        prepare('ok', 3, 3, one, one, caller)
        good = run('prepared-snapshot')
        assert good.startswith('answer|func:answer|2|')
        definition.write_text(first.replace('1', '9223372036854775808'))
        error = f'definition.phs:{first.index("1")}: Integer literal is outside int64 range'
        prepare(error, 4, 3, one, one, both)
        assert run('prepared-snapshot') == good
        prepare(error, 5, 3, '', one, both)
        definition.write_text(first.replace('1', '3'))
        prepare('ok', 6, 6, one, one, caller)
        repaired = run('prepared-snapshot')
        assert repaired.startswith('answer|func:answer|3|') and repaired != good
        assert b'return 3LL;' in bytes.fromhex(repaired.split('|')[-1])
        with service(exe, app) as cold:
            assert cold('prepare').split('|') == ['ok', '1', '1', both, both, '']
            assert cold('prepared-snapshot') == repaired

        # A changed public name invalidates an unchanged caller; both candidates publish together.
        definition.write_text(first.replace('answer', 'other'))
        prepare('main.phs:7: Unknown function: answer', 7, 6, one, both, both)
        assert run('prepared-snapshot') == repaired
        main.write_text('return other();')
        prepare('ok', 8, 8, caller, both, '')
        assert run('prepared-snapshot').startswith('other|func:other|1|')

    definition.write_text(first)
    main.write_text('return answer();')
    generated = app/'generated'
    cpp = ['definition.cpp', 'main.cpp', 'entry.cpp']

    def artifacts():
        if not generated.exists():
            return {}
        assert all(p.name in [*cpp, '.scpp-bundle.lock'] for p in generated.iterdir())
        return {n: ((generated/n).read_bytes(), (generated/n).stat().st_ino, (generated/n).stat().st_mtime_ns)
                for n in cpp if (generated/n).is_file()}

    with service(exe, app) as run:
        def lower(case, status, prepared, emitted, changed):
            before = artifacts()
            row = run('lower')
            assert row.split('|') == [status, prepared, emitted, changed], (case, row)
            after = artifacts()
            actual = [n for n in cpp if before.get(n) != after.get(n)]
            expected = changed.rstrip(',').split(',') if changed else []
            assert actual == expected, (case, actual)
            print(f'{case}: {row}')

        lower('cold', 'ok', both, both, 'definition.cpp,main.cpp,entry.cpp,')
        lower('unchanged', 'ok', '', '', '')
        definition.write_text(first.replace('1', '2'))
        lower('body edit', 'ok', one, one, 'definition.cpp,')
        definition.write_text(definition.read_text() + ' // comment')
        lower('comment edit', 'ok', one, one, '')
        definition.write_text(first.replace('1', '9223372036854775808'))
        lower('preparation error', error, one, '', '')
        definition.write_text(first.replace('1', '3'))
        lower('repair', 'ok', one, one, 'definition.cpp,')
        definition.write_text(first.replace('1', '4'))
        with (generated/'.scpp-bundle.lock').open('rb') as reader:
            fcntl.flock(reader, fcntl.LOCK_SH)
            lower('publication blocked', 'Output bundle busy', one, one, '')
        lower('retry same input', 'ok', '', one, 'definition.cpp,')
        lower('settled', 'ok', '', '', '')

        # Make the second destination invalid. The first replacement has already happened.
        saved = artifacts()
        definition.write_text(first.replace('answer', 'renamed').replace('1', '5'))
        main.write_text('return renamed();')
        (generated/'main.cpp').rename(work/'saved-caller')
        (generated/'main.cpp').mkdir()
        lower('rollback during publication', 'Invalid output file', both, both, '')
        (generated/'main.cpp').rmdir()
        (work/'saved-caller').rename(generated/'main.cpp')
        assert artifacts() == saved
        lower('retry after rollback', 'ok', '', both, 'definition.cpp,main.cpp,')
        duplicate = app/'alternate.phs'
        duplicate.write_text(definition.read_text().replace('5', '6'))
        lower('two files declare renamed', 'definition.phs:9: Duplicate function: renamed; first declaration at alternate.phs:1:10',
              '', '', '')
        definition.unlink()
        lower('keep alternate', 'ok', 'alternate.phs,', 'alternate.phs,', 'definition.cpp,')
        lower('unchanged survivor', 'ok', '', '', '')
        fresh_app = work/'cold-app'
        fresh_app.mkdir()
        for name in ('alternate.phs', 'main.phs'):
            shutil.copyfile(app/name, fresh_app/name)
        with service(exe, fresh_app) as cold:
            assert cold('lower').split('|') == ['ok', 'alternate.phs,main.phs,', 'alternate.phs,main.phs,', 'definition.cpp,main.cpp,entry.cpp,']
        assert all((generated/n).read_bytes() == (fresh_app/'generated'/n).read_bytes() for n in cpp)
        binary = work/'lowered-program'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                        ROOT/'runtime/generated/public', *(generated/n for n in cpp),
                        *manifest['link_inputs'], '-o', binary], check=True)
        assert subprocess.run([binary], timeout=10).returncode == 6


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-retained-frontend-') as tmp:
        verify(Path(tmp), *sys.argv[1:])
