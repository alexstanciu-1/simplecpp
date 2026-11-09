"""Concrete vector exports, provider relocation, publication recovery and native ABI proof."""
import argparse
import copy
import json
import os
from pathlib import Path
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[3]
ENV = {**os.environ, 'XDEBUG_MODE': 'off'}


def run(args, expected=0):
    start = time.perf_counter()
    result = subprocess.run(list(map(str, args)), capture_output=True, text=True, env=ENV, timeout=120)
    assert result.returncode == expected, (args, result.stdout, result.stderr)
    return result.stdout, (time.perf_counter() - start) * 1000


def proof(work):
    work.mkdir(parents=True, exist_ok=True)
    metadata = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    publication['vectors'].pop('header', None)
    facts = ROOT / 'build/clang/native-layout/facts.json'
    context = facts.with_name('context.json')
    generator = ROOT / 'tools/api_generator/generate.php'
    mp, pp, output = work / 'metadata.json', work / 'publication.json', work / 'runtime'

    def emit(m, p, expected=0):
        mp.write_text(json.dumps(m)); pp.write_text(json.dumps(p))
        run(['php', generator, 'emit-runtime', mp, pp, facts, context, output], expected)
        if expected == 0:
            prepared = json.loads((output / 'prepared.json').read_text())
            raw, _ = run(['php', generator, 'prepare-runtime', mp, pp, facts, context])
            assert prepared == json.loads(raw), 'Emission changed prepared provider graph'
            headers, _ = run(['php', generator, 'provider-headers', mp, pp])
            expected_headers = sorted(prepared['submodules']['nodes'][p]['header'] for p in prepared['submodules']['published'])
            adapters = {a['header'] for f in prepared.get('string_functions', {}).values() for a in f.get('adapters', {}).values()}
            if (output / 'public/scpp/value_error.hpp').exists():
                adapters.add('scpp/value_error.hpp')
            assert json.loads(headers) == sorted(set(expected_headers) | adapters), 'CMake inventory differs from published providers'
            return prepared

    def snapshot():
        return {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}

    baseline = emit(metadata, publication)
    original = snapshot()
    compiler = work / 'compiler'
    run(['php', generator, 'emit-compiler', mp, pp, facts, context, compiler])
    compiler_before = {p.name: p.read_bytes() for p in compiler.rglob('*.php')}
    runner = work / 'resolve.php'
    runner.write_text("<?php\nrequire " + repr(str(ROOT / 'compiler/types/conversion.php')) + ";\n"
        + "require " + repr(str(ROOT / 'compiler/types/definition.php')) + ";\n"
        + "require " + repr(str(ROOT / 'compiler/types/vector.php')) + ";\n"
        + "require " + repr(str(ROOT / 'compiler/types/carrier.php')) + ";\n"
        + "require " + repr(str(ROOT / 'compiler/types/hash.php')) + ";\n"
        + "require " + repr(str(ROOT / 'compiler/types/boxed.php')) + ";\n"
        + "foreach (['int64','null','bool','uint8','float64','string','registry'] as $type) require $argv[1] . '/types/' . $type . '.php';\n"
        + "$type = \\scpp\\compiler\\type_registry::specialize('vector', \\scpp\\compiler\\type_registry::integer());\n"
        + "$queries = []; if ($type !== null) foreach (\\scpp\\compiler\\collection_cardinality::cases() as $op) {\n"
        + "    $queries[] = [$type->cardinality_result($op)->name, $type->lower_cpp_cardinality($op, 'receiver')]; }\n"
        + "echo json_encode($type === null ? null : [$type->name, $type->cpp_type(), $type->cpp_headers(), $queries]);\n")
    def binding():
        value, _ = run(['php', runner, compiler])
        return json.loads(value)
    observations = [['int64', '(receiver).size()'], ['bool_t', '(receiver).empty()']]
    assert binding() == ['vector<int64>', 'scpp::vector_int64', ['scpp/vectors.hpp'], observations]
    names = sorted(v['name'] for v in baseline['vectors'])
    assert names == ['vector_int64', 'vector_string']
    for name in names:
        assert baseline['submodules']['type_exports'][name] == {'symbol': 'scpp::' + name, 'provider': 'core.vectors'}
    assert baseline['submodules']['groups']['vectors']['types'] == names
    assert baseline['submodules']['nodes']['core.vectors']['requires'] == ['core.scalars', 'core.string']
    assert '#include <scpp/numeric.hpp>' not in (output / 'public/scpp/vectors.hpp').read_text()

    # Invalid provider/header and effective cycles fail before changing a successful publication.
    for kind in ('missing', 'legacy_header', 'cycle', 'unpublished', 'duplicate'):
        m, p = copy.deepcopy(metadata), copy.deepcopy(publication)
        if kind == 'missing': m['vector']['provider'] = 'missing'
        if kind == 'legacy_header': p['vectors']['header'] = 'scpp/wrong.hpp'
        if kind == 'cycle': m['submodules']['core.scalars']['requires'] = ['core.vectors']
        if kind == 'unpublished': m['submodules']['core.vectors']['requires'] = ['numeric.scalars']
        if kind == 'duplicate': p['vectors']['specializations'].append(p['vectors']['specializations'][0])
        emit(m, p, 1)
        assert snapshot() == original, kind
    emit(metadata, publication)
    assert snapshot() == original

    no_vectors = copy.deepcopy(publication)
    no_vectors.pop('vectors'); no_vectors.pop('carriers'); no_vectors.pop('hashes', None)
    emit(metadata, no_vectors, 1)  # Split/join explicitly demand the vector<string> export.
    no_vectors['string']['functions'] = [f for f in no_vectors['string']['functions']
                                         if f not in ('explode', 'implode', 'strpos', 'strrpos')]
    absent = emit(metadata, no_vectors)
    assert not list((output / 'public/scpp').glob('*/string_vectors.hpp'))
    assert not (output / 'public/scpp/vectors.hpp').exists()
    assert not any(k.startswith('vector_') for k in absent['submodules']['type_exports'])
    assert 'core.vectors' not in absent['submodules']['published']
    run(['php', generator, 'emit-compiler', mp, pp, facts, context, compiler])
    assert binding() is None, 'Retired publication specialization still resolves'
    assert 'class type_vector_' not in (compiler / 'types/vectors.php').read_text()
    emit(metadata, publication)
    assert snapshot() == original

    # Element-specific dependencies: an int-only vector needs no string or optional numeric provider.
    minimal = copy.deepcopy(no_vectors)
    minimal.pop('string')
    minimal.pop('boxed')
    minimal['vectors'] = {'specializations': [publication['vectors']['specializations'][0]]}
    narrow = emit(metadata, minimal)
    assert narrow['submodules']['nodes']['core.vectors']['requires'] == ['core.scalars']
    assert 'vector_string' not in narrow['submodules']['type_exports']

    report = {}
    for variant in ('default', 'relocated', 'shared_string'):
        m = copy.deepcopy(metadata)
        if variant == 'relocated': m['submodules']['core.vectors']['header'] = 'scpp/collections/list.hpp'
        if variant == 'shared_string': m['vector']['provider'] = 'core.string'
        plan = emit(m, publication)
        provider = plan['submodules']['type_exports']['vector_int64']['provider']
        header = plan['submodules']['nodes'][provider]['header']
        if variant != 'default': assert not (output / 'public/scpp/vectors.hpp').exists()
        run(['php', generator, 'emit-compiler', mp, pp, facts, context, compiler])
        after = {p.name: p.read_bytes() for p in compiler.rglob('*.php')}
        assert {k: v for k, v in after.items() if k != 'vectors.php'} == {k: v for k, v in compiler_before.items() if k != 'vectors.php'}, 'Scalar compiler output changed'
        assert binding() == ['vector<int64>', 'scpp::vector_int64', [header], observations], 'Compiler ignored runtime export placement'
        for flavor in ('cpp', 'php', 'js'):
            adapter = output / f'public/scpp/{flavor}/string_vectors.hpp'
            assert f'#include "{header}"' in adapter.read_text()
        source = work / 'consumer.cpp'
        source.write_text(f'''#include <{header}>
#include <stdexcept>
int main() {{
    scpp::vector_int64 values;
    values.append(scpp::int64{{8}});
    auto copy = values;
    copy.set(scpp::int64{{0}}, scpp::int64{{9}});
    if (values.at(scpp::int64{{0}}) != 8 || copy.at(scpp::int64{{0}}) != 9) return 1;
    if (values.size() != 1 || values.empty()) return 2;
    try {{ values.at(scpp::int64{{-1}}); return 3; }} catch (const std::out_of_range&) {{}}
    scpp::vector_string strings;
    strings.append(scpp::string{{"a\\0b", 3}});
    if (strings.at(scpp::int64{{0}}).size() != 3) return 4;
    return 0;
}}
''')
        flags = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-I' + str(output / 'public')]
        # The public closure itself must not instantiate or include STL templates.
        probe = work / 'header.cpp'; probe.write_text(f'#include <{header}>\n')
        preprocessed, _ = run([*flags, '-E', '-P', probe])
        assert 'template<' not in preprocessed and 'template <' not in preprocessed
        rejected = work / 'rejected.cpp'
        rejected.write_text(f'#include <{header}>\nvoid f() {{ scpp::vector_int64 v; v.append(1.5); }}\n')
        run([*flags, '-fsyntax-only', rejected], 1)
        runtime_object = work / 'runtime.o'
        run([*flags, '-I' + str(ROOT / 'runtime/src/numeric'), '-I' + str(ROOT / 'runtime/src/sequence'), '-I' + str(ROOT / 'runtime/src/ownership'),
             '-c', output / 'src/runtime.cpp', '-o', runtime_object])
        obj = work / 'consumer.o'
        _, compile_ms = run([*flags, '-ftime-trace', '-ftime-trace-granularity=0', '-c', source, '-o', obj])
        _, link_ms = run(['clang++', '-stdlib=libc++', '-fuse-ld=mold', obj, runtime_object, '-o', work / 'consumer'])
        run([work / 'consumer'])
        events = json.loads(obj.with_suffix('.json').read_text())['traceEvents']
        totals = {e['name']: e.get('dur', 0) / 1000 for e in events if e['name'].startswith('Total ')}
        report[variant] = {'compile_ms': compile_ms, 'frontend_ms': totals.get('Total Frontend'),
                           'llvm_ms': totals.get('Total Backend'), 'link_ms': link_ms}
    emit(metadata, publication)
    assert snapshot() == original
    (work / 'results.json').write_text(json.dumps(report, indent=2))
    print(json.dumps(report, indent=2))


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        proof(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-vector-providers-') as directory:
            proof(Path(directory))
