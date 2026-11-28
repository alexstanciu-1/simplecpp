"""Observe retained body work, real object rebuilds, and both callers' native results."""
import json
from pathlib import Path
import shutil
import subprocess

from translated_vector_recipe import ROOT


def native(directory, units, links, expected, results, php, cxx, linker):
    directory.mkdir(exist_ok=True)
    graph = directory/'build.ninja'
    if not graph.exists():
        # Compile every emitted unit with normal header dependencies. Link a separate
        # observer so both direct callers execute without narrowing int64 to an exit code.
        names = list(results)
        symbols = [name+'_' if php else 'source_'+name.encode().hex() for name in names]
        declarations = ''.join(f'scpp::int64 {name}();' for name in symbols)
        if php:
            declarations = 'namespace App {' + declarations + '}'
            symbols = ['App::'+name for name in symbols]
        (directory/'probe.cpp').write_text('#include <scpp/core/scalars.hpp>\n#include <cstdio>\n' +
            declarations + '\nint main() { std::printf("' + ' '.join(['%lld'] * len(names)) + '", ' +
            ', '.join(f'static_cast<long long>({symbol}())' for symbol in symbols) + '); }')
    rules = [f'rule cc\n  command = {cxx} -std=c++23 -stdlib=libc++ -I{ROOT}/runtime/generated/public -MMD -MF $out.d -c $in -o $out\n  depfile = $out.d\n  deps = gcc',
             f'rule link\n  command = {cxx} -stdlib=libc++ --ld-path={linker} $in -o $out']
    rules += [f'build {name}.o: cc {src}' for name, src in units.items()]
    subjects = ' '.join(name+'.o' for name in units if name not in ('entry', 'main'))
    rules += ['build probe.o: cc probe.cpp',
              f'build observer: link probe.o {subjects} ' + ' '.join(links),
              'default observer ' + ' '.join(name+'.o' for name in units)]
    graph_text = '\n'.join(rules)+'\n'
    if not graph.exists() or graph.read_text() != graph_text:
        graph.write_text(graph_text)
    log = directory/'.ninja_log'
    offset = log.stat().st_size if log.exists() else 0
    subprocess.run(['ninja', '-C', directory], check=True, stdout=subprocess.PIPE)
    edges = [line.split('\t')[3] for line in log.read_bytes()[offset:].decode().splitlines()
             if not line.startswith('#')]
    if expected is not None:
        assert set(edges) == {name+'.o' for name in expected} | ({'observer'} if expected - {'main', 'entry'} else set()), edges
    assert subprocess.check_output([directory/'observer'], text=True) == ' '.join(map(str, results.values()))


def emitted_units(app, facts, production, retained, definition_name='caller'):
    subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, facts, production],
                   check=True, stdout=subprocess.PIPE)
    manifest = json.loads((production/'manifest.json').read_text())
    by_unit = {v: Path(k).stem for k, v in manifest['sources'].items()}
    php_units = {by_unit.get(path, Path(path).stem): production/path for path in manifest['translation_units']}
    retained_units = {p.name.removeprefix('unit-').removesuffix('.cpp').removesuffix('.phs'): p
                      for p in retained.glob('*.cpp')}
    retained_units[definition_name] = retained_units.pop('definition')
    return retained_units, php_units, manifest['link_inputs']


