"""Native proofs for metadata-owned providers, declaration relocation and include recovery."""
import argparse
import json
import os
import shlex
import shutil
from pathlib import Path
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[3]
ENV = {**os.environ, 'XDEBUG_MODE': 'off'}


def run(args, expected=0, cwd=None):
    result = subprocess.run(list(map(str, args)), capture_output=True, text=True, env=ENV, timeout=60, cwd=cwd)
    assert result.returncode == expected, (args, result.stdout, result.stderr)
    return result


def snapshot(root):
    return {str(p.relative_to(root)): p.read_bytes() for p in root.rglob('*') if p.is_file()}


def artifacts(root):
    return {str(p.relative_to(root)): p.read_text() for p in root.rglob('*') if p.suffix in ('.cpp', '.hpp')}


def proof(work):
    work.mkdir(parents=True, exist_ok=True)
    facts = ROOT / 'build/clang/native-layout/facts.json'
    context = facts.with_name('context.json')
    generator = ROOT / 'tools/api_generator/generate.php'
    original = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = work / 'publication.json'
    published = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    published['vectors'].pop('header', None)  # Provider metadata owns the relocatable spelling.
    publication.write_text(json.dumps(published))
    report = {'configuration': 'Clang, C++23, libc++, O2/g0, mold, sequential commands, no PCH/LTO; variant runtime compiled once and reused for application builds', 'variants': {}}

    def generate(metadata, runtime, compiler):
        run(['php', generator, 'emit-runtime', metadata, publication, facts, context, runtime])
        run(['php', generator, 'emit-compiler', metadata, publication, facts, context, compiler])

    review_contract_proof(work / 'review-contracts', original, publication, facts, context)
    cmake_provider_proof(work / 'cmake-providers', original, publication, facts, context)

    for variant in ('default', 'renamed', 'relocated', 'type_relocated'):
        directory = work / variant
        directory.mkdir(exist_ok=True)
        metadata = json.loads(json.dumps(original))
        if variant != 'default':
            metadata['submodules']['core.string']['header'] = 'scpp/proof/text.hpp'
            metadata['submodules']['core.output']['header'] = 'scpp/proof/write.hpp'
        if variant == 'relocated':
            metadata['output']['provider'] = 'core.string'
            metadata['submodules']['core.vectors']['header'] = 'scpp/proof/list.hpp'
            metadata['submodules']['core.hashes']['header'] = 'scpp/proof/map.hpp'
        if variant == 'type_relocated':
            metadata['string']['provider'] = 'core.output'
            metadata['submodules']['core.output']['requires_exports'] = metadata['submodules']['core.string'].pop('requires_exports')
        if variant != 'default':
            for provider, header in [('core.scalars', 'scpp/proof/scalars.hpp'), ('numeric.scalars', 'scpp/proof/custom.hpp'), ('core.conversions', 'scpp/proof/conversions.hpp')]:
                metadata['submodules'][provider]['header'] = header
            next(row for row in metadata['type_exports'] if row['identity'] == 'uint8')['name'] = 'byte'
        path = directory / 'metadata.json'
        path.write_text(json.dumps(metadata))
        runtime, compiler = directory / 'runtime', directory / 'compiler'
        generate(path, runtime, compiler)
        before = snapshot(runtime)
        generate(path, runtime, compiler)
        assert snapshot(runtime) == before, 'Nondeterministic publication'
        run(['php', generator, 'emit-runtime', path, publication, facts, context, runtime, '--check'])
        run(['php', generator, 'emit-compiler', path, publication, facts, context, compiler, '--check'])
        # Exercise the real freshness wrappers, which preserve timestamps on no-op builds.
        stamps = {str(p): p.stat().st_mtime_ns for base in (runtime, compiler) for p in base.rglob('*') if p.is_file()}
        run(['cmake', '-DGENERATOR=' + str(ROOT / 'build/clang/scpp_api_generator'),
             '-DMETADATA=' + str(path), '-DPUBLICATION=' + str(publication), '-DOUTPUT=' + str(runtime),
             '-P', ROOT / 'cmake/PublishRuntime.cmake'])
        run(['cmake', '-DPHP=php', '-DGENERATOR=' + str(generator), '-DMETADATA=' + str(path), '-DPUBLICATION=' + str(publication),
             '-DFACTS=' + str(facts), '-DCONTEXT=' + str(context), '-DOUTPUT=' + str(compiler),
             '-P', ROOT / 'cmake/PublishCompiler.cmake'])
        assert all(Path(p).stat().st_mtime_ns == stamp for p, stamp in stamps.items()), 'No-op publication touched artifacts'
        core_header = metadata['submodules'][metadata['scalar_providers']['core']]['header']
        conversion_header = metadata['submodules'][metadata['checked_conversions']['provider']]['header']
        string_header = metadata['submodules'][metadata['string']['provider']]['header']
        output_header = metadata['submodules'][metadata['output']['provider']]['header']
        assert 'scpp/output.hpp' not in (runtime / 'private/output_provider.hpp').read_text() or variant == 'default'
        if variant == 'relocated':
            assert not (runtime / 'public/scpp/proof/write.hpp').exists()
            assert 'void echo_int' in (runtime / 'public' / string_header).read_text()
        flags = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-Wall', '-Wextra', '-Werror',
                 '-I', runtime / 'public', '-I', runtime / 'private',
                 '-I', ROOT / 'runtime/src/numeric', '-I', ROOT / 'runtime/src/sequence']
        closure = {}
        for index, header in enumerate(sorted(set([core_header, conversion_header, string_header, output_header]))):
            isolated = directory / f'header-{index}.cpp'
            isolated.write_text(f'#include <{header}>\n')
            run([*flags, '-fsyntax-only', isolated])
            closure[header] = run([*flags, '-MM', isolated]).stdout
        if variant != 'default':
            assert "return 'scpp::byte';" in (compiler / 'types/uint8.php').read_text()
            alias_probe = directory / 'alias.cpp'
            alias_probe.write_text(f'#include <{core_header}>\nstatic_assert(__is_same(scpp::byte, scpp::uint8));\n')
            run([*flags, '-fsyntax-only', alias_probe])
        if variant == 'type_relocated':
            assert not (runtime / 'public/scpp/proof/text.hpp').exists()
            assert 'class string final' in (runtime / 'public' / string_header).read_text()
        runtime_objects = []
        start = time.perf_counter()
        runtime_sources = [runtime / 'src/runtime.cpp', ROOT / 'runtime/src/output/output.cpp',
                           ROOT / 'runtime/src/entry/entry.cpp', ROOT / 'runtime/src/numeric/arithmetic.cpp']
        if variant == 'relocated':
            runtime_sources.append(runtime / 'src/hash.cpp')
            flags.extend(['-I', ROOT / 'runtime/src/hash'])
        for index, source in enumerate(runtime_sources):
            obj = directory / f'runtime-{index}.o'
            run([*flags, '-c', source, '-o', obj])
            runtime_objects.append(obj)
        row = {'runtime_build_ms': (time.perf_counter() - start) * 1000, 'include_closures': closure, 'builds': {}}
        # A test-local bootstrap substitutes only generated artifacts, keeping production boot unchanged.
        boot = directory / 'boot.php'
        source = (ROOT / 'compiler/boot.php').read_text()
        source = source.replace("__DIR__ . '/generated/", repr(str(compiler) + '/') + " . '")
        source = source.replace('__DIR__', repr(str(ROOT / 'compiler')))
        boot.write_text(source)
        protocol = directory / 'protocol.php'
        source = (ROOT / 'compiler/tests/incremental_executable.php').read_text()
        protocol.write_text(source.replace("__DIR__ . '/../boot.php'", repr(str(boot))))

        for flavor in ('cpp', 'php', 'js'):
            app, out = directory / flavor / 'app', directory / flavor / 'out'
            app.mkdir(parents=True, exist_ok=True)
            (app / 'module.json').write_text('{}')
            def fn(name, typ, body):
                return (f'{typ} {name}()' if flavor == 'cpp' else f'function {name}(): {typ}') + '{' + body + '}'
            stamp = int(time.time())
            def write(name, text):
                nonlocal stamp
                target = app / f'{name}.{flavor}'
                target.write_text(text)
                stamp += 1
                os.utime(target, (stamp, stamp))
            narrowed = {'cpp': 'byte narrowed = (byte) (scalar() + 1); return (int) narrowed;',
                        'php': '$narrowed byte = (byte) (scalar() + 1); return (int) $narrowed;',
                        'js': 'let narrowed: byte = (byte) (scalar() + 1); return (int) narrowed;'}[flavor]
            observations = ''
            if variant == 'relocated':
                for name, typ in [('list_size', 'vector<int>'), ('map_size', 'hash<int,int>')]:
                    local = {'cpp': f'{typ} v = [];', 'php': f'$v {typ} = [];', 'js': f'let v: {typ} = [];'}[flavor]
                    v = '$v' if flavor == 'php' else 'v'
                    body = f'if (empty({v})) {{return count({v});}} return 91;'
                    if typ == 'vector<int>':
                        body = (f'if (!empty({v})) {{return 91;}} {v}[] = 1; {v}[0] = 8; '
                                f'if ({v}[0] !== 8) {{return 91;}} return count({v}) - 1;')
                    write(name, fn(name, 'int', local + body))
                    observations += f'if ({name}() !== 0) {{return 91;}} '
            write('main', observations + 'visible(); body(); scalar(); absent(); ' + narrowed)
            write('unobserved', {'cpp': 'global null unused = null;', 'php': 'global $unused null = null;',
                                 'js': 'global let unused: null = null;'}[flavor])
            write('absent', fn('absent', 'null', 'return null;'))
            write('visible', fn('visible', 'string', 'return "V";'))
            write('body', fn('body', 'int', 'return 1;'))
            write('scalar', fn('scalar', 'int', 'return 7;'))
            process = subprocess.Popen(['php', str(protocol), str(app), str(out)], stdin=subprocess.PIPE,
                                       stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=ENV)
            def step(error=False):
                process.stdin.write('"run"\n')
                process.stdin.flush()
                line = process.stdout.readline()
                assert line, process.stderr.read()
                response = json.loads(line)
                assert ('error' in response) == error, response
                return response
            def fresh():
                fresh_out = directory / flavor / 'fresh'
                result = subprocess.run(['php', str(protocol), str(app), str(fresh_out)], input='"run"\n',
                                        capture_output=True, text=True, env=ENV, timeout=60)
                assert result.returncode == 0 and 'error' not in json.loads(result.stdout), result
                assert artifacts(out) == artifacts(fresh_out), 'Incremental/fresh mismatch'
            try:
                step()
                # Native bundle paths use source keys; find the scalar unit by its definition.
                scalar_units = {p: text for p, text in artifacts(out).items() if p.endswith('.cpp') and 'return 7LL;' in text}
                assert scalar_units, 'Missing scalar-only proof unit'
                for path, text in scalar_units.items():
                    header = (out / path).with_suffix('.hpp').read_text()
                    for unit in (header, text):
                        assert f'#include <{core_header}>' in unit
                        for excluded in (conversion_header, string_header, output_header,
                                         metadata['submodules'][metadata['scalar_providers']['numeric']]['header']):
                            assert f'#include <{excluded}>' not in unit
                baseline = artifacts(out)
                globals_only = [p for p, text in baseline.items() if p.endswith('.cpp') and 'constinit scpp::null_t var_unused' in text]
                assert len(globals_only) == 1
                assert not (out / globals_only[0]).with_suffix('.hpp').exists()
                assert [line for line in baseline[globals_only[0]].splitlines() if line.startswith('#include')] == [f'#include <{core_header}>']
                null_units = [p for p, text in baseline.items() if p.endswith('.cpp') and 'scpp::null_t absent_()' in text]
                assert len(null_units) == 1
                for suffix in ('.cpp', '.hpp'):
                    unit = (out / null_units[0]).with_suffix(suffix).read_text()
                    assert [line for line in unit.splitlines() if line.startswith('#include <')] == [f'#include <{core_header}>']
                if variant == 'relocated':
                    query_units = [unit for path, unit in baseline.items() if path.endswith('.cpp')
                                   and '.size()' in unit and '.empty()' in unit]
                    assert len(query_units) == 2
                    for family in ('core.vectors', 'core.hashes'):
                        header = metadata['submodules'][family]['header']
                        assert sum(f'#include <{header}>' in unit for unit in query_units) == 1
                        assert not (runtime / 'public' / original['submodules'][family]['header']).exists()
                local = {'cpp': 'string text = "B";', 'php': '$text string = "B";', 'js': 'let text: string = "B";'}[flavor]
                write('body', fn('body', 'int', local + ('echo $text;' if flavor == 'php' else 'echo text;') + 'return 1;'))
                changed = step()
                assert len(changed['emitted_sources']) == 1, changed
                fresh()
                texts = artifacts(out)
                if variant != 'default':
                    assert any('scpp::byte ' in text for text in texts.values()), 'Export spelling not used by scalar declaration'
                    assert any('scpp::checked_int64_to_uint8(' in text for text in texts.values()), 'Checked conversion helper missing'
                headers = [text for p, text in texts.items() if p.endswith('.hpp')]
                assert any(f'#include <{string_header}>' in text for text in headers)
                if output_header != string_header:
                    assert all(f'#include <{output_header}>' not in text for text in headers)
                body_units = [text for p, text in texts.items() if p.endswith('.cpp') and 'echo_bytes(' in text]
                assert len(body_units) == 1 and f'#include <{output_header}>' in body_units[0]
                assert f'#include <{string_header}>' in body_units[0]
                assert any('return 7LL;' in text and string_header not in text and output_header not in text for p, text in texts.items() if p.endswith('.cpp'))
                for p, text in scalar_units.items():
                    assert texts[p] == text, 'Unrelated scalar unit changed'
                # Build the actual generated multifile program and retain stage timings.
                objects = []
                start = time.perf_counter()
                for index, source in enumerate(sorted(out.rglob('*.cpp'))):
                    obj = directory / flavor / f'app-{index}.o'
                    run([*flags, '-ftime-trace', '-c', source, '-o', obj])
                    objects.append(obj)
                link_start = time.perf_counter()
                executable = directory / flavor / 'program'
                run([*flags, '--ld-path=ld.mold', *objects, *runtime_objects, '-o', executable])
                elapsed = (time.perf_counter() - start) * 1000
                link_ms = (time.perf_counter() - link_start) * 1000
                result = run([executable], 8)
                assert result.stdout == 'B'
                totals = {}
                for obj in objects:
                    for event in json.loads(obj.with_suffix('.json').read_text())['traceEvents']:
                        if event['name'].startswith('Total '):
                            name = event['name'][6:]
                            totals[name] = totals.get(name, 0) + event['dur'] / 1000
                row['builds'][flavor] = {'php_ms': changed['php_ms'], 'php_and_native_ms': changed['php_ms'] + elapsed, 'native_ms': elapsed, 'link_command_ms': link_ms,
                                        'frontend_ms': totals.get('Frontend', 0), 'backend_ms': totals.get('Backend', 0),
                                        'optimizer_ms': totals.get('Optimizer', 0), 'codegen_ms': totals.get('CodeGenPasses', 0)}
                write('body', fn('body', 'int', 'return missing();'))
                step(True)
                step(True)
                write('body', fn('body', 'int', 'return 1;'))
                step()
                fresh()
                assert artifacts(out) == baseline, 'Removed output use left stale includes'
                assert step()['preparation']['prepared'] == 0
            finally:
                process.stdin.close()
                assert process.wait(timeout=10) == 0, process.stderr.read()
        report['variants'][variant] = row

    # Required providers may be reached transitively; do not require a fixed direct-edge list.
    transitive = json.loads(json.dumps(original))
    transitive['submodules']['core.string']['requires_exports'] = []
    transitive['submodules']['core.string']['requires'] = ['core.output']
    transitive['submodules']['core.output']['requires'] = ['core.scalars']
    path = work / 'transitive.json'
    path.write_text(json.dumps(transitive))
    generated = work / 'transitive-runtime'
    run(['php', generator, 'emit-runtime', path, publication, facts, context, generated])
    isolated = work / 'transitive.cpp'
    isolated.write_text('#include <scpp/string.hpp>\n')
    run(['clang++', '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I', generated / 'public', isolated])
    text = (generated / 'public/scpp/string.hpp').read_text()
    assert '#include "scpp/output.hpp"' in text and '#include "scpp/core/scalars.hpp"' not in text

    # Invalid metadata must leave a previously published directory intact.
    directory = work / 'default'
    destination = directory / 'runtime'
    before = snapshot(destination)
    compiler_before = snapshot(directory / 'compiler')
    mutations = {
        'unknown_provider': lambda m: m['string'].update(provider='missing'),
        'omitted_requirements': lambda m: m['submodules']['core.string'].update(requires=[], requires_exports=[]),
        'shared_scalar_provider': lambda m: m['scalar_providers'].update(numeric=m['scalar_providers']['core']),
        'missing_dependency': lambda m: m['submodules']['core.string'].update(requires=['missing']),
        'cycle': lambda m: (m['submodules']['core.string'].update(requires=['core.output']), m['submodules']['core.output'].update(requires=['core.string'])),
        'invalid_header': lambda m: m['submodules']['core.string'].update(header='../private.hpp'),
        'conflict': lambda m: m['submodules']['core.string'].update(header='scpp/core/scalars.hpp'),
        'unknown_export': lambda m: m['type_exports'].append({'identity': 'imaginary', 'name': 'imaginary', 'declaration_group': 'scalar_core'}),
        'duplicate_export': lambda m: m['type_exports'].append(m['type_exports'][0].copy()),
        'missing_export': lambda m: m.update(type_exports=[r for r in m['type_exports'] if r['identity'] != 'bool_t']),
        'unknown_export_provider': lambda m: m['scalar_providers'].update(numeric='missing'),
        'false_placement': lambda m: m['type_exports'][0].update(declaration_group='scalar_core'),
        'undeclared_name': lambda m: m['type_exports'][0].update(name='imaginary'),
        'missing_export_provider': lambda m: m['type_exports'][0].pop('declaration_group'),
        'wrong_export_group': lambda m: m['type_exports'][-1].update(declaration_group='output'),
        'competing_export_owner': lambda m: m['type_exports'][-1].update(provider='core.string'),
        'export_cycle': lambda m: m['submodules']['core.output'].update(requires_exports=['string']) or m['submodules']['core.string'].update(requires=['core.output']),
        'unknown_export_dependency': lambda m: m['submodules']['core.string'].update(requires_exports=['imaginary']),
        'unavailable_module': lambda m: m['submodules']['core.string'].update(module='missing'),
    }
    for name, mutate in mutations.items():
        metadata = json.loads(json.dumps(original))
        mutate(metadata)
        invalid = work / f'{name}.json'
        invalid.write_text(json.dumps(metadata))
        run(['php', generator, 'emit-runtime', invalid, publication, facts, context, destination], 1)
        assert snapshot(destination) == before, name
        run(['php', generator, 'emit-compiler', invalid, publication, facts, context, directory / 'compiler'], 1)
        assert snapshot(directory / 'compiler') == compiler_before, name
    binding_mutations = {
        'competing_header': lambda m: m['compiler']['runtime_bindings']['echo_bytes'].update(header='scpp/other.hpp'),
        'wrong_storage_group': lambda m: m['compiler']['runtime_bindings']['string_storage'].update(declaration_group='output'),
        'wrong_output_group': lambda m: m['compiler']['runtime_bindings']['echo_int'].update(declaration_group='string'),
        'unknown_group': lambda m: m['compiler']['runtime_bindings']['echo_int'].update(declaration_group='missing'),
        'storage_header_fallback': lambda m: (m['compiler']['runtime_bindings']['string_storage'].pop('type_export'),
                                            m['compiler']['runtime_bindings']['string_storage'].update(header='scpp/string.hpp')),
        'output_header_fallback': lambda m: (m['compiler']['runtime_bindings']['echo_bytes'].pop('declaration_group'),
                                           m['compiler']['runtime_bindings']['echo_bytes'].update(header='scpp/output.hpp')),
    }
    for name, mutate in binding_mutations.items():
        metadata = json.loads(json.dumps(original))
        mutate(metadata)
        invalid = work / (name + '.json')
        invalid.write_text(json.dumps(metadata))
        run(['php', generator, 'emit-compiler', invalid, publication, facts, context, directory / 'compiler'], 1)
        assert snapshot(directory / 'compiler') == compiler_before, name
    # Native depfiles must observe metadata-driven changes to the generated private bridge.
    command = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-I', str(destination / 'public'),
               '-I', str(destination / 'private'), '-MMD', '-MF', 'output-probe.d', '-c',
               str(ROOT / 'runtime/src/output/output.cpp'), '-o', 'output-probe.o']
    graph = directory / 'proof.ninja'
    graph.write_text('rule compile\n  command = ' + shlex.join(command) + '\n  depfile = output-probe.d\n  deps = gcc\n'
                     + 'build output-probe.o: compile ' + str(ROOT / 'runtime/src/output/output.cpp') + '\n')
    run(['ninja', '-f', graph.name], cwd=directory)
    obj = directory / 'output-probe.o'
    stamp = obj.stat().st_mtime_ns
    run(['ninja', '-f', graph.name], cwd=directory)
    assert obj.stat().st_mtime_ns == stamp, 'No-op rebuilt native output implementation'
    # Rename/relocate into the same publication and require obsolete paths to disappear.
    renamed = work / 'renamed/metadata.json'
    run(['php', generator, 'emit-runtime', renamed, publication, facts, context, destination, '--check'], 1)
    run(['php', generator, 'emit-compiler', renamed, publication, facts, context, directory / 'compiler', '--check'], 1)
    run(['cmake', '-DGENERATOR=' + str(ROOT / 'build/clang/scpp_api_generator'),
         '-DMETADATA=' + str(renamed), '-DPUBLICATION=' + str(publication), '-DOUTPUT=' + str(destination),
         '-P', ROOT / 'cmake/PublishRuntime.cmake'])
    run(['cmake', '-DPHP=php', '-DGENERATOR=' + str(generator), '-DMETADATA=' + str(renamed), '-DPUBLICATION=' + str(publication),
         '-DFACTS=' + str(facts), '-DCONTEXT=' + str(context), '-DOUTPUT=' + str(directory / 'compiler'),
         '-P', ROOT / 'cmake/PublishCompiler.cmake'])
    run(['ninja', '-f', graph.name], cwd=directory)
    assert obj.stat().st_mtime_ns != stamp, 'Provider relocation did not rebuild native consumer'
    assert not (destination / 'public/scpp/string.hpp').exists()
    assert not (destination / 'public/scpp/output.hpp').exists()
    generate(work / 'relocated/metadata.json', destination, directory / 'compiler')
    assert not (destination / 'public/scpp/proof/write.hpp').exists()
    report['cached_application'] = cached_application_proof(work / 'cached-application', original, facts, context, publication)
    (work / 'results.json').write_text(json.dumps(report, indent=2) + '\n')
    print('Provider relocation, native includes, all-flavor incremental recovery and publication validation passed')



