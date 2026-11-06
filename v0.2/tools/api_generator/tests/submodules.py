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
    publication.write_text((ROOT / 'runtime/metadata/publication.json').read_text())
    report = {'configuration': 'Clang, C++23, libc++, O2/g0, mold, sequential commands, no PCH/LTO; variant runtime compiled once and reused for application builds', 'variants': {}}

    def generate(metadata, runtime, compiler):
        run(['php', generator, 'emit-runtime', metadata, publication, facts, context, runtime])
        run(['php', generator, 'emit-compiler', metadata, facts, context, compiler])

    for variant in ('default', 'renamed', 'relocated'):
        directory = work / variant
        directory.mkdir(exist_ok=True)
        metadata = json.loads(json.dumps(original))
        if variant != 'default':
            metadata['submodules']['core.string']['header'] = 'scpp/proof/text.hpp'
            metadata['submodules']['core.output']['header'] = 'scpp/proof/write.hpp'
        if variant == 'relocated':
            metadata['output']['provider'] = 'core.string'
        path = directory / 'metadata.json'
        path.write_text(json.dumps(metadata))
        runtime, compiler = directory / 'runtime', directory / 'compiler'
        generate(path, runtime, compiler)
        before = snapshot(runtime)
        generate(path, runtime, compiler)
        assert snapshot(runtime) == before, 'Nondeterministic publication'
        run(['php', generator, 'emit-runtime', path, publication, facts, context, runtime, '--check'])
        run(['php', generator, 'emit-compiler', path, facts, context, compiler, '--check'])
        # Exercise the real freshness wrappers, which preserve timestamps on no-op builds.
        stamps = {str(p): p.stat().st_mtime_ns for base in (runtime, compiler) for p in base.rglob('*') if p.is_file()}
        run(['cmake', '-DGENERATOR=' + str(ROOT / 'build/clang/scpp_api_generator'),
             '-DMETADATA=' + str(path), '-DPUBLICATION=' + str(publication), '-DOUTPUT=' + str(runtime),
             '-P', ROOT / 'cmake/PublishRuntime.cmake'])
        run(['cmake', '-DPHP=php', '-DGENERATOR=' + str(generator), '-DMETADATA=' + str(path),
             '-DFACTS=' + str(facts), '-DCONTEXT=' + str(context), '-DOUTPUT=' + str(compiler),
             '-P', ROOT / 'cmake/PublishCompiler.cmake'])
        assert all(Path(p).stat().st_mtime_ns == stamp for p, stamp in stamps.items()), 'No-op publication touched artifacts'
        string_header = metadata['submodules']['core.string']['header']
        output_header = metadata['submodules'][metadata['output']['provider']]['header']
        assert 'scpp/output.hpp' not in (runtime / 'private/output_provider.hpp').read_text() or variant == 'default'
        if variant == 'relocated':
            assert not (runtime / 'public/scpp/proof/write.hpp').exists()
            assert 'void echo_int' in (runtime / 'public' / string_header).read_text()
        flags = ['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-Wall', '-Wextra', '-Werror',
                 '-I', runtime / 'public', '-I', runtime / 'private',
                 '-I', ROOT / 'runtime/src/numeric', '-I', ROOT / 'runtime/src/sequence']
        closure = {}
        for index, header in enumerate(sorted(set([string_header, output_header]))):
            isolated = directory / f'header-{index}.cpp'
            isolated.write_text(f'#include <{header}>\n')
            run([*flags, '-fsyntax-only', isolated])
            closure[header] = run([*flags, '-MM', isolated]).stdout
        runtime_objects = []
        start = time.perf_counter()
        for index, source in enumerate([runtime / 'src/runtime.cpp', ROOT / 'runtime/src/output/output.cpp',
                                        ROOT / 'runtime/src/entry/entry.cpp', ROOT / 'runtime/src/numeric/arithmetic.cpp']):
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
            write('main', 'visible(); body(); scalar(); return 8;')
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
                baseline = artifacts(out)
                local = {'cpp': 'string text = "B";', 'php': '$text string = "B";', 'js': 'let text: string = "B";'}[flavor]
                write('body', fn('body', 'int', local + ('echo $text;' if flavor == 'php' else 'echo text;') + 'return 1;'))
                changed = step()
                assert len(changed['emitted_sources']) == 1, changed
                fresh()
                texts = artifacts(out)
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
    transitive['submodules']['core.string']['requires'] = ['core.output']
    transitive['submodules']['core.output']['requires'] = ['core.integers', 'core.values']
    path = work / 'transitive.json'
    path.write_text(json.dumps(transitive))
    generated = work / 'transitive-runtime'
    run(['php', generator, 'emit-runtime', path, publication, facts, context, generated])
    isolated = work / 'transitive.cpp'
    isolated.write_text('#include <scpp/string.hpp>\n')
    run(['clang++', '-std=c++23', '-stdlib=libc++', '-fsyntax-only', '-I', generated / 'public', isolated])
    text = (generated / 'public/scpp/string.hpp').read_text()
    assert '#include "scpp/output.hpp"' in text and '#include "scpp/values.hpp"' not in text

    # Invalid metadata must leave a previously published directory intact.
    directory = work / 'default'
    destination = directory / 'runtime'
    before = snapshot(destination)
    compiler_before = snapshot(directory / 'compiler')
    mutations = {
        'unknown_provider': lambda m: m['string'].update(provider='missing'),
        'omitted_requirements': lambda m: m['submodules']['core.string'].update(requires=[]),
        'omitted_values': lambda m: m['submodules']['core.string'].update(requires=['core.integers']),
        'missing_dependency': lambda m: m['submodules']['core.string'].update(requires=['missing']),
        'cycle': lambda m: (m['submodules']['core.string'].update(requires=['core.output']), m['submodules']['core.output'].update(requires=['core.string'])),
        'invalid_header': lambda m: m['submodules']['core.string'].update(header='../private.hpp'),
        'conflict': lambda m: m['submodules']['core.string'].update(header='scpp/values.hpp'),
        'unavailable_module': lambda m: m['submodules']['core.string'].update(module='missing'),
    }
    for name, mutate in mutations.items():
        metadata = json.loads(json.dumps(original))
        mutate(metadata)
        invalid = work / f'{name}.json'
        invalid.write_text(json.dumps(metadata))
        run(['php', generator, 'emit-runtime', invalid, publication, facts, context, destination], 1)
        assert snapshot(destination) == before, name
        run(['php', generator, 'emit-compiler', invalid, facts, context, directory / 'compiler'], 1)
        assert snapshot(directory / 'compiler') == compiler_before, name
    binding_mutations = {
        'competing_header': lambda m: m['compiler']['runtime_bindings']['echo_bytes'].update(header='scpp/other.hpp'),
        'wrong_storage_group': lambda m: m['compiler']['runtime_bindings']['string_storage'].update(declaration_group='output'),
        'wrong_output_group': lambda m: m['compiler']['runtime_bindings']['echo_int'].update(declaration_group='string'),
        'unknown_group': lambda m: m['compiler']['runtime_bindings']['echo_int'].update(declaration_group='missing'),
        'storage_header_fallback': lambda m: (m['compiler']['runtime_bindings']['string_storage'].pop('declaration_group'),
                                            m['compiler']['runtime_bindings']['string_storage'].update(header='scpp/string.hpp')),
        'output_header_fallback': lambda m: (m['compiler']['runtime_bindings']['echo_bytes'].pop('declaration_group'),
                                           m['compiler']['runtime_bindings']['echo_bytes'].update(header='scpp/output.hpp')),
    }
    for name, mutate in binding_mutations.items():
        metadata = json.loads(json.dumps(original))
        mutate(metadata)
        invalid = work / (name + '.json')
        invalid.write_text(json.dumps(metadata))
        run(['php', generator, 'emit-compiler', invalid, facts, context, directory / 'compiler'], 1)
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
    run(['php', generator, 'emit-compiler', renamed, facts, context, directory / 'compiler', '--check'], 1)
    run(['cmake', '-DGENERATOR=' + str(ROOT / 'build/clang/scpp_api_generator'),
         '-DMETADATA=' + str(renamed), '-DPUBLICATION=' + str(publication), '-DOUTPUT=' + str(destination),
         '-P', ROOT / 'cmake/PublishRuntime.cmake'])
    run(['cmake', '-DPHP=php', '-DGENERATOR=' + str(generator), '-DMETADATA=' + str(renamed),
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
        run(['php', generator, 'emit-compiler', metadata, facts, context, compiler / 'generated'])
    generate(original)
    for flavor in ('cpp', 'php', 'js'):
        app, out, native = (work / flavor / name for name in ('app', 'out', 'native'))
        for directory in (app, out, native):
            directory.mkdir(parents=True, exist_ok=True)
        (app / 'module.json').write_text('{}')
        local = {'cpp': 'string text = "cached";', 'php': '$text string = "cached";',
                 'js': 'let text: string = "cached";'}[flavor]
        source = app / ('main.' + flavor)
        source.write_text(local + ('echo $text;' if flavor == 'php' else 'echo text;') + 'return 8;')
        source_before = source.stat().st_mtime_ns, source.read_bytes()
        def compile_project():
            return json.loads(run(['php', runner, app, facts, out]).stdout)
        # Reset the publication for this flavor before its initial build.
        generate(original)
        assert compile_project()['rebuilt']
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
        for label in ('renamed', 'relocated'):
            value = json.loads(json.dumps(original))
            value['submodules']['core.string']['header'] = 'scpp/cache/text.hpp'
            value['submodules']['core.output']['header'] = 'scpp/cache/write.hpp'
            if label == 'relocated':
                value['output']['provider'] = 'core.string'
            generate(value)
            # New process: PHP classes loaded before regeneration cannot be hot-replaced.
            response = compile_project()
            assert response['rebuilt'], 'Regenerated compiler did not invalidate application cache'
            texts = artifacts(out)
            joined = '\n'.join(texts.values())
            assert '<scpp/string.hpp>' not in joined and '<scpp/output.hpp>' not in joined
            assert '<scpp/cache/text.hpp>' in joined
            assert ('<scpp/cache/write.hpp>' in joined) == (label == 'renamed')
            run(['ninja', '-j', '4'], cwd=native)
            after = [obj.stat().st_mtime_ns for obj in objects]
            assert after != before, 'Existing application objects were not rebuilt'
            assert run([native / 'program'], 8).stdout == 'cached'
            assert not compile_project()['rebuilt'], 'Regenerated application cache not reusable'
            run(['ninja', '-j', '4'], cwd=native)
            assert [obj.stat().st_mtime_ns for obj in objects] == after, 'No-op native rebuild'
            assert (source.stat().st_mtime_ns, source.read_bytes()) == source_before, 'Source changed during metadata proof'
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