def verify(work, exe, cxx, linker, adapter, service, forwarding=False):
    app = work/'parameters'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    target = 'relay' if forwarding else 'identity'
    if forwarding:
        (app/'relay.phs').write_text('function relay(int $value): int { return identity($value); }')
    dependents = {'identity', 'relay'} if forwarding else {'identity', 'caller', 'large'}
    for name, operand in [('caller', f'{target}(7)'), ('large', f'{target}(9007199254740993)'), ('quiet', '42')]:
        (app/f'{name}.phs').write_text(f'function {name}(): int {{ return {operand}; }}')
    definition = app/'identity.phs'
    retained = app/'generated'
    production = work/'php-bundle'
    facts = Path(adapter).parent/'native-layout/facts.json'

    with service(exe, app) as request:
        stages = [
            ('cold', 'int', '$value', None, 9007199254740993),
            ('signature', 'float', '$value', dependents, 9007199254740992),
            ('rename', 'float', '$renamed', {'identity'}, 9007199254740992),
            ('repair', 'int', '$value', dependents, 9007199254740993),
            ('unchanged', 'int', '$value', set(), 9007199254740993),
        ]
        if forwarding:
            # Exercise the opposite argument conversion too: relay receives a float
            # and forwards it to the repaired integer identity.
            relay_and_callers = {'relay', 'caller', 'large'}
            stages.extend([('float-relay', 'int', '$value', relay_and_callers, 9007199254740992),
                           ('restore-relay', 'int', '$value', relay_and_callers, 9007199254740993)])
        for stage, parameter, body, expected, large in stages:
            if stage in ('float-relay', 'restore-relay'):
                relay_type = 'float' if stage == 'float-relay' else 'int'
                (app/'relay.phs').write_text(f'function relay({relay_type} $value): int {{ return identity($value); }}')
            elif stage != 'unchanged':
                definition.write_text(f'function identity({parameter} {body}): int {{ return {body}; }}')
            status, prepared, emitted, written = request('lower').split('|')
            assert status == 'ok', status
            if expected is not None:
                assert set(filter(None, prepared.split(','))) == {n+'.phs' for n in expected}, prepared
                assert set(filter(None, emitted.split(','))) == {n+'.phs' for n in expected}, emitted
            retained_units, php_units, links = emitted_units(app, facts, production, retained)
            results = {'caller': 7, 'large': large}
            # A rename changes the PHP parameter spelling in C++. The retained unit
            # uses a positional name, so its bytes and object stay identical.
            retained_expected = set() if stage == 'rename' else expected
            if retained_expected is not None:
                assert set(filter(None, written.split(','))) == {retained_units[n].name for n in retained_expected}
            native(work/'retained-objs', retained_units, links, retained_expected, results, False, cxx, linker)
            # Production publishes parameter names in the shared header, so its
            # dependent objects also rebuild on this name-only edit.
            php_expected = dependents if stage == 'rename' else expected
            native(work/'php-objs', php_units, links, php_expected, results, True, cxx, linker)

        definition.write_text('function identity(): int { return 7; }')
        saved = {p.name: p.read_bytes() for p in retained.glob('*.cpp')}
        for _ in range(2):
            failure = request('lower')
            assert 'Wrong argument count for identity' in failure and failure.endswith('||'), failure
        definition.write_text('function identity(int $value): int { return $value; }')
        assert request('lower').startswith('ok|')
        assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved
        if forwarding:
            relay = app/'relay.phs'
            original = relay.read_text()
            relay.write_text(original.replace('identity($value)', 'identity($missing)'))
            failed = request('lower')
            assert f'relay.phs:{relay.read_text().index("$missing")}: Unknown parameter' in failed and failed.endswith('||'), failed
            relay.write_text(original)
            assert request('lower').startswith('ok|')
            assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved
    cold = work/'fresh-parameters'
    cold.mkdir()
    for path in app.glob('*.phs'):
        shutil.copyfile(path, cold/path.name)
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|')
        assert {p.name: p.read_bytes() for p in (cold/'generated').glob('*.cpp')} == saved
        (cold/'main.phs').write_text('return identity(7);')
        assert request('lower').startswith('ok|')
        (cold/'identity.phs').write_text('function identity(float $value): int { return $value; }')
        status, prepared, emitted, written = request('lower').split('|')
        assert status == 'ok' and 'main.phs,' in prepared and 'main.phs,' in emitted and 'main.cpp,' in written
        main = (cold/'generated/main.cpp').read_text()
        assert 'static_cast<scpp::float64>(7LL)' in main
        assert request('lower') == 'ok|||'
    # Previously published bytes on the original project remain intact.
    assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved


def verify_multiple(work, exe, cxx, linker, adapter, service):
    app = work/'multiple'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    (app/'caller.phs').write_text('function caller(): int { return relay(9007199254740993); }')
    (app/'relay.phs').write_text('function relay(int $value): int { return pick(7, $value); }')
    # A separate three-slot call checks that forwarded arguments use the caller's slot.
    (app/'third.phs').write_text('function third(int $a, float $b, int $c): int { return $c; }')
    (app/'swap.phs').write_text('function swap(int $a, int $b): int { return third($a, 0, $b); }')
    (app/'quiet.phs').write_text('function quiet(): int { return swap(5, -17); }')
    pick = app/'pick.phs'
    retained = app/'generated'
    signature = 'function pick(int $first, {} $second): int {{ return $second; }}'
    affected = {'pick', 'relay'}
    with service(exe, app) as request:
        for kind, changes, result in [('int', None, 9007199254740993), ('float', affected, 9007199254740992),
                                      ('int', affected, 9007199254740993), (None, set(), 9007199254740993)]:
            if kind:
                pick.write_text(signature.format(kind))
            status, prepared, emitted, written = request('lower').split('|')
            assert status == 'ok', status
            ru, pu, links = emitted_units(app, Path(adapter).parent/'native-layout/facts.json', work/'production', retained)
            if changes is not None:
                for paths in (prepared, emitted):
                    assert set(filter(None, paths.split(','))) == {n+'.phs' for n in changes}, paths
                assert set(filter(None, written.split(','))) == {ru[n].name for n in changes}, written
            results = {'caller': result, 'quiet': -17}
            native(work/'retained-objects', ru, links, changes, results, False, cxx, linker)
            native(work/'production-objects', pu, links, changes, results, True, cxx, linker)

        saved = {p.name: p.read_bytes() for p in retained.glob('*.cpp')}
        for invalid, diagnostic in [
            ('function pick(int $first): int { return $first; }', 'Wrong argument count for pick'),
            ('function pick(int $first, int $first): int { return $first; }', 'Duplicate parameter: $first'),
            ('function pick(int $a, int $b,): int { return $b; }', 'Trailing parameter comma is deferred'),
            ('function pick(int $a, int $b): int { return relay($a,); }', 'Trailing argument comma is deferred'),
        ]:
            pick.write_text(invalid)
            failure = request('lower')
            assert diagnostic in failure and failure.endswith('||'), failure
            assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved
        pick.write_text(signature.format('int'))
        assert request('lower').startswith('ok|')
        assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved
    cold = work/'cold'
    cold.mkdir()
    for source in app.glob('*.phs'):
        shutil.copyfile(source, cold/source.name)
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|')
    assert {p.name: p.read_bytes() for p in (cold/'generated').glob('*.cpp')} == saved


