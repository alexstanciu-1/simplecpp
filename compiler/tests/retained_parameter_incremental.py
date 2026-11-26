"""Observe retained body work, real object rebuilds, and both callers' native results."""
import json
from pathlib import Path
import shutil
import subprocess

from translated_vector_recipe import ROOT


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

    def native(directory, units, links, expected, large, php):
        directory.mkdir(exist_ok=True)
        graph = directory/'build.ninja'
        if not graph.exists():
            # Compile every emitted unit with normal header dependencies. Link a separate
            # observer so both direct callers execute without narrowing int64 to an exit code.
            names = ('caller', 'large')
            symbols = [name+'_' if php else 'source_'+name.encode().hex() for name in names]
            declarations = ''.join(f'scpp::int64 {name}();' for name in symbols)
            if php:
                declarations = 'namespace App {' + declarations + '}'
                symbols = ['App::'+name for name in symbols]
            (directory/'probe.cpp').write_text('#include <scpp/core/scalars.hpp>\n#include <cstdio>\n' +
                declarations + '\nint main() { std::printf("%lld %lld", static_cast<long long>(' +
                symbols[0] + '()), static_cast<long long>(' + symbols[1] + '())); }')
            rules = [f'rule cc\n  command = {cxx} -std=c++23 -stdlib=libc++ -I{ROOT}/runtime/generated/public -MMD -MF $out.d -c $in -o $out\n  depfile = $out.d\n  deps = gcc',
                     f'rule link\n  command = {cxx} -stdlib=libc++ --ld-path={linker} $in -o $out']
            rules += [f'build {name}.o: cc {src}' for name, src in units.items()]
            subjects = 'caller.o large.o identity.o' + (' relay.o' if forwarding else '')
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
        assert subprocess.check_output([directory/'observer'], text=True) == f'7 {large}'

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
            subprocess.run(['php', ROOT/'compiler/main.php', 'project', app, facts, production],
                           check=True, stdout=subprocess.PIPE)
            manifest = json.loads((production/'manifest.json').read_text())
            by_unit = {v: Path(k).stem for k, v in manifest['sources'].items()}
            php_units = {by_unit.get(path, Path(path).stem): production/path for path in manifest['translation_units']}
            retained_units = {p.name.removeprefix('unit-').removesuffix('.cpp').removesuffix('.php'): p
                              for p in retained.glob('*.cpp')}
            retained_units['caller'] = retained_units.pop('definition')
            # A rename changes the PHP parameter spelling in C++. The retained unit
            # uses a positional name, so its bytes and object stay identical.
            retained_expected = set() if stage == 'rename' else expected
            if retained_expected is not None:
                assert set(filter(None, written.split(','))) == {retained_units[n].name for n in retained_expected}
            native(work/'retained-objs', retained_units, manifest['link_inputs'], retained_expected, large, False)
            # Production publishes parameter names in the shared header, so its
            # dependent objects also rebuild on this name-only edit.
            php_expected = dependents if stage == 'rename' else expected
            native(work/'php-objs', php_units, manifest['link_inputs'], php_expected, large, True)

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
