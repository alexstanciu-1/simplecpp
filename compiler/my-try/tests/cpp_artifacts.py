#!/usr/bin/env python3
"""Focused artifact transport and separate-unit native build proof; not a compiler rebuild."""
import argparse
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_artifacts import decode_artifacts, materialize_artifacts, single_unit_text, probe_text, probe_bundle
from cpp_fixture import CppFixtureCompiler
from native_validate import php_literal


def rejection_checks(output):
    valid = {'id': 'entry', 'kind': 'translation_unit', 'path': 'main.cpp', 'text': 'int main() {}'}
    cases = [[], [valid, valid], [dict(valid, kind='unknown')], [dict(valid, text=3)],
             [dict(valid, kind='header', path='only.hpp')]]
    for path in ('../escape.cpp', '/absolute.cpp', 'a/../b.cpp', 'a//b.cpp',
                 'a\\b.cpp', 'C:/b.cpp', 'a/./b.cpp', 'wrong.hpp'):
        cases.append([dict(valid, path=path)])
    cases.append([dict(valid, path='parent.cpp'), dict(valid, id='child', path='parent.cpp/child.cpp')])
    for index, artifacts in enumerate(cases):
        destination = output / ('rejected-' + str(index))
        try:
            materialize_artifacts(json.dumps({'format': 'scpp-cpp-artifacts-v1', 'artifacts': artifacts}), destination)
        except ValueError:
            if destination.exists():
                raise RuntimeError('Invalid bundle caused partial publication')
        else:
            raise RuntimeError('Invalid artifact bundle accepted')
    for payload in ('[]', '{}', '{"format":"future","artifacts":[]}'):
        try:
            decode_artifacts(payload)
        except ValueError:
            pass
        else:
            raise RuntimeError('Unknown transport accepted')


def entry_boundary_checks():
    """Instrumentation may bypass the outer boundary, never a source handler."""
    entry = ('int main(int argc, char** argv)\n{\n'
             '// SCPP-ENTRY-TRY\n\ttry {\n'
             'try { throw 1; } catch (int) {}\n'
             '// SCPP-ENTRY-CATCH\n} catch (...) { return 1; }\n'
             '// SCPP-ENTRY-END\n}\n')
    for partitioned in (False, True):
        artifacts = [{'id': 'entry', 'kind': 'translation_unit', 'path': 'entry.cpp', 'text': entry}]
        if partitioned:
            artifacts.insert(0, {'id': 'header', 'kind': 'header', 'path': 'empty.hpp', 'text': '#pragma once\n'})
        text = probe_text(json.dumps({'format': 'scpp-cpp-artifacts-v1', 'artifacts': artifacts}))
        production = probe_bundle(text, instrumented=True)
        if production is not None and '// SCPP-ENTRY-CATCH' not in production:
            raise RuntimeError('Ordinary entry lost its production handler')
        observers = [
            text.replace('int main(int argc, char** argv)', 'int main(int argc, char** argv) try'),
            text.replace('int main(int argc, char** argv)',
                         '// SCPP-PROBE-OBSERVE-ENTRY\nint observed_entry(int argc, char** argv)')
            + '\nint main() { try { return observed_entry(0, nullptr); } catch (...) { return 91; } }\n',
        ]
        for instrumented in observers:
            if partitioned:
                unchanged = probe_bundle(instrumented)
                if '// SCPP-ENTRY-CATCH' not in unchanged:
                    raise RuntimeError('Uninstrumented transport removed the production handler')
            elif probe_bundle(instrumented) is not None:
                raise RuntimeError('Uninstrumented single source changed')
            recovered = decode_artifacts(probe_bundle(instrumented, instrumented=True))[-1]['text']
            if '// SCPP-ENTRY-CATCH' in recovered or 'catch (int)' not in recovered:
                raise RuntimeError('Exception observer failed to isolate the outer boundary')
            if '// SCPP-PROBE-OBSERVE-ENTRY' in recovered:
                raise RuntimeError('Exception observer marker was not consumed')
            if 'int observed_entry' in recovered and 'catch (...) { return 91; }' not in recovered:
                raise RuntimeError('Renamed-entry observer lost its own handler')