def verify_named(work, exe, cxx, linker, adapter, service):
    app = work/'named'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    (app/'caller.phs').write_text('function caller(): int { return pick(7, 11); }')
    pick, named = app/'pick.phs', app/'labeled.phs'
    pick.write_text('function pick(int $data, int $second): int { return $second; }')
    named.write_text('function labeled(): int { return pick(second: 13, data: 5); }')
    generated = app/'generated'
    facts = Path(adapter).parent/'native-layout/facts.json'

    def snapshot(directory):
        return {p.name: p.read_bytes() for p in directory.glob('*.cpp')}

    def compare(expected, labeled):
        ru, pu, links = emitted_units(app, facts, work/'production', generated)
        results = {'caller': 11, 'labeled': labeled}
        native(work/'retained-objects', ru, links, expected, results, False, cxx, linker)
        native(work/'production-objects', pu, links, None, results, True, cxx, linker)

    with service(exe, app) as request:
        assert request('lower').startswith('ok|')
        compare(None, 13)
        saved = snapshot(generated)
        pick.write_text(pick.read_text().replace('$second', '$result'))
        status, worklist, emitted, written = request('lower').split('|')
        offset = named.read_text().index('second:')
        assert f'labeled.phs:{offset}: Unknown named argument: second' in status, status
        assert set(filter(None, worklist.split(','))) == {'labeled.phs', 'pick.phs'}, worklist
        assert emitted == written == '' and snapshot(generated) == saved
        rejected = subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, facts, work/'production'],
                                  text=True, capture_output=True)
        assert rejected.returncode and 'Unknown named argument: second' in rejected.stderr, rejected.stderr
        repaired = named.read_text().replace('second:', 'result:')
        named.write_text(repaired.replace('function labeled', 'function while'))
        assert request('lower') == 'labeled.phs:9: Expected function name|||'
        assert snapshot(generated) == saved
        named.write_text(repaired)
        status, worklist, emitted, written = request('lower').split('|')
        assert status == 'ok' and 'caller.phs' not in (worklist + emitted), (status, worklist, emitted)
        assert written == '' and snapshot(generated) == saved
        compare(set(), 13)
        assert request('lower') == 'ok|||'
        cold = work/'fresh-named'
        shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
        with service(exe, cold) as fresh_request:
            assert fresh_request('lower').startswith('ok|') and snapshot(cold/'generated') == saved
        # Both types stay int. The named call must follow the labels to their new slots.
        pick.write_text('function pick(int $result, int $data): int { return $data; }')
        status, worklist, emitted, written = request('lower').split('|')
        assert status == 'ok' and 'caller.phs' not in (worklist + emitted), (status, worklist, emitted)
        compare({'labeled'}, 5)
        for operands, message in [('7, result: 8', 'Parameter supplied twice: result'),
                                  ('result: 7, 8', 'Positional argument after named argument')]:
            named.write_text(f'function labeled(): int {{ return pick({operands}); }}')
            failure = request('lower')
            assert message in failure and failure.endswith('||'), failure


def verify_defaults(work, exe, cxx, linker, adapter, service):
    app = work/'defaults'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    (app/'caller.phs').write_text('function caller(): int { return pick(7); }')
    (app/'supplied.phs').write_text('function supplied(): int { return pick(7, second: 11); }')
    pick = app/'pick.phs'
    signature = 'function pick(int $first, int $second%s): int { return $second; }'
    retained = app/'generated'
    facts = Path(adapter).parent/'native-layout/facts.json'

    def snapshot(directory):
        return {p.name: p.read_bytes() for p in directory.glob('*.cpp')}

    with service(exe, app) as request:
        for stage, value in [('cold', 11), ('edit', 13), ('repair', 11)]:
            if stage == 'repair':
                prior = snapshot(retained)
                pick.write_text(signature % '')
                failed = request('lower')
                assert 'Wrong argument count for pick' in failed and failed.endswith('||'), failed
                assert 'supplied.phs' not in failed and snapshot(retained) == prior
            pick.write_text(signature % f' = {value}')
            status, prepared, emitted, written = request('lower').split('|')
            assert status == 'ok', status
            ru, pu, links = emitted_units(app, facts, work/'production', retained)
            if stage != 'cold':
                for paths in (prepared, emitted):
                    assert set(filter(None, paths.split(','))) == {'pick.phs', 'caller.phs'}, paths
                assert written == 'definition.cpp,', written
            results = {'caller': value, 'supplied': 11}
            expected = None if stage == 'cold' else {'caller'}
            native(work/'retained-objs', ru, links, expected, results, False, cxx, linker)
            native(work/'production-objs', pu, links, expected, results, True, cxx, linker)
            if stage == 'cold':
                original = snapshot(retained)
            assert request('lower') == 'ok|||'
        assert snapshot(retained) == original
    cold = work/'fresh-defaults'
    shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|') and snapshot(cold/'generated') == original
        fresh_units = {name: cold/'generated'/path.name for name, path in ru.items()}
        native(work/'fresh-objs', fresh_units, links, None, results, False, cxx, linker)
        # A named argument may skip an earlier optional float. Check the literal
        # even when every caller supplies that slot explicitly.
        pick = cold/'pick.phs'
        pick.write_text('function pick(float $first = -3.75, int $second = 0): int { return $first; }')
        (cold/'caller.phs').write_text('function caller(): int { return pick(second: 11); }')
        assert request('lower').startswith('ok|')
        ru, pu, links = emitted_units(cold, facts, work/'float-production', cold/'generated')
        for units, php in [(ru, False), (pu, True)]:
            native(work/('float-php' if php else 'float-retained'), units, links, None,
                   {'caller': -3, 'supplied': 7}, php, cxx, linker)
        pick.write_text(pick.read_text().replace('int $second = 0', 'int $second = 1e30'))
        failure = request('lower')
        assert 'Floating conversion is outside int64 range' in failure and failure.endswith('||'), failure


