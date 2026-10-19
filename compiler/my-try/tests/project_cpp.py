#!/usr/bin/env python3
"""Cross-file semantic/output isolation and separately compiled program proofs."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler, build_runtime_object
from cpp_artifacts import decode_artifacts, materialize_artifacts, probe_bundle, probe_text


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    programs = output / 'programs'
    programs.mkdir()
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12,
                       journals=[output / 'commands.json']) as runner:
        suites = ['project_cpp', 'reference_forwarding', 'module_sync', 'lifecycle_handoff', 'token_cleanup']
        def suite(name):
            command = ['php', APP / 'tests' / (name + '.php')]
            if name == 'project_cpp':
                command.append(programs)
            runner.run(name, command)
        run_tasks([Task(name, lambda name=name: suite(name)) for name in suites], jobs=12)
        manifest = json.loads((programs / 'executions.json').read_text())
        runtime_object = build_runtime_object(runner, APP.parents[1], output)
        compilers = []
        for pch in (False, True):
            folder = output / ('pch' if pch else 'no-pch')
            folder.mkdir()
            compilers.append((folder, CppFixtureCompiler(runner, APP.parents[1], folder, pch=pch, runtime=True,
                                                        runtime_object=runtime_object)))
        def execute(folder, compiler, fixture):
            path = Path(fixture['path'])
            payload = path.read_bytes()
            # Editable probe transport must preserve every file byte when uninstrumented.
            roundtrip = probe_bundle(probe_text(payload))
            # Editable probes carry identity and source bytes, not publication include metadata.
            original = [{key: artifact[key] for key in ('id', 'kind', 'path', 'text')}
                        for artifact in decode_artifacts(payload)]
            if decode_artifacts(roundtrip) != original:
                raise RuntimeError('Probe transport changed artifacts')
            executable = folder / path.stem
            compiler.compile_artifacts(folder.name + '-' + path.stem, payload, executable)
            runner.run(folder.name + '-' + path.stem + '-execute', [executable], expected=fixture['exit_code'])
        run_tasks([Task(folder.name + '-' + Path(fixture['path']).stem,
                        lambda folder=folder, compiler=compiler, fixture=fixture: execute(folder, compiler, fixture))
                   for folder, compiler in compilers for fixture in manifest], jobs=12)

        # Each generated header stands alone; PCH must not mask a missing dependency.
        header_tasks = []
        for fixture in manifest:
            payload = Path(fixture['path']).read_bytes()
            directory = output / 'headers' / Path(fixture['path']).stem
            materialize_artifacts(payload, directory)
            for index, artifact in enumerate(decode_artifacts(payload)):
                if artifact['kind'] != 'header':
                    continue
                source = directory / ('header-check-' + str(index) + '.cpp')
                source.write_text('#include "' + artifact['path'] + '"\n')
                name = 'header-' + directory.name + '-' + str(index)
                header_tasks.append(Task(name, lambda name=name, source=source, directory=directory:
                    runner.run(name, [*compilers[0][1].flags, '-I', directory, '-fsyntax-only', source])))
        run_tasks(header_tasks, jobs=12)

        # Ask clang for actual transitive dependencies, not only emitted-text assertions.
        directory = output / 'headers' / 'record_dependencies'
        artifacts = decode_artifacts((programs / 'record_dependencies.json').read_bytes())
        box_path = next(a['path'] for a in artifacts if a['id'] == 'type:Box')
        for source_name, expects_layout in [('forward.phs', False), ('copy.phs', True), ('nested.phs', True)]:
            encoded = 'n' + source_name.encode().hex() + '.cpp'
            unit = next(a for a in artifacts if a['path'].endswith('/' + encoded))
            dependencies = runner.run('dependencies-' + source_name,
                [*compilers[0][1].flags, '-I', directory, '-MM', directory / unit['path']])
            if (box_path.encode() in dependencies) != expects_layout:
                raise RuntimeError('Incorrect transitive layout dependency for ' + source_name)

        # New processes and relocated checkout roots cannot enter artifact identities.
        baseline = None
        for index in range(2):
            source = output / ('relocation-' + str(index))
            source.mkdir()
            (source / 'main.phs').write_text('return answer();')
            (source / 'library.phs').write_text('function answer(): int { return 7; }')
            payload = runner.run('cli-relocation-' + str(index), ['php', APP / 'main.php', '--s2s', source,
                                '--entry', 'main.phs'], cli_reason='Tests explicit entry and process restart')
            if baseline is not None and baseline != payload:
                raise RuntimeError('Relocation changed generated artifacts')
            baseline = payload
            rejected = runner.run('cli-no-entry-' + str(index), ['php', APP / 'main.php', '--s2s', source],
                                  expected=1, cli_reason='Tests missing multi-source entry')
            if rejected:
                raise RuntimeError('Missing entry published partial output')
        (output / 'summary.json').write_text(json.dumps(dict(passed=True, php_suites=len(suites),
            generated_program_runs=2 * len(manifest), pch_and_no_pch=True,
            standalone_header_checks=len(header_tasks), transitive_dependency_checks=3,
            native_compiler_rebuilt=False, incremental_native_cache=False, shadow_warnings_enabled=True), indent=2) + '\n')


if __name__ == '__main__':
    main()
