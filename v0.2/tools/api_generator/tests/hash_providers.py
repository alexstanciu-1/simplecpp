"""Hash exports, provider relocation/retirement and two-argument compiler dispatch."""
import argparse
import copy
import json
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[3]


def run(args, expected=0):
    result = subprocess.run(list(map(str, args)), capture_output=True, text=True,
                            env={**os.environ, 'XDEBUG_MODE': 'off'}, timeout=120)
    assert result.returncode == expected, (args, result.stdout, result.stderr)
    return result.stdout


def proof(work):
    work.mkdir(parents=True, exist_ok=True)
    metadata = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    generator = ROOT / 'tools/api_generator/generate.php'
    facts = ROOT / 'build/clang/native-layout/facts.json'
    context = facts.with_name('context.json')
    mp, pp = work / 'metadata.json', work / 'publication.json'
    runtime, compiler = work / 'runtime', work / 'compiler'
    runner = work / 'resolve.php'
    runner.write_text('<?php\n' + ''.join('require ' + repr(str(ROOT / 'compiler/types' / (name + '.php'))) + ';\n'
        for name in ['conversion', 'definition', 'vector', 'carrier', 'hash', 'boxed'])
        + "foreach (['int64','null','bool','uint8','float64','string','registry'] as $name) require $argv[1].'/types/'.$name.'.php';\n"
        + "$t=\\scpp\\compiler\\type_registry::specialize('hash',\\scpp\\compiler\\type_registry::integer(),\\scpp\\compiler\\type_registry::string());\n"
        + "echo json_encode($t === null ? null : [$t->name,$t->cpp_type(),$t->cpp_headers()]);\n")

    def emit(m, p, expected=0):
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(p))
        run(['php', generator, 'emit-runtime', mp, pp, facts, context, runtime], expected)
        run(['php', generator, 'emit-compiler', mp, pp, facts, context, compiler], expected)
        if expected == 0:
            plan = json.loads((runtime / 'prepared.json').read_text())
            headers = json.loads(run(['php', generator, 'provider-headers', mp, pp]))
            assert headers == sorted(plan['submodules']['nodes'][p]['header'] for p in plan['submodules']['published'])
            return plan

    default = emit(metadata, publication)
    assert len(default['hashes']['specializations']) == 4
    for specialization in default['hashes']['specializations']:
        assert default['submodules']['type_exports'][specialization['name']]['provider'] == 'core.hashes'
    assert default['submodules']['nodes']['core.hashes']['requires'] == ['core.carriers', 'core.scalars', 'core.string']
    assert '#include <scpp/numeric.hpp>' not in (runtime / 'public/scpp/hashes.hpp').read_text()
    before = {p.name: p.read_bytes() for p in (compiler / 'types').glob('*.php')}
    for variant in ('relocated', 'shared_carriers'):
        m = copy.deepcopy(metadata)
        if variant == 'relocated': m['submodules']['core.hashes']['header'] = 'scpp/maps/typed.hpp'
        else: m['hash']['provider'] = 'core.carriers'
        plan = emit(m, publication)
        provider = plan['submodules']['type_exports']['hash_int64_string']['provider']
        header = plan['submodules']['nodes'][provider]['header']
        assert json.loads(run(['php', runner, compiler])) == ['hash<int64,string>', 'scpp::hash_int64_string', [header]]
        after = {p.name: p.read_bytes() for p in (compiler / 'types').glob('*.php')}
        assert {k:v for k,v in before.items() if k != 'hashes.php'} == {k:v for k,v in after.items() if k != 'hashes.php'}
        assert not (runtime / 'public/scpp/hashes.hpp').exists()
        consumer = work / 'consumer.cpp'
        consumer.write_text(f'#include <{header}>\nvoid f(scpp::hash_int64_string, scpp::hash_string_int64, scpp::hash_int64_int64, scpp::hash_string_string) {{}}\n')
        run(['clang++', '-std=c++23', '-stdlib=libc++', '-Werror', '-fsyntax-only', '-I'+str(runtime / 'public'), consumer])
        assert 'template<' not in (runtime / 'public' / header).read_text()
        run(['clang++', '-std=c++23', '-stdlib=libc++', '-Werror', '-fsyntax-only',
             '-I'+str(runtime / 'public'), '-I'+str(ROOT / 'runtime/src/hash'),
             '-I'+str(ROOT / 'runtime/src/numeric'), runtime / 'src/hash.cpp'])

    emit(metadata, publication)
    for invalid in ('key', 'value', 'duplicate', 'provider', 'legacy_header', 'cycle', 'nullable', 'construction'):
        m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
        if invalid in ('key', 'value'): p['hashes']['specializations'][0][invalid] = 'bool_t'
        if invalid == 'duplicate': p['hashes']['specializations'].append(p['hashes']['specializations'][0])
        if invalid == 'provider': m['hash']['provider'] = 'missing'
        if invalid == 'legacy_header': p['hashes']['header'] = 'scpp/wrong.hpp'
        if invalid == 'cycle': m['hash']['provider'] = 'core.vectors'
        if invalid == 'construction': m['hash']['construction'] = 'nonempty'
        if invalid == 'nullable': p.pop('carriers')
        stable = {str(f.relative_to(runtime)): f.read_bytes() for f in runtime.rglob('*') if f.is_file()}
        compiler_stable = (compiler / 'types/hashes.php').read_bytes()
        emit(m, p, 1)
        assert (compiler / 'types/hashes.php').read_bytes() == compiler_stable
        assert {str(f.relative_to(runtime)): f.read_bytes() for f in runtime.rglob('*') if f.is_file()} == stable

    absent = copy.deepcopy(publication); absent.pop('hashes')
    retired = emit(metadata, absent)
    assert 'hash_int64_string' not in retired['submodules']['type_exports']
    assert not (runtime / 'public/scpp/hashes.hpp').exists()
    assert not (runtime / 'src/hash.cpp').exists()
    assert json.loads(run(['php', runner, compiler])) is None
    subset = copy.deepcopy(publication)
    subset['hashes']['specializations'] = [{'value':'int64','key':'int64'}]
    emit(metadata, subset)
    assert json.loads(run(['php', runner, compiler])) is None
    assert 'class type_hash_string_' not in (compiler / 'types/hashes.php').read_text()
    emit(metadata, publication)
    assert json.loads(run(['php', runner, compiler]))[1] == 'scpp::hash_int64_string'
    print('Hash provider placement, strict boundaries, two-argument registration and publication recovery passed')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        proof(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-hash-providers-') as directory:
            proof(Path(directory))