def verify_rebinding_stage(work, app, exe, request, service, cxx, linker, adapter,
                           stage, sources, objects, results, production_objects=None):
    generated = app/'generated'
    facts = Path(adapter).parent/'native-layout/facts.json'
    status, prepared, emitted, written = request('lower').split('|')
    assert status == 'ok', (stage, status)
    ru, pu, links = emitted_units(app, facts, work/'production', generated)
    if sources is not None:
        for paths in (prepared, emitted):
            assert set(filter(None, paths.split(','))) == {n+'.phs' for n in sources}, (stage, paths)
        assert set(filter(None, written.split(','))) == {ru[n].name for n in objects}, (stage, written)
    native(work/'retained-objs', ru, links, objects, results, False, cxx, linker)
    native(work/'production-objs', pu, links, production_objects, results, True, cxx, linker)

    cold = work/('fresh-'+stage)
    shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
    with service(exe, cold) as fresh_request:
        assert fresh_request('lower').startswith('ok|')
    fresh_dir = cold/'generated'
    assert {p.name: p.read_bytes() for p in fresh_dir.glob('*.cpp')} == {
        p.name: p.read_bytes() for p in generated.glob('*.cpp')}, stage
    fresh_units = {name: fresh_dir/path.name for name, path in ru.items()}
    native(cold/'objects', fresh_units, links, None, results, False, cxx, linker)


def verify_default_rebinding(work, exe, cxx, linker, adapter, service):
    app = work/'rebinding'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    (app/'caller.phs').write_text('function caller(): int { return pick(first: 7); }')
    (app/'positional.phs').write_text('function positional(): int { return pick(7, 8); }')

    # Each edit is applied to the same session. Source work includes emission
    # attempts, while the expected objects identify units whose bytes changed.
    stages = [
        ('initial', 'first = 11', 'second = 22', 22, 8, None, None),
        ('swap', 'second = 11', 'first = 22', 11, 7, {'pick', 'caller'}, {'pick', 'caller'}),
        ('used', 'second = 13', 'first = 22', 13, 7, {'pick', 'caller'}, {'caller'}),
        ('unused', 'second = 13', 'first = 24', 13, 7, {'pick'}, set()),
    ]
    with service(exe, app) as request:
        for stage, left, right, named, positional, sources, objects in stages:
            (app/'pick.phs').write_text(f'function pick(int ${left}, int ${right}): int {{ return $second; }}')
            verify_rebinding_stage(work, app, exe, request, service, cxx, linker, adapter, stage,
                                   sources, objects, {'caller': named, 'positional': positional})


