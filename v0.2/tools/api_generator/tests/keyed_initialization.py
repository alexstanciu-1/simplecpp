"""Fixed-session initialization bindings: fallback, relocation, retirement and reduced publication."""
import argparse
import copy
import json
import os
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'compiler/tests'))
from list_initialization_native import run
from collection_arguments import EMIT


def proof(work, quick=False):
    work.mkdir(parents=True, exist_ok=True)
    metadata = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    publication['hashes'].pop('header', None)
    facts = ROOT / 'build/clang/native-layout/facts.json'
    context = facts.with_name('context.json')
    generator = ROOT / 'tools/api_generator/generate.php'
    mp, pp = work / 'metadata.json', work / 'publication.json'
    output, compiler = work / 'runtime/generated', work / 'compiler'
    boot = work / 'boot.php'
    bootstrap = (ROOT / 'compiler/boot.php').read_text().replace("__DIR__ . '/generated/", repr(str(compiler) + '/') + " . '")
    boot.write_text(bootstrap.replace('__DIR__', repr(str(ROOT / 'compiler'))))
    observations = {}
    for variant in ('owned', 'readonly', 'relocated', 'collision', 'reduced', 'disabled', 'restored'):
        m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
        init = m['hash']['initialization']
        if variant == 'readonly': init.pop('consume')
        if variant == 'collision': m['hash']['key_contracts']['int64']['hash'] = 'static_cast<std::uint64_t>(@key@) * 0'
        if variant == 'relocated':
            m['submodules']['core.hashes']['header'] = 'scpp/collections/input.hpp'
            init.update(read_only='copy_inputs', consume='consume_inputs', input='input_storage', stack_bytes=1, inline_entries=0, entry='input_entry')
        if variant == 'reduced':
            p['hashes']['specializations'] = [dict(key='int64', value='int64')]
        if variant == 'disabled': m['hash'].pop('initialization')
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(p))
        for product, target in [('runtime', output), ('compiler', compiler)]:
            run(['php', generator, 'emit-' + product, mp, pp, facts, context, target])
        header = m['submodules']['core.hashes']['header']
        inventory = json.loads((output / 'inventory.json').read_text())['files']
        assert all((output / path).is_file() for path in inventory)
        if variant != 'relocated':
            assert not (output / 'public/scpp/collections/input.hpp').exists()
            assert 'public/scpp/collections/input.hpp' not in inventory
        else:
            assert not (output / 'public/scpp/hashes.hpp').exists()
            assert 'public/scpp/hashes.hpp' not in inventory
        if variant in ('readonly', 'disabled'):
            assert 'from_owned_entries' not in (output / 'public' / header).read_text()
        if variant == 'disabled':
            assert 'keyed_input' not in (output / 'public' / header).read_text()
        obj = work / 'hash.o'
        runtime_obj = work / 'runtime.o'
        inc = ['-I', output / 'public', '-I', output / 'private', '-I', ROOT / 'runtime/src/sequence', '-I', ROOT / 'runtime/src/numeric', '-I', ROOT / 'runtime/src/hash']
        if quick:
            # Compile one real consumer against this variant's freshly published header.
            source = '$v hash<int,int> = [1 => 7, 2 => 9]; return $v[2];'
            emit = ['php', '-r', EMIT, str(boot), 'php', source]
            if variant == 'disabled':
                rejected = subprocess.run(emit, capture_output=True, text=True)
                assert rejected.returncode and 'published keyed destination' in rejected.stderr, rejected
            else:
                cpp = run(emit)
                assert '::' + init.get('consume', init['read_only']) + '(' in cpp
                run(['clang++', '-std=c++23', *inc, '-x', 'c++', '-fsyntax-only', '-'], input=cpp)
            continue
        for cxx in ('clang++', 'g++'):
            run([cxx, '-std=c++23', '-O2', *inc, '-c', output / 'src/hash.cpp', '-o', obj])
            run([cxx, '-std=c++23', '-O2', *inc, '-c', output / 'src/runtime.cpp', '-o', runtime_obj])
            for flavor in ('cpp', 'php', 'js'):
                source = {'cpp':'hash<int,int> v = [1 => 7, 2 => 9]; return v[2];',
                          'php':'$v hash<int,int> = [1 => 7, 2 => 9]; return $v[2];',
                          'js':'let v: hash<int,int> = [1 => 7, 2 => 9]; return v[2];'}[flavor]
                if variant == 'disabled':
                    result = subprocess.run(['php', '-r', EMIT, str(boot), flavor, source], capture_output=True, text=True,
                                            env={**os.environ, 'XDEBUG_MODE':'off'})
                    assert result.returncode != 0 and 'published keyed destination' in result.stderr
                    continue
                cpp = run(['php', '-r', EMIT, boot, flavor, source])
                factory = init.get('consume', init['read_only'])
                assert '::' + factory + '(' + ('input.data()' if variant == 'relocated' else 'input') + ', 2)' in cpp
                assert '#include "' + header + '"' in cpp or '#include <' + header + '>' in cpp
                unit, exe = work / 'consumer.cpp', work / 'consumer'
                unit.write_text(cpp)
                run([cxx, '-std=c++23', '-O2', '-Wall', '-Wextra', '-Werror', *inc, unit, obj, runtime_obj, ROOT / 'runtime/src/entry/entry.cpp', '-o', exe])
                result = subprocess.run([exe])
                assert result.returncode == 9, (variant, cxx, flavor)
                observations[f'{variant}-{cxx}-{flavor}'] = 'pass'
            # A required constructor makes an accidental default construction observable at compile time.
            if cxx == 'clang++':
                app_source = ('struct Point { int x; Point(int n) { this->x=n; } }; Point p(7); '
                              'hash<Point,int> table = [1 => p, 2 => p]; p->x=3; auto found=table[2]; return found->x;')
                if variant == 'disabled':
                    result = subprocess.run(['php', '-r', EMIT, str(boot), 'cpp', app_source], capture_output=True, text=True)
                    assert result.returncode and 'published keyed destination' in result.stderr
                else:
                    cpp = run(['php', '-r', EMIT, boot, 'cpp', app_source])
                    assert '::' + init.get('consume', init['read_only']) + '(input.data(), 2)' in cpp
                    unit.write_text(cpp)
                    run([cxx, '-std=c++23', '-Wall', '-Wextra', '-Werror', *inc, unit, obj, runtime_obj,
                         ROOT / 'runtime/src/ownership/owner_access.cpp', ROOT / 'runtime/src/entry/entry.cpp', '-o', exe])
                    assert subprocess.run([exe]).returncode == 7
    # Rejected metadata must leave the last complete export untouched.
    snapshot = {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    invalid_fields = [('consume','bad::name'), ('input','class'), ('entry','iteration_limit'), ('entry','iteration_output'), ('read_only','get_or_null'), ('read_only','set'), ('read_only','storage_'), ('input','data'), ('entry','data_'), ('consume','int64'), ('presizing','none'), ('transfer','throwing'), ('stack_bytes',0), ('duplicates','replace')]
    if quick:
        # Cover a malformed identifier, a generated-member collision and an invalid key policy.
        invalid_fields = [('consume', 'bad::name'), ('entry', 'iteration_limit'), ('duplicates', 'replace')]
    for field, value in invalid_fields:
        m = copy.deepcopy(metadata); m['hash']['initialization'][field] = value
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(publication))
        result = subprocess.run(['php', str(generator), 'emit-runtime', str(mp), str(pp), str(facts), str(context), str(output)], capture_output=True)
        assert result.returncode != 0, field
        assert snapshot == {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    # The generated native assertion enforces the byte budget without PHP layout guesses.
    m = copy.deepcopy(metadata)
    m['hash']['initialization']['inline_entries'] = 257
    mp.write_text(json.dumps(m)); pp.write_text(json.dumps(publication))
    run(['php', generator, 'emit-runtime', mp, pp, facts, context, output])
    for cxx in ('clang++', 'g++'):
        bad = subprocess.run([cxx, '-std=c++23', '-I', str(output / 'public'), '-x', 'c++', '-fsyntax-only', '-'],
                             input='#include <scpp/hashes.hpp>\n', text=True, capture_output=True)
        assert bad.returncode != 0 and 'initializer inline limit exceeds byte budget' in bad.stderr
    mp.write_text(json.dumps(metadata))
    run(['php', generator, 'emit-runtime', mp, pp, facts, context, output])
    assert snapshot == {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    (work / 'results.json').write_text(json.dumps(observations, indent=2))
    print('PASS fixed-session providers, read-only fallback, relocation, reduction, rejection and removal/restoration')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--quick', action='store_true', help='check compiler/provider compatibility for each publication')
    args = parser.parse_args()
    proof(args.out.resolve(), args.quick)
