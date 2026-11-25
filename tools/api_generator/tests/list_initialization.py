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
from list_initialization_native import run, includes
from collection_arguments import EMIT


def proof(work, quick=False):
    work.mkdir(parents=True, exist_ok=True)
    metadata = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    publication['vectors'].pop('header', None)
    facts = ROOT / 'build/clang/native-layout/facts.json'
    context = facts.with_name('context.json')
    generator = ROOT / 'tools/api_generator/generate.php'
    mp, pp = work / 'metadata.json', work / 'publication.json'
    output, compiler = work / 'runtime/generated', work / 'compiler'
    boot = work / 'boot.php'
    bootstrap = (ROOT / 'compiler/boot.php').read_text().replace("__DIR__ . '/generated/", repr(str(compiler) + '/') + " . '")
    boot.write_text(bootstrap.replace('__DIR__', repr(str(ROOT / 'compiler'))))
    observations = {}
    for variant in ('owned', 'readonly', 'relocated', 'reduced', 'append', 'disabled', 'restored'):
        m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
        init = m['vector']['initialization']['bulk']
        if variant == 'readonly': init.pop('consume')
        if variant == 'relocated':
            m['submodules']['core.vectors']['header'] = 'scpp/collections/input.hpp'
            init.update(read_only='copy_inputs', consume='consume_inputs', input='input_storage', stack_bytes=1, inline_elements={'integer':0,'string':0})
        if variant == 'reduced':
            for key in ('hashes', 'carriers', 'string', 'boxed', 'cli_helpers', 'text_helpers', 'text_builders'): p.pop(key, None)
            p['vectors']['specializations'] = [p['vectors']['specializations'][0]]
        if variant == 'append': m['vector']['initialization'].pop('bulk')
        if variant == 'disabled': m['vector'].pop('initialization')
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(p))
        for product, target in [('runtime', output), ('compiler', compiler)]:
            run(['php', generator, 'emit-' + product, mp, pp, facts, context, target])
        header = m['submodules']['core.vectors']['header']
        inventory = json.loads((output / 'inventory.json').read_text())['files']
        assert all((output / path).is_file() for path in inventory)
        if variant != 'relocated':
            assert not (output / 'public/scpp/collections/input.hpp').exists()
            assert 'public/scpp/collections/input.hpp' not in inventory
        else:
            assert not (output / 'public/scpp/vectors.hpp').exists()
            assert 'public/scpp/vectors.hpp' not in inventory
        if variant in ('readonly', 'append', 'disabled'):
            assert 'from_owned' not in (output / 'public' / header).read_text()
        if variant == 'disabled':
            assert 'literal_input' not in (output / 'public' / header).read_text()
        obj = work / 'runtime.o'
        inc = ['-I', output / 'public', '-I', output / 'private', '-I', ROOT / 'runtime/src/sequence', '-I', ROOT / 'runtime/src/numeric']
        if quick:
            # Compile one real consumer against this variant's freshly published header.
            source = '$v vector<int> = [7, 9]; return $v[1];'
            emit = ['php', '-r', EMIT, str(boot), 'php', source]
            if variant == 'disabled':
                rejected = subprocess.run(emit, capture_output=True, text=True)
                assert rejected.returncode and 'sequence-initializable' in rejected.stderr, rejected
            else:
                cpp = run(emit)
                if variant == 'append':
                    assert '.append(' in cpp
                else:
                    assert '::' + init.get('consume', init['read_only']) + '(' in cpp
                run(['clang++', '-std=c++23', *inc, '-x', 'c++', '-fsyntax-only', '-'], input=cpp)
            continue
        for cxx in ('clang++', 'g++'):
            run([cxx, '-std=c++23', '-O2', *inc, '-c', output / 'src/runtime.cpp', '-o', obj])
            for flavor in ('cpp', 'php', 'js'):
                source = {'cpp':'vector<int> v = [7, 9]; return v[1];',
                          'php':'$v vector<int> = [7, 9]; return $v[1];',
                          'js':'let v: vector<int> = [7, 9]; return v[1];'}[flavor]
                if variant == 'disabled':
                    result = subprocess.run(['php', '-r', EMIT, str(boot), flavor, source], capture_output=True, text=True,
                                            env={**os.environ, 'XDEBUG_MODE':'off'})
                    assert result.returncode != 0 and 'sequence-initializable' in result.stderr
                    continue
                cpp = run(['php', '-r', EMIT, boot, flavor, source])
                if variant == 'append':
                    assert '.append(' in cpp and '::from_owned(' not in cpp
                else:
                    factory = init.get('consume', init['read_only'])
                    assert '::' + factory + '(' + ('input.data()' if variant == 'relocated' else 'input') + ', 2)' in cpp
                assert '#include "' + header + '"' in cpp or '#include <' + header + '>' in cpp
                unit, exe = work / 'consumer.cpp', work / 'consumer'
                unit.write_text(cpp)
                run([cxx, '-std=c++23', '-O2', '-Wall', '-Wextra', '-Werror', *inc, unit, obj, ROOT / 'runtime/src/entry/entry.cpp', '-o', exe])
                result = subprocess.run([exe])
                assert result.returncode == 9, (variant, cxx, flavor)
                observations[f'{variant}-{cxx}-{flavor}'] = 'pass'
    # Rejected metadata must leave the last complete export untouched.
    snapshot = {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    for field, value in [('consume','bad::name'), ('input','class'), ('presizing','none'), ('transfer','throwing'), ('stack_bytes',0), ('element_families',['dynamic'])]:
        m = copy.deepcopy(metadata); m['vector']['initialization']['bulk'][field] = value
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(publication))
        result = subprocess.run(['php', str(generator), 'emit-runtime', str(mp), str(pp), str(facts), str(context), str(output)], capture_output=True)
        assert result.returncode != 0, field
        assert snapshot == {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    # The generated native assertion enforces the byte budget without PHP layout guesses.
    m = copy.deepcopy(metadata)
    m['vector']['initialization']['bulk']['inline_elements']['integer'] = 513
    mp.write_text(json.dumps(m)); pp.write_text(json.dumps(publication))
    run(['php', generator, 'emit-runtime', mp, pp, facts, context, output])
    for cxx in ('clang++', 'g++'):
        bad = subprocess.run([cxx, '-std=c++23', '-I', str(output / 'public'), '-x', 'c++', '-fsyntax-only', '-'],
                             input='#include <scpp/vectors.hpp>\n', text=True, capture_output=True)
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