def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    fixture = output / 'multi.json'
    source = output / 'source'
    source.mkdir()
    (source / 'main.phs').write_text('function answer(): int { return 7; } return answer();')
    with CommandRunner(output / 'logs', ROOT.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        suites = ['incremental_cpp', 'combined_sync', 'constructed_type', 'reference_model']
        tasks = [Task(name, lambda name=name: runner.run(name, ['php', ROOT / 'tests' / (name + '.php')]))
                 for name in suites]
        tasks.append(Task('artifact-model', lambda: runner.run('artifact-model',
                          ['php', ROOT / 'tests/cpp_artifacts.php', fixture])))
        run_tasks(tasks, jobs=12)
        rejection_checks(output)
        entry_boundary_checks()
        payload = runner.run('artifact-cli', ['php', ROOT / 'main.php', '--s2s', source, '--no-prelower-templates'],
                             cli_reason='Tests the artifact CLI delivery contract')
        if 'function_answer' not in probe_text(payload):
            raise RuntimeError('CLI failed to deliver generated program')
        recovery = output / 'recovery'
        recovery.mkdir()
        (recovery / 'main.phs').write_text('return 0;')
        request = output / 'request.txt'
        request.write_text('s2s:' + str(source))
        driver = (ROOT / 'tests/native_driver.php.in').read_text()
        driver = (driver.replace('__RUN_LLVM_PROOFS__', 'false')
                  .replace('__REQUEST_FILE__', php_literal(request))
                  .replace('__RECOVERY_DIR__', php_literal(recovery))
                  .replace('__RUNTIME_CATALOG_DIR__', php_literal(ROOT.parents[1] / 'runtime/metadata/modules'))
                  .replace('__PIPELINE_DIR__', php_literal(source)))
        driver_path = output / 'driver.php'
        driver_path.write_text(driver)
        host_path = output / 'host.php'
        host_path.write_text('<?php\nrequire ' + php_literal(ROOT / 'boot.php')
                             + ';\nrequire ' + php_literal(ROOT / 'tests/cpp_probe.php')
                             + ';\nrequire ' + php_literal(ROOT / 'tests/s2s_proof.php')
                             + ';\nrequire ' + php_literal(driver_path) + ';\n')
        driver_output = runner.run('artifact-driver-host', ['php', host_path])
        if driver_output != payload:
            raise RuntimeError('CLI and native-driver host transports differ')
        multi = fixture.read_bytes()
        try:
            single_unit_text(multi)
        except ValueError:
            pass
        else:
            raise RuntimeError('Source probe discarded extra artifacts')
        # Exercise both include completeness and the existing run-local runtime PCH.
        compilers = []
        for pch in (False, True):
            directory = output / ('pch' if pch else 'no-pch')
            directory.mkdir()
            compilers.append((directory, CppFixtureCompiler(runner, ROOT.parents[1], directory, pch=pch)))

        def execute(directory, compiler, name, bundle, expected):
            executable = directory / name
            label = directory.name + '-' + name
            compiler.compile_artifacts(label, bundle, executable)
            runner.run(label + '-execute', [executable], expected=expected)

        run_tasks([Task(directory.name + '-' + name,
                        lambda directory=directory, compiler=compiler, name=name, bundle=bundle, expected=expected:
                        execute(directory, compiler, name, bundle, expected))
                   for directory, compiler in compilers
                   for name, bundle, expected in [('single', payload, 7), ('multi', multi, 42)]], jobs=12)
        # Native assertions can introduce calls absent from the original entry.
        # Both insertion and appended-main probes need declarations, not merged units.
        probe_payload = json.dumps({'format': 'scpp-cpp-artifacts-v1', 'artifacts': [
            {'id': 'answer', 'kind': 'header', 'path': 'answer.hpp',
             'text': '#pragma once\nint answer();\n'},
            {'id': 'library', 'kind': 'translation_unit', 'path': 'library.cpp',
             'text': '#include "answer.hpp"\nint answer() { return 7; }\n'},
            {'id': 'entry', 'kind': 'translation_unit', 'path': 'entry.cpp',
             'text': 'int main() { return 0; }\n'}]})
        editable = probe_text(probe_payload)
        if decode_artifacts(probe_bundle(editable)) != decode_artifacts(probe_payload):
            raise RuntimeError('Uninstrumented transport changed production dependencies')
        probes = {
            'inserted': editable.replace('return 0;', 'return answer();'),
            'appended': editable.replace('int main()', 'int unused_entry()')
                        + 'int main() { return answer(); }\n',
        }

        def execute_probe(directory, compiler, name, text):
            source = directory / (name + '.cpp')
            source.write_text(text)
            executable = directory / name
            label = directory.name + '-' + name
            compiler.compile(label, source, executable)
            runner.run(label + '-execute', [executable], expected=7)

        run_tasks([Task(directory.name + '-' + name,
                       lambda directory=directory, compiler=compiler, name=name, text=text:
                       execute_probe(directory, compiler, name, text))
                   for directory, compiler in compilers for name, text in probes.items()], jobs=12)
        # Malformed source must never publish a partial successful bundle on stdout.
        (source / 'main.phs').write_text('function broken(')
        rejected = runner.run('artifact-cli-rejected', ['php', ROOT / 'main.php', '--s2s', source, '--no-prelower-templates'], expected=1,
                              cli_reason='Tests CLI failure publication')
        if rejected:
            raise RuntimeError('Failed compilation published an artifact bundle')
        driver_rejected = runner.run('artifact-driver-rejected', ['php', host_path])
        if not driver_rejected.startswith(b'ERROR\n'):
            raise RuntimeError('Native-driver rejection/recovery protocol changed')
        (output / 'summary.json').write_text(json.dumps(dict(
            passed=True, php_suites=len(suites) + 1, generated_program_runs=8,
            partitioned_fixture='backend transport fixture, not multi-source lowering',
            native_compiler_rebuilt=False, persistent_pch=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