def cmake_provider_proof(work, original, publication_path, facts, context):
    """Use the production CMake inventory helper across relocation in an existing build."""
    work.mkdir(exist_ok=True)
    metadata = work / 'metadata.json'
    metadata.write_text(json.dumps(original))
    publication = work / 'publication.json'
    request = json.loads(publication_path.read_text())
    request.setdefault('modules', []).append('numeric')
    request['types'] = list(original['types'])
    publication.write_text(json.dumps(request))
    generated, build = work / 'runtime', work / 'build'
    (work / 'CMakeLists.txt').write_text(f'''cmake_minimum_required(VERSION 3.25)
project(provider_outputs NONE)
find_program(PHP_EXECUTABLE php REQUIRED)
set(runtime_metadata "{metadata}")
set(php_generator_source "{ROOT / 'tools/api_generator/generate.php'}")
include("{ROOT / 'cmake/ProviderOutputs.cmake'}")
scpp_provider_outputs(outputs "{generated}" "{publication}")
file(WRITE "${{CMAKE_BINARY_DIR}}/outputs.txt" "${{outputs}}")
add_custom_command(OUTPUT ${{outputs}}
  COMMAND ${{PHP_EXECUTABLE}} ${{php_generator_source}} emit-runtime
    ${{runtime_metadata}} "{publication}" "{facts}" "{context}" "{generated}"
  DEPENDS ${{runtime_metadata}} "{publication}" VERBATIM)
add_custom_target(publication ALL DEPENDS ${{outputs}})
''')
    run(['cmake', '-G', 'Ninja', '-S', work, '-B', build])
    def check():
        run(['cmake', '--build', build])
        paths = (build / 'outputs.txt').read_text().split(';')
        assert all(Path(path).is_file() for path in paths)
        stamps = {path: Path(path).stat().st_mtime_ns for path in paths}
        assert 'no work to do' in run(['cmake', '--build', build]).stdout
        assert all(Path(path).stat().st_mtime_ns == stamp for path, stamp in stamps.items())
        return paths
    before = check()
    value = json.loads(json.dumps(original))
    for provider, name in [('core.scalars', 'core'), ('numeric.scalars', 'custom'), ('core.conversions', 'checked')]:
        value['submodules'][provider]['header'] = f'scpp/cmake/{name}.hpp'
    metadata.write_text(json.dumps(value))
    after = check()  # Ninja must trigger CMake regeneration itself.
    assert before != after
    assert all(not Path(path).exists() for path in set(before) - set(after))
    assert all(str(generated / f'public/scpp/cmake/{name}.hpp') in after for name in ('core', 'custom', 'checked'))
    # A missing declared output must regenerate, without a manual clean/configure.
    Path(after[0]).unlink()
    check()
    # Removing optional demand must retire its header and CMake output in the same build.
    request['types'] = list(original['core_integers'])
    request['modules'] = []
    publication.write_text(json.dumps(request))
    reduced = check()
    custom = str(generated / 'public/scpp/cmake/custom.hpp')
    assert custom not in reduced and not Path(custom).exists()
    assert set(after) - set(reduced) == {custom}
    # Re-enabling demand must restore the output without a manual clean/configure.
    request['types'] = list(original['types'])
    request['modules'] = ['numeric']
    publication.write_text(json.dumps(request))
    assert check() == after


