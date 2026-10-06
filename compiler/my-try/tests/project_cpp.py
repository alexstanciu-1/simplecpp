#!/usr/bin/env python3
"""Cross-file semantic/output isolation and separately compiled program proofs."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler
from cpp_artifacts import decode_artifacts, probe_bundle, probe_text


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
        compilers = []
        for pch in (False, True):
            folder = output / ('pch' if pch else 'no-pch')
            folder.mkdir()
            compilers.append((folder, CppFixtureCompiler(runner, APP.parents[1], folder, pch=pch)))
        def execute(folder, compiler, fixture):
            path = Path(fixture['path'])
            payload = path.read_bytes()
            # Editable probe transport must preserve every file byte when uninstrumented.
            roundtrip = probe_bundle(probe_text(payload))
            if decode_artifacts(roundtrip) != decode_artifacts(payload):
                raise RuntimeError('Probe transport changed artifacts')
            executable = folder / path.stem
            compiler.compile_artifacts(folder.name + '-' + path.stem, payload, executable)
            runner.run(folder.name + '-' + path.stem + '-execute', [executable], expected=fixture['exit_code'])
        run_tasks([Task(folder.name + '-' + Path(fixture['path']).stem,
                        lambda folder=folder, compiler=compiler, fixture=fixture: execute(folder, compiler, fixture))
                   for folder, compiler in compilers for fixture in manifest], jobs=12)

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
            native_compiler_rebuilt=False, incremental_native_cache=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
