"""Interactions between numeric call watches, source ownership and publication."""
import fcntl
from pathlib import Path
import shutil
import subprocess

from retained_parameter_incremental import emitted_units, native


def cpp_files(directory):
    return {p.name: p.read_bytes() for p in directory.glob('*.cpp')}


def write_function(app, name, expression):
    (app/(name+'.phs')).write_text(f'function {name}(): int {{ return {expression}; }}')


def default(app, value, kind='int', filename='pick.phs'):
    (app/filename).write_text(f'function pick({kind} $v = {value}): int {{ return $v; }}')


def verify(work, exe, cxx, linker, adapter, service, sequence):
    app = work/'project'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('return caller();')
    generated = app/'generated'
    facts = Path(adapter).parent/'native-layout/facts.json'
    write_function(app, 'caller', 'pick()')
    write_function(app, 'control', 'pick(7)')
    default(app, '11')

    with service(exe, app) as request:
        def step(results, prepared=None, objects=None, *, emitted=None, removed=(), fresh=False):
            status, worklist, units, writes = request('lower').split('|')
            assert status == 'ok', status
            ru, pu, links = emitted_units(app, facts, work/'production', generated)
            if prepared is not None:
                for actual, expected in [(worklist, prepared), (units, prepared if emitted is None else emitted)]:
                    assert set(filter(None, actual.split(','))) == {n+'.phs' for n in expected}, (actual, expected)
                expected_files = {ru[n].name for n in objects}.union(removed)
                assert set(filter(None, writes.split(','))) == expected_files, (writes, expected_files)
                assert all(not (generated/name).exists() for name in removed)
            native(work/'retained-objs', ru, links, objects, results, False, cxx, linker)
            native(work/'production-objs', pu, links, None, results, True, cxx, linker)
            if fresh:
                cold = work/'fresh'
                shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
                with service(exe, cold) as new:
                    assert new('lower').startswith('ok|')
                bundle = cold/'generated'
                assert cpp_files(bundle) == cpp_files(generated)
                native(cold/'objects', {n: bundle/p.name for n, p in ru.items()}, links,
                       None, results, False, cxx, linker)
            return ru

        if sequence == 'shared':
            write_function(app, 'peer', 'pick()')
            write_function(app, 'outer', 'caller()')

            def results(caller, peer):
                return {'caller': caller, 'peer': peer, 'outer': caller, 'control': 7}

            step(results(11, 11))
            default(app, '13')
            step(results(13, 13), {'caller', 'peer', 'pick'}, {'caller', 'peer'})
            default(app, '13.75')
            step(results(13, 13), {'pick'}, set())
            default(app, '17')
            write_function(app, 'peer', 'pick(19)')
            step(results(17, 19), {'caller', 'peer', 'pick'}, {'caller', 'peer'})
            default(app, '23')
            step(results(23, 19), {'caller', 'pick'}, {'caller'}, fresh=True)
        elif sequence == 'zero':
            results = {'caller': 0, 'control': 7}
            default(app, '0.0', 'float')
            units = step(results)
            positive = units['caller'].read_bytes()
            default(app, '-0.0', 'float')
            step(results, {'pick', 'caller'}, {'caller'})
            negative = units['caller'].read_bytes()
            assert b'-0x0' in negative and b'-0x0' not in positive, (positive, negative)
            default(app, '-0.00', 'float')
            step(results, {'pick'}, set(), fresh=True)
        elif sequence == 'ownership':
            results = {'caller': 11, 'control': 7}
            step(results)
            before = cpp_files(generated)
            default(app, '11', filename='replacement.phs')
            failure, prepared, emitted, writes = request('lower').split('|')
            assert 'Duplicate function: pick' in failure and prepared == emitted == writes == ''
            assert cpp_files(generated) == before
            (app/'pick.phs').unlink()
            step(results, {'replacement'}, {'replacement'}, removed=['unit-pick.phs.cpp'])
            default(app, '17', filename='replacement.phs')
            step({'caller': 17, 'control': 7}, {'replacement', 'caller'}, {'caller'}, fresh=True)
        elif sequence == 'publication':
            step({'caller': 11, 'control': 7})
            before = cpp_files(generated)
            with (generated/'.scpp-bundle.lock').open('rb') as lock:
                fcntl.flock(lock, fcntl.LOCK_SH)
                default(app, '13')
                status, prepared, _, writes = request('lower').split('|')
                assert status == 'Output bundle busy' and writes == ''
                assert set(filter(None, prepared.split(','))) == {'caller.phs', 'pick.phs'}
                assert cpp_files(generated) == before
                default(app, '17')
            step({'caller': 17, 'control': 7}, {'caller', 'pick'}, {'caller'}, fresh=True)
        elif sequence == 'entry':
            verify_entry(work, app, request, exe, service, cxx, linker, facts)
        else:
            raise ValueError(sequence)
        assert request('lower') == 'ok|||'


def verify_entry(work, app, request, exe, service, cxx, linker, facts):
    generated = app/'generated'
    (app/'caller.phs').unlink()
    main = app/'main.phs'
    main.write_text('return pick();')
    other = app/'other.phs'
    other.write_text('function other(int $v = 13): int { return $v; }')
    for stage, result, changed in [('cold', 11, None), ('switch', 13, {'definition', 'pick', 'main'}),
                                   ('old-default', 13, set()), ('new-default', 17, {'main'})]:
        if stage == 'switch':
            main.write_text('return other();')
        elif stage == 'old-default':
            default(app, '19')
        elif stage == 'new-default':
            other.write_text(other.read_text().replace('13', '17'))
        status, prepared, emitted, written = request('lower').split('|')
        assert status == 'ok', status
        ru, pu, links = emitted_units(app, facts, work/'production', generated, 'definition')
        if stage != 'cold':
            expected = {'switch': {'main'}, 'old-default': {'pick'}, 'new-default': {'main', 'other'}}[stage]
            assert set(filter(None, prepared.split(','))) == {n+'.phs' for n in expected}, prepared
            if stage == 'switch':
                expected = {'other', 'main', 'pick'}
            assert set(filter(None, emitted.split(','))) == {n+'.phs' for n in expected}, emitted
            files = {ru[n].name for n in changed}
            if stage == 'switch':
                files.add('unit-other.phs.cpp')
            assert set(filter(None, written.split(','))) == files, written
        builds = [('retained', ru, False, changed), ('production', pu, True, None)]
        if stage == 'new-default':
            cold = work/'fresh'
            shutil.copytree(app, cold, ignore=shutil.ignore_patterns('generated'))
            with service(exe, cold) as new:
                assert new('lower').startswith('ok|')
            bundle = cold/'generated'
            assert cpp_files(bundle) == cpp_files(generated)
            builds.append(('fresh', {n: bundle/p.name for n, p in ru.items()}, False, None))
        for name, units, php, edges in builds:
            directory = work/(name+'-objs')
            native(directory, units, links, edges, {'control': 7}, php, cxx, linker)
            binary = directory/'program'
            subprocess.run([cxx, '-stdlib=libc++', f'--ld-path={linker}',
                            *(directory/(n+'.o') for n in units), *links, '-o', binary], check=True)
            assert subprocess.run([binary], timeout=10).returncode == result, (stage, name)