def review_contract_proof(work, original, publication_path, facts, context):
    """Scalar demand, separate checked helpers, semantic dependencies and header retirement."""
    work.mkdir(exist_ok=True)
    generator = ROOT / 'tools/api_generator/generate.php'
    publication = json.loads(publication_path.read_text())
    metadata_path, request = work / 'metadata.json', work / 'publication.json'
    runtime, compiler = work / 'runtime', work / 'compiler'
    def emit(metadata, publication, expected=0):
        metadata_path.write_text(json.dumps(metadata))
        request.write_text(json.dumps(publication))
        prepared = run(['php', generator, 'prepare-runtime', metadata_path, request, facts, context], expected)
        result = run(['php', generator, 'emit-runtime', metadata_path, request, facts, context, runtime], expected)
        if expected == 0:
            assert json.loads(prepared.stdout) == json.loads((runtime / 'prepared.json').read_text())
            headers = json.loads(run(['php', generator, 'provider-headers', metadata_path, request]).stdout)
            assert all((runtime / 'public' / h).is_file() for h in headers)
        else:
            assert prepared.stderr == result.stderr
        return result
    def compile_header(header, body=''):
        source = work / 'header.cpp'
        source.write_text(f'#include <{header}>\n' + body)
        run(['clang++', '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I', runtime / 'public', source])
    # Seed obsolete outputs and require the publisher to retire both legacy scalar headers.
    legacy = runtime / 'public/scpp'
    legacy.mkdir(parents=True, exist_ok=True)
    for name in ('integers.hpp', 'values.hpp'):
        (legacy / name).write_text('// obsolete scalar output\n')
    emit(original, publication)
    for name in ('integers.hpp', 'values.hpp', 'numeric/scalars.hpp'):
        assert not (legacy / name).exists()
    # The provider can exist even when its null declaration is not requested.
    compiler_command = ['php', generator, 'emit-compiler', metadata_path, request, facts, context, compiler]
    run(compiler_command)
    compiler_before = snapshot(compiler)
    without_null = {key: value for key, value in publication.items() if key not in ('carriers', 'hashes', 'boxed')}
    without_null['string'] = dict(publication['string'], functions=[])
    without_null['values'] = [name for name in publication['values'] if name != 'null_t']
    emit(original, without_null)
    assert 'using null_t' not in (legacy / 'core/scalars.hpp').read_text()
    assert 'null_t' in run(compiler_command, 1).stderr
    assert snapshot(compiler) == compiler_before
    without_null['values'].append('null_t')
    emit(original, without_null)
    run(compiler_command)
    compile_header('scpp/core/scalars.hpp', 'scpp::null_t value = nullptr;')
    emit(original, publication)
    core = (legacy / 'core/scalars.hpp').read_text()
    assert '#include' not in core and 'using int16 =' not in core and 'using uint32 =' not in core
    compile_header('scpp/core/scalars.hpp', 'static_assert(__is_same(scpp::int64, __INT64_TYPE__));\nstatic_assert(__is_same(scpp::byte, __UINT8_TYPE__));\nstatic_assert(__is_same(scpp::bool_t, bool));\nstatic_assert(__is_same(scpp::float64, double));\n')
    compile_header('scpp/conversions.hpp', 'auto convert(scpp::int64 x) { return scpp::checked_int64_to_uint8(x); }\n')
    assert 'numeric/scalars.hpp' not in (legacy / 'conversions.hpp').read_text()
    before = snapshot(runtime)
    for field in ('types', 'values'):
        invalid = json.loads(json.dumps(publication))
        invalid['headers'][field] = 'scpp/old.hpp'
        assert 'Obsolete scalar header fields' in emit(original, invalid, 1).stderr
        assert snapshot(runtime) == before
    cyclic = json.loads(json.dumps(original))
    cyclic['submodules']['core.scalars']['requires'] = ['core.conversions']
    assert 'Cyclic' in emit(cyclic, publication, 1).stderr
    assert snapshot(runtime) == before
    absent = json.loads(json.dumps(original))
    absent['submodules']['core.output']['requires'] = ['numeric.scalars']
    assert 'not published' in emit(absent, publication, 1).stderr
    assert snapshot(runtime) == before
    # The standalone scalar preparation entry point must enforce the same graph contract.
    scalar_request = {key: value for key, value in publication.items() if key in
                      ('version', 'namespace', 'types', 'operators', 'headers', 'values', 'short_aliases', 'modules')}
    request.write_text(json.dumps(scalar_request))
    metadata_path.write_text(json.dumps(cyclic))
    for command in ('prepare-scalars', 'emit-scalars'):
        args = [runtime] if command == 'emit-scalars' else []
        assert 'Cyclic' in run(['php', generator, command, metadata_path, request, facts, context, *args], 1).stderr
    assert snapshot(runtime) == before
    # Runtime finalization must wait for string demand; the scalar projection lacks it.
    later_family = json.loads(json.dumps(original))
    later_family['submodules']['core.output']['requires'] = ['core.string']
    emit(later_family, publication)
    request.write_text(json.dumps(scalar_request))
    before = snapshot(runtime)
    for command in ('prepare-scalars', 'emit-scalars'):
        args = [runtime] if command == 'emit-scalars' else []
        assert 'not published' in run(['php', generator, command, metadata_path, request, facts, context, *args], 1).stderr
    assert snapshot(runtime) == before
    # Optional numeric publication changes declaration demand, not source admissions.
    numeric = json.loads(json.dumps(publication))
    numeric['modules'] = ['numeric']
    numeric['types'] = list(original['types'])
    emit(original, numeric)
    compile_header('scpp/numeric/scalars.hpp', 'static_assert(__is_same(scpp::int16, __INT16_TYPE__));\nstatic_assert(__is_same(scpp::uint16, __UINT16_TYPE__));\nstatic_assert(__is_same(scpp::uint32, __UINT32_TYPE__));\n')
    assert '#include' not in (legacy / 'numeric/scalars.hpp').read_text()
    assert 'numeric/scalars.hpp' in (legacy / 'conversions.hpp').read_text()
    # Reduced numeric-only publication must not pull an absent core provider.
    reduced = {k: v for k, v in numeric.items() if k in
               ('version', 'namespace', 'types', 'operators', 'headers', 'values', 'short_aliases', 'modules')}
    reduced.update(types=['int16', 'uint16'], values=[], operators=[], short_aliases=[])
    emit(original, reduced)
    assert not (legacy / 'core/scalars.hpp').exists()
    assert 'core/scalars.hpp' not in (legacy / 'conversions.hpp').read_text()
    compile_header('scpp/numeric/scalars.hpp')
    compile_header('scpp/conversions.hpp')
    denied = json.loads(json.dumps(reduced))
    denied['modules'] = []
    before = snapshot(runtime)
    assert 'requires numeric module' in emit(original, denied, 1).stderr
    assert snapshot(runtime) == before
    # String conversion dependencies follow published casts, including optional widths.
    reduced.update(types=['int64', 'int16'], values=['bool_t'], string={'operations': [], 'casts': []})
    emit(original, reduced)
    assert 'numeric/scalars.hpp' not in (legacy / 'string.hpp').read_text()
    reduced['string']['casts'] = ['int16']
    emit(original, reduced)
    assert 'numeric/scalars.hpp' in (legacy / 'string.hpp').read_text()
    graph = json.loads((runtime / 'prepared.json').read_text())['submodules']
    for provider in ('core.string', 'core.conversions'):
        assert 'numeric.scalars' in graph['nodes'][provider]['requires']
        assert 'numeric.scalars' in graph['closures'][provider]
    compile_header('scpp/string.hpp')
    # Relocate all three providers with real declarations and checked helper signatures.
    matched = json.loads(json.dumps(original))
    for provider, header in [('core.scalars', 'scpp/review/core.hpp'), ('numeric.scalars', 'scpp/review/custom.hpp'), ('core.conversions', 'scpp/review/checked.hpp')]:
        matched['submodules'][provider]['header'] = header
    emit(matched, numeric)
    run(['php', generator, 'emit-compiler', metadata_path, request, facts, context, compiler])
    for identity in ('uint8', 'int64', 'float64', 'bool'):
        assert 'scpp/review/core.hpp' in (compiler / f'types/{identity}.php').read_text()
    assert 'scpp/review/checked.hpp' in (compiler / 'types/uint8.php').read_text()
    for header in ('scpp/review/core.hpp', 'scpp/review/custom.hpp', 'scpp/review/checked.hpp'):
        compile_header(header)
    run(['clang++', '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I', runtime / 'public',
         '-I', runtime / 'private', '-I', ROOT / 'runtime/src/numeric',
         '-I', ROOT / 'runtime/src/sequence', runtime / 'src/runtime.cpp'])