def verify_call_retargeting(work, exe, cxx, linker, adapter, service):
    app = work/'retarget'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    (app/'control.phs').write_text('function control(): int { return left(); }')

    def target(name, default):
        return f'function {name}(int $value = {default}): int {{ return $value; }}'

    (app/'left.phs').write_text(target('left', 11))
    (app/'right.phs').write_text(target('right', 11))
    stages = [
        ('initial', 'caller', 'function caller(): int { return left(); }', 11, 11, None, None),
        ('switch', 'caller', 'function caller(): int { return right(); }', 11, 11, {'caller'}, {'caller'}),
        ('left-default', 'left', target('left', 13), 11, 13, {'left', 'control'}, {'control'}),
        ('right-default', 'right', target('right', 17), 17, 13, {'right', 'caller'}, {'caller'}),
    ]
    with service(exe, app) as request:
        for stage, source, contents, caller, control, sources, objects in stages:
            (app/(source+'.phs')).write_text(contents)
            verify_rebinding_stage(work, app, exe, request, service, cxx, linker, adapter, stage,
                                   sources, objects, {'caller': caller, 'control': control},
                                   production_objects={'caller', 'right'} if stage == 'switch' else objects)


def verify_missing_call_repair(work, exe, cxx, linker, adapter, service):
    app = work/'missing-call'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    caller = app/'caller.phs'
    caller.write_text('function caller(): int { return left(); }')
    (app/'control.phs').write_text('function control(): int { return left(); }')
    left = app/'left.phs'
    left.write_text('function left(int $value = 11): int { return $value; }')
    generated = app/'generated'
    facts = Path(adapter).parent/'native-layout/facts.json'
    objects = work/'retained-objs'

    def published():
        return {p.name: (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns)
                for p in generated.iterdir()}

    with service(exe, app) as request:
        assert request('lower').startswith('ok|')
        ru, _, links = emitted_units(app, facts, work/'production', generated)
        initial = {'caller': 11, 'control': 11}
        native(objects, ru, links, None, initial, False, cxx, linker)
        saved = published()
        caller.write_text('function caller(): int { return right(); }')
        error = f'caller.phs:{caller.read_text().index("right()")}: Unknown function: right'

        for stage, expected in [('retarget', {'caller.phs'}), ('left-edit', {'left.phs', 'caller.phs'})]:
            if stage == 'left-edit':
                left.write_text(left.read_text().replace('11', '13'))
            status, prepared, emitted, written = request('lower').split('|')
            assert status == error and emitted == written == '', (stage, status, emitted, written)
            assert set(filter(None, prepared.split(','))) == expected, (stage, prepared)
            assert published() == saved
            native(objects, ru, links, set(), initial, False, cxx, linker)

        (app/'right.phs').write_text('function right(int $value = 17): int { return $value; }')
        status, prepared, emitted, written = request('lower').split('|')
        assert status == 'ok', status
        assert set(filter(None, prepared.split(','))) == {'caller.phs', 'right.phs', 'control.phs'}, prepared
        assert set(filter(None, emitted.split(','))) == {'caller.phs', 'control.phs', 'left.phs', 'right.phs'}, emitted
        ru, pu, links = emitted_units(app, facts, work/'production', generated)
        rebuilt = {'caller', 'right', 'control'}
        assert set(filter(None, written.split(','))) == {ru[name].name for name in rebuilt}, written
        repaired = {'caller': 17, 'control': 13}
        native(objects, ru, links, rebuilt, repaired, False, cxx, linker)
        native(work/'production-objs', pu, links, None, repaired, True, cxx, linker)
        saved = published()
        assert request('lower') == 'ok|||' and published() == saved
        native(objects, ru, links, set(), repaired, False, cxx, linker)

    cold = work/'fresh-repair'
    shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
    with service(exe, cold) as fresh:
        assert fresh('lower').startswith('ok|')
    fresh_dir = cold/'generated'
    assert {p.name: p.read_bytes() for p in fresh_dir.glob('*.cpp')} == {
        p.name: p.read_bytes() for p in generated.glob('*.cpp')}
    native(cold/'objects', {k: fresh_dir/p.name for k, p in ru.items()},
           links, None, repaired, False, cxx, linker)
