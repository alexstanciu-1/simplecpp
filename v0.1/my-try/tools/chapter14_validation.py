"""Focused CH14 native artifact, publication and build acceptance."""
import hashlib
import json
from pathlib import Path
import shutil
import time
from test_runner import Task, run_tasks
from cpp_fixture import CppFixtureCompiler, build_runtime_object
from cpp_artifacts import decode_artifacts, materialize_artifacts


def validate_chapter14(args, results, logs, started, runner, source, project, executable):
    from native_validate import APP, ROOT, php_literal
    hashes_path = results / 'harness_hashes.json'
    hashes = json.loads(hashes_path.read_text())
    hashes['tools/chapter14_validation.py'] = hashlib.sha256(Path(__file__).read_bytes()).hexdigest()
    hashes_path.write_text(json.dumps(hashes, indent=2) + '\n')
    php_code = ('require ' + php_literal(APP / 'boot.php') + '; require '
                + php_literal(APP / 'tests/cpp_probe.php') + '; require '
                + php_literal(APP / 'tests/s2s_proof.php') + '; require '
                + php_literal(source / 'main.php') + ';')
    fixtures = logs / 'fixtures'
    fixtures.mkdir()
    suites = ['project_cpp', 'cpp_artifacts', 'runtime_catalog', 'runtime_build_contract']

    def host_suite(name):
        folder = fixtures / name
        folder.mkdir()
        runner.run(name + '-host-suite', ['php', APP / 'tests' / (name + '.php'), folder])
    run_tasks([Task(name, lambda name=name: host_suite(name)) for name in suites], jobs=args.jobs)

    def invoke(name, folder, request, native):
        folder.mkdir(parents=True, exist_ok=True)
        (folder / 'request.txt').write_text(request)
        return runner.run(name, [executable] if native else ['php', '-r', php_code], cwd=folder, timeout=300)

    startup = logs / 'startup'
    host = invoke('startup-host', startup, 's2s-proof', False)
    if invoke('startup-native', startup, 's2s-proof', True) != host:
        raise RuntimeError('Native startup proof differs')
    runtime_object = build_runtime_object(runner, ROOT, logs, cxx='clang++-18')
    compilers = []
    for pch in (False, True):
        folder = logs / ('pch' if pch else 'no-pch')
        folder.mkdir()
        compilers.append((folder, CppFixtureCompiler(runner, ROOT, folder, pch=pch,
                          runtime=True, runtime_object=runtime_object, cxx='clang++-18')))
    cases = json.loads((fixtures / 'project_cpp/native_projects.json').read_text())
    header_count = 0

    def artifacts(name, row):
        sources, expected = row
        folder = logs / 'programs' / name
        folder.mkdir(parents=True)
        for relative, text in sources.items():
            (folder / relative).write_text(text)
        request = 's2s:' + str(folder)
        host = invoke(name + '-host', folder, request, False)
        native = invoke(name + '-native', folder, request, True)
        if native != host or native.startswith(b'ERROR\n'):
            raise RuntimeError(name + ': artifact parity failed')
        (folder / 'native.json').write_bytes(native)
        for output, compiler in compilers:
            program = output / name
            compiler.compile_artifacts(output.name + '-' + name, native, program)
            runner.run(output.name + '-' + name + '-execute', [program], expected=expected)
        headers = folder / 'headers'
        materialize_artifacts(native, headers)
        count = 0
        for index, artifact in enumerate(decode_artifacts(native)):
            if artifact['kind'] == 'header':
                probe = headers / ('probe-' + str(index) + '.cpp')
                probe.write_text('#include "' + artifact['path'] + '"\n')
                runner.run(name + '-header-' + str(index),
                           [*compilers[0][1].flags, '-I', headers, '-fsyntax-only', probe])
                count += 1
        return count
    outcomes = run_tasks([Task(name, lambda name=name, row=row: artifacts(name, row))
                          for name, row in cases.items()], jobs=args.jobs)
    header_count = sum(row.value for row in outcomes)

    def disk_request(name, folder, mode, native=True, **values):
        folder.mkdir(parents=True, exist_ok=True)
        for key, value in dict(mode=mode, **values).items():
            (folder / (key + '.txt')).write_text(str(value))
        return invoke(name, folder, 'chapter14:' + str(folder), native)

    def disk_proof(optional):
        label = 'module' if optional else 'incremental'
        folder = logs / label
        folder.mkdir()
        src = folder / 'source'
        src.mkdir()
        published, host_published, cache = folder / 'published', folder / 'host-published', folder / 'cache'
        runtime, catalogs, module = ROOT / 'runtime/include', ROOT / 'runtime/metadata/modules', 'core'
        schema = None
        if optional:
            runtime, catalogs, module = folder / 'runtime', folder / 'catalogs', 'build_proof'
            shutil.copytree(ROOT / 'runtime/include', runtime)
            catalogs.mkdir()
            shutil.copyfile(ROOT / 'runtime/metadata/modules/core.json', catalogs / 'core.json')
            provider = runtime / 'build_proof'
            provider.mkdir()
            (provider / 'provider.hpp').write_text('#pragma once\n#include "scpp/int_t.hpp"\nscpp::int_t<> optional_answer();\n')
            (provider / 'provider.cpp').write_text('#include "provider.hpp"\nscpp::int_t<> optional_answer() { return scpp::int_t<>{7 + PROOF_BONUS}; }\n')
            schema = dict(version=1, module=module, dependencies=['core'],
                functions=[dict(id='proof.answer', name='optional_answer', parameters=[], returns='int')],
                cpp=[dict(id='proof.answer', symbol='optional_answer', header='build_proof/provider.hpp')],
                cpp_build=dict(sources=['core/runtime.cpp', 'build_proof/provider.cpp'], libraries=['m'], definitions=['PROOF_BONUS=1']))
            (catalogs / 'build_proof.json').write_text(json.dumps(schema))
            sources, expected = {'main.phs': 'return optional_answer();'}, 8
        else:
            sources, expected = cases['legal_shadowing']
        for relative, text in sources.items():
            (src / relative).write_text(text)
        observations = []

        def publish(name, rejection=False):
            outputs = []
            for native, destination in [(False, host_published), (True, published)]:
                output = disk_request(label + '-' + name + ('-native-publish' if native else '-host-publish'),
                    folder / ('native-request' if native else 'host-request'), 'publish', native,
                    published=destination, source=src, catalog=catalogs, module=module)
                if output.startswith(b'ERROR\n') != rejection:
                    raise RuntimeError(label + '-' + name + ': publication result mismatch: ' + output.decode())
                outputs.append(output)
            if outputs[0] != outputs[1]:
                raise RuntimeError(label + '-' + name + ': publication parity mismatch')
            if not rejection:
                if (published / '.scpp-output.manifest').read_bytes() != (host_published / '.scpp-output.manifest').read_bytes():
                    raise RuntimeError('Native publication manifest differs')

        def build(name, counts, code, pch=True, rejection=False):
            output = disk_request(label + '-' + name + '-build', folder / 'build-request', 'build',
                published=published, cache=cache if pch else folder / 'no-pch-cache', runtime=runtime, pch='yes' if pch else 'no')
            if rejection:
                if not output.startswith(b'ERROR\n'):
                    raise RuntimeError('Native build accepted failed publication')
                return
            if output.startswith(b'ERROR\n'):
                raise RuntimeError(output.decode())
            result = json.loads(output)
            if tuple(result[k] for k in ('compiled', 'pch_built', 'linked')) != counts:
                raise RuntimeError(label + '-' + name + ': unexpected native cache actions: ' + output.decode())
            if not optional and name == 'cold' and '-Wshadow' not in result['diagnostics']:
                raise RuntimeError('Native build lost successful shadow diagnostics')
            runner.run(label + '-' + name + '-execute', [result['executable']], expected=code)
            observations.append(dict(name=name, **result))
            (folder / 'observations.json').write_text(json.dumps(observations, indent=2) + '\n')

        publish('cold')
        if optional:
            # Prove native build consumes the snapshot, not the source catalog.
            (catalogs / 'build_proof.json').rename(catalogs / 'saved.json')
        build('cold', (3, 1, 1), expected)
        build('warm', (0, 0, 0), expected)
        if optional:
            (catalogs / 'saved.json').rename(catalogs / 'build_proof.json')
        before = {p: (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns)
                  for p in published.rglob('*') if p.is_file() and p.name != '.scpp-output.status'}
        publish('noop')
        if any((p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns) != state for p, state in before.items()):
            raise RuntimeError('Native no-op publication rewrote files')
        (src / 'main.phs').write_text('return optional_answer() + 1;' if optional else 'return check() + 1;')
        publish('body')
        build('body', (1, 0, 1), expected + 1)
        if optional:
            provider_file = runtime / 'build_proof/provider.cpp'
            provider_file.write_text(provider_file.read_text().replace('7 +', '9 +'))
            build('provider', (1, 0, 1), 11)
            schema['cpp_build']['definitions'] = ['PROOF_BONUS=2']
            (catalogs / 'build_proof.json').write_text(json.dumps(schema))
            publish('definitions')
            build('definitions', (3, 1, 1), 12)
            schema['cpp_build']['libraries'] = []
            (catalogs / 'build_proof.json').write_text(json.dumps(schema))
            publish('libraries')
            build('libraries', (0, 0, 1), 12)
            build('no-pch', (3, 0, 1), 12, pch=False)
        else:
            (src / 'main.phs').write_text('throw 3;')
            publish('invalid', rejection=True)
            if not (published / '.scpp-output.status').read_text().startswith('scpp-output-v1:pending:'):
                raise RuntimeError('Failed native publication stayed ready')
            build('invalid', None, None, rejection=True)
            (src / 'main.phs').write_text(sources['main.phs'])
            publish('repair')
            build('repair', (0, 0, 0), expected)
            build('no-pch', (3, 0, 1), expected, pch=False)
        return len(observations)

    disk_outcomes = run_tasks([Task('native-incremental', lambda: disk_proof(False)),
                               Task('native-runtime-module', lambda: disk_proof(True))], jobs=args.jobs)
    runner.run('incremental-compiler-build', ['php', args.target_checkout / 'bin/scpp.php', 'build', '--no-stan'],
               cwd=project, timeout=600, cli_reason='CLI build tool with process exit status')
    summary = dict(passed=True, scope='Chapter 14 bounded native gate', candidate_revision=args.candidate_revision,
                   phs_conversion=True, native_compiler_rebuilt=True, stan='skipped', llvm='skipped',
                   compiler_optimization=args.compiler_optimization, generated_optimization='O0',
                   source_php_files=len(json.loads((results / 'source_hashes.json').read_text())),
                   artifact_parity_projects=len(cases), generated_program_runs=len(cases) * 2,
                   standalone_headers=header_count, host_suites=suites, pch_and_no_pch=True,
                   native_publication_parity=True, native_failed_publication_recovery=True,
                   native_incremental_build_runs=disk_outcomes[0].value,
                   native_module_build_runs=disk_outcomes[1].value,
                   executable_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(),
                   wall_seconds=round(time.monotonic() - started, 3))
    (results / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
