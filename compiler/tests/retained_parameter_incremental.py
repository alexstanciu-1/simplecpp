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
        graph.write_text('\n'.join(rules)+'\n')
    log = directory/'.ninja_log'
    offset = log.stat().st_size if log.exists() else 0
    subprocess.run(['ninja', '-C', directory], check=True, stdout=subprocess.PIPE)
    edges = [line.split('\t')[3] for line in log.read_bytes()[offset:].decode().splitlines()
             if not line.startswith('#')]
    if expected is not None:
        assert set(edges) == {name+'.o' for name in expected} | ({'observer'} if expected else set()), edges
    assert subprocess.check_output([directory/'observer'], text=True) == ' '.join(map(str, results.values()))


def emitted_units(app, facts, production, retained):
    subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, facts, production],
                   check=True, stdout=subprocess.PIPE)
    manifest = json.loads((production/'manifest.json').read_text())
    by_unit = {v: Path(k).stem for k, v in manifest['sources'].items()}
    php_units = {by_unit.get(path, Path(path).stem): production/path for path in manifest['translation_units']}
    retained_units = {p.name.removeprefix('unit-').removesuffix('.cpp').removesuffix('.php'): p
                      for p in retained.glob('*.cpp')}
    retained_units['caller'] = retained_units.pop('definition')
    return retained_units, php_units, manifest['link_inputs']


def verify(work, exe, cxx, linker, adapter, service, forwarding=False):
    app = work/'parameters'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text('return caller();')
    target = 'relay' if forwarding else 'identity'
    if forwarding:
        (app/'relay.php').write_text('function relay(int $value): int { return identity($value); }')
    dependents = {'identity', 'relay'} if forwarding else {'identity', 'caller', 'large'}
    for name, operand in [('caller', f'{target}(7)'), ('large', f'{target}(9007199254740993)'), ('quiet', '42')]:
        (app/f'{name}.php').write_text(f'function {name}(): int {{ return {operand}; }}')
    definition = app/'identity.php'
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
                (app/'relay.php').write_text(f'function relay({relay_type} $value): int {{ return identity($value); }}')
            elif stage != 'unchanged':
                definition.write_text(f'function identity({parameter} {body}): int {{ return {body}; }}')
            status, prepared, emitted, written = request('lower').split('|')
            assert status == 'ok', status
            if expected is not None:
                assert set(filter(None, prepared.split(','))) == {n+'.php' for n in expected}, prepared
                assert set(filter(None, emitted.split(','))) == {n+'.php' for n in expected}, emitted
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
            relay = app/'relay.php'
            original = relay.read_text()
            relay.write_text(original.replace('identity($value)', 'identity($missing)'))
            failed = request('lower')
            assert f'relay.php:{relay.read_text().index("$missing")}: Unknown parameter' in failed and failed.endswith('||'), failed
            relay.write_text(original)
            assert request('lower').startswith('ok|')
            assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved
    cold = work/'fresh-parameters'
    cold.mkdir()
    for path in app.glob('*.php'):
        shutil.copyfile(path, cold/path.name)
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|')
        assert {p.name: p.read_bytes() for p in (cold/'generated').glob('*.cpp')} == saved
        (cold/'main.php').write_text('return identity(7);')
        assert request('lower').startswith('ok|')
        (cold/'identity.php').write_text('function identity(float $value): int { return $value; }')
        status, prepared, emitted, written = request('lower').split('|')
        assert status == 'ok' and 'main.php,' in prepared and 'main.php,' in emitted and 'main.cpp,' in written
        main = (cold/'generated/main.cpp').read_text()
        assert 'static_cast<scpp::float64>(7LL)' in main
        assert request('lower') == 'ok|||'
    # Previously published bytes on the original project remain intact.
    assert {p.name: p.read_bytes() for p in retained.glob('*.cpp')} == saved


def verify_multiple(work, exe, cxx, linker, adapter, service):
    app = work/'multiple'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text('return caller();')
    (app/'caller.php').write_text('function caller(): int { return relay(9007199254740993); }')
    (app/'relay.php').write_text('function relay(int $value): int { return pick(7, $value); }')
    # A separate three-slot call checks that forwarded arguments use the caller's slot.
    (app/'third.php').write_text('function third(int $a, float $b, int $c): int { return $c; }')
    (app/'swap.php').write_text('function swap(int $a, int $b): int { return third($a, 0, $b); }')
    (app/'quiet.php').write_text('function quiet(): int { return swap(5, -17); }')
    pick = app/'pick.php'
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
                    assert set(filter(None, paths.split(','))) == {n+'.php' for n in changes}, paths
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
    for source in app.glob('*.php'):
        shutil.copyfile(source, cold/source.name)
    with service(exe, cold) as request:
        assert request('lower').startswith('ok|')
    assert {p.name: p.read_bytes() for p in (cold/'generated').glob('*.cpp')} == saved


def verify_named(work, exe, cxx, linker, adapter, service):
    app = work/'named'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text('return caller();')
    (app/'caller.php').write_text('function caller(): int { return pick(7, 11); }')
    pick, named = app/'pick.php', app/'labeled.php'
    pick.write_text('function pick(int $first, int $second): int { return $second; }')
    named.write_text('function labeled(): int { return pick(second: 13, first: 5); }')
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
        assert f'labeled.php:{offset}: Unknown named argument: second' in status, status
        assert set(filter(None, worklist.split(','))) == {'labeled.php', 'pick.php'}, worklist
        assert emitted == written == '' and snapshot(generated) == saved
        rejected = subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, facts, work/'production'],
                                  text=True, capture_output=True)
        assert rejected.returncode and 'Unknown named argument: second' in rejected.stderr, rejected.stderr
        named.write_text(named.read_text().replace('second:', 'result:'))
        status, worklist, emitted, written = request('lower').split('|')
        assert status == 'ok' and 'caller.php' not in (worklist + emitted), (status, worklist, emitted)
        assert written == '' and snapshot(generated) == saved
        compare(set(), 13)
        assert request('lower') == 'ok|||'
        cold = work/'fresh-named'
        shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
        with service(exe, cold) as fresh_request:
            assert fresh_request('lower').startswith('ok|') and snapshot(cold/'generated') == saved
        # Both types stay int. The named call must follow the labels to their new slots.
        pick.write_text('function pick(int $result, int $first): int { return $first; }')
        status, worklist, emitted, written = request('lower').split('|')
        assert status == 'ok' and 'caller.php' not in (worklist + emitted), (status, worklist, emitted)
        compare({'labeled'}, 5)
        for operands, message in [('7, result: 8', 'Parameter supplied twice: result'),
                                  ('result: 7, 8', 'Positional argument after named argument')]:
            named.write_text(f'function labeled(): int {{ return pick({operands}); }}')
            failure = request('lower')
            assert message in failure and failure.endswith('||'), failure