def cached_application_proof(work, original, facts, context, publication):
    """Restart the actual project compiler after regeneration and reuse application/native outputs."""
    work.mkdir(exist_ok=True)
    compiler = work / 'compiler'
    # Copy the implementation so Project_Changes scans the test publication, not production PHP.
    for source in (ROOT / 'compiler').rglob('*.php'):
        relative = source.relative_to(ROOT / 'compiler')
        if relative.parts[0] in ('tests', 'generated'):
            continue
        target = compiler / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)
    runtime = work / 'runtime'
    runner = work / 'project.php'
    runner.write_text('<?php\nrequire ' + repr(str(compiler / 'boot.php')) + ';\n'
                      + '$result = (new \\scpp\\compiler\\Project_Compiler(' + repr(str(ROOT / 'runtime'))
                      + '))->run($argv[1], $argv[2], $argv[3]);\n'
                      + 'echo json_encode($result, JSON_THROW_ON_ERROR), "\\n";\n')
    generator = ROOT / 'tools/api_generator/generate.php'
    metadata = work / 'metadata.json'
    reports = {}
    def generate(value):
        metadata.write_text(json.dumps(value))
        run(['php', generator, 'emit-runtime', metadata, publication, facts, context, runtime])
        run(['php', generator, 'emit-compiler', metadata, publication, facts, context, compiler / 'generated'])
    generate(original)
    for flavor in ('cpp', 'php', 'js'):
        app, out, native = (work / flavor / name for name in ('app', 'out', 'native'))
        for directory in (app, out, native):
            directory.mkdir(parents=True, exist_ok=True)
        (app / 'module.json').write_text('{}')
        local = {'cpp': 'string text = "cached";', 'php': '$text string = "cached";',
                 'js': 'let text: string = "cached";'}[flavor]
        source = app / ('main.' + flavor)
        narrowed = {'cpp': 'byte narrowed = (byte) value(); return (int) narrowed;',
                    'php': '$narrowed byte = (byte) value(); return (int) $narrowed;',
                    'js': 'let narrowed: byte = (byte) value(); return (int) narrowed;'}[flavor]
        source.write_text(local + ('echo $text;' if flavor == 'php' else 'echo text;') + narrowed)
        provider_source = app / ('value.' + flavor)
        provider_source.write_text(('int value()' if flavor == 'cpp' else 'function value(): int') + '{return 8;}')
        provider_before = provider_source.stat().st_mtime_ns, provider_source.read_bytes()
        source_before = source.stat().st_mtime_ns, source.read_bytes()
        def compile_project():
            return json.loads(run(['php', runner, app, facts, out]).stdout)
        # Reset the publication for this flavor before its initial build.
        generate(original)
        assert compile_project()['rebuilt']
        assert 'scpp::uint8 ' in '\n'.join(artifacts(out).values())
        assert not compile_project()['rebuilt'], 'Initial application cache not reusable'
        units = sorted(out.rglob('*.cpp'))
        flags = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-I', str(runtime / 'public'),
                 '-I', str(runtime / 'private'), '-I', str(ROOT / 'runtime/src/numeric'),
                 '-I', str(ROOT / 'runtime/src/sequence')]
        lines = ['rule compile', '  command = ' + shlex.join(flags) + ' -MMD -MF $out.d -c $in -o $out',
                 '  depfile = $out.d', '  deps = gcc', 'rule link',
                 '  command = clang++ -stdlib=libc++ --ld-path=ld.mold $in -o $out']
        inputs = units + [runtime / 'src/runtime.cpp', ROOT / 'runtime/src/output/output.cpp',
                          ROOT / 'runtime/src/entry/entry.cpp', ROOT / 'runtime/src/numeric/arithmetic.cpp']
        for index, unit in enumerate(inputs):
            escaped = str(unit).replace('$', '$$').replace(' ', '$ ').replace(':', '$:')
            lines.append(f'build unit-{index}.o: compile {escaped}')
        lines += ['build program: link ' + ' '.join(f'unit-{i}.o' for i in range(len(inputs))), 'default program']
        (native / 'build.ninja').write_text('\n'.join(lines) + '\n')
        run(['ninja', '-j', '4'], cwd=native)
        assert run([native / 'program'], 8).stdout == 'cached'
        objects = [native / f'unit-{i}.o' for i in range(len(units))]
        before = [obj.stat().st_mtime_ns for obj in objects]
        run(['ninja', '-j', '4'], cwd=native)
        assert [obj.stat().st_mtime_ns for obj in objects] == before
        changes = []
        for label in ('spelling_only', 'renamed', 'relocated', 'type_relocated'):
            value = json.loads(json.dumps(original))
            if label != 'spelling_only':
                value['submodules']['core.string']['header'] = 'scpp/cache/text.hpp'
                value['submodules']['core.output']['header'] = 'scpp/cache/write.hpp'
                value['submodules']['core.scalars']['header'] = 'scpp/cache/scalars.hpp'
                value['submodules']['core.conversions']['header'] = 'scpp/cache/checked.hpp'
            if label == 'relocated':
                value['output']['provider'] = 'core.string'
            if label == 'type_relocated':
                value['string']['provider'] = 'core.output'
                value['submodules']['core.output']['requires_exports'] = value['submodules']['core.string'].pop('requires_exports')
            next(row for row in value['type_exports'] if row['identity'] == 'uint8')['name'] = 'byte'
            if label == 'spelling_only':
                assert value['submodules'] == original['submodules']
                runtime_before = artifacts(runtime)
                application_before = artifacts(out)
            generate(value)
            if label == 'spelling_only':
                assert artifacts(runtime) == runtime_before, 'Spelling-only change altered runtime declarations'
            # New process: PHP classes loaded before regeneration cannot be hot-replaced.
            response = compile_project()
            assert response['rebuilt'], 'Regenerated compiler did not invalidate application cache'
            texts = artifacts(out)
            joined = '\n'.join(texts.values())
            assert 'scpp::byte ' in joined and 'scpp::uint8 ' not in joined
            assert 'scpp::checked_int64_to_uint8(' in joined
            if label == 'spelling_only':
                assert texts == {path: text.replace('scpp::uint8', 'scpp::byte')
                                 for path, text in application_before.items()}, 'Spelling-only change affected other emission'
            else:
                assert '<scpp/core/scalars.hpp>' not in joined and '<scpp/conversions.hpp>' not in joined
                assert '<scpp/cache/scalars.hpp>' in joined and '<scpp/cache/checked.hpp>' in joined
                assert '<scpp/string.hpp>' not in joined and '<scpp/output.hpp>' not in joined
                assert ('<scpp/cache/text.hpp>' in joined) == (label != 'type_relocated')
                assert ('<scpp/cache/write.hpp>' in joined) == (label != 'relocated')
            run(['ninja', '-j', '4'], cwd=native)
            after = [obj.stat().st_mtime_ns for obj in objects]
            assert after != before, 'Existing application objects were not rebuilt'
            assert run([native / 'program'], 8).stdout == 'cached'
            assert not compile_project()['rebuilt'], 'Regenerated application cache not reusable'
            run(['ninja', '-j', '4'], cwd=native)
            assert [obj.stat().st_mtime_ns for obj in objects] == after, 'No-op native rebuild'
            assert (source.stat().st_mtime_ns, source.read_bytes()) == source_before, 'Source changed during metadata proof'
            assert (provider_source.stat().st_mtime_ns, provider_source.read_bytes()) == provider_before
            before = after
            changes.append(label)
        reports[flavor] = {'rebuilt_and_executed': changes, 'compiler_restart_required': True}
    return reports


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        proof(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-submodules-') as directory:
            proof(Path(directory))
