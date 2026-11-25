"""Bounded CH13 fixture parity/execution after the shared native candidate build."""
import hashlib
import json
from pathlib import Path
import time
from test_runner import Task, run_tasks
from cpp_fixture import CppFixtureCompiler
from cpp_artifacts import probe_text


def validate_chapter13(args, results, logs, started, runner, source, project, executable):
    from native_validate import APP, ROOT, php_literal
    if "s2s-edit:" not in (source / "main.php").read_text():
        raise RuntimeError("Native driver snapshot predates edit proofs; resume to rebuild the updated driver")
    php_code = ('require ' + php_literal(APP / 'boot.php') + '; require '
                + php_literal(APP / 'tests/cpp_probe.php') + '; require '
                + php_literal(APP / 'tests/s2s_proof.php') + '; require '
                + php_literal(source / 'main.php') + ';')
    harness_path = results / 'harness_hashes.json'
    harness_hashes = json.loads(harness_path.read_text())
    harness_hashes['tools/chapter13_validation.py'] = hashlib.sha256(Path(__file__).read_bytes()).hexdigest()
    harness_path.write_text(json.dumps(harness_hashes, indent=2) + '\n')
    fixtures = logs / 'fixtures'
    fixtures.mkdir()
    suites = ['error_values', 'exceptions', 'finally']

    def host_suite(suite):
        folder = fixtures / suite
        folder.mkdir()
        runner.run(suite + '-host-suite', ['php', APP / 'tests' / (suite + '.php'), folder])
    run_tasks([Task(s, lambda s=s: host_suite(s)) for s in suites], jobs=args.jobs)
    def diagnostic(index, text):
        folder = logs / ('diagnostic-' + str(index))
        folder.mkdir()
        (folder / 'main.phs').write_text(text)
        name = 'diagnostic-' + str(index)
        stdout = runner.run(name, ['php', APP / 'main.php', '--s2s', folder], expected=1,
                            cli_reason='Tests the production CLI failure boundary')
        if stdout or 'S2S generation failed:' not in (logs / (name + '.stderr')).read_text():
            raise RuntimeError(name + ': failed compilation leaked output or lost its diagnostic')
    diagnostics = ['error::make(3);', 'throw 3;', 'try {} finally {} finally {}']
    run_tasks([Task('diagnostic-' + str(i), lambda i=i, text=text: diagnostic(i, text))
               for i, text in enumerate(diagnostics)], jobs=args.jobs)
    compiler = CppFixtureCompiler(runner, ROOT, logs, pch=False,
                                  return_type_errors=True, runtime=True, cxx='clang++-18')
    valid, rejected = [], []
    for suite in suites:
        manifest = json.loads((fixtures / suite / 'native_cases.json').read_text())
        executions = json.loads((fixtures / suite / 'executions.json').read_text())
        for execution in executions:
            name = Path(execution['path']).stem
            valid.append((suite + '-' + name, manifest['valid'][name], execution))
        rejected += [(suite + '-reject-' + str(i), text + ' return 0;')
                     for i, text in enumerate(manifest['rejected'])]

    def compare(name, text, rejection=False):
        folder = logs / 'programs' / name
        folder.mkdir(parents=True)
        (folder / 'main.phs').write_text(text)
        (folder / 'request.txt').write_text('s2s:' + str(folder))
        host = runner.run(name + '-host', ['php', '-r', php_code], cwd=folder)
        native = runner.run(name + '-native', [executable], cwd=folder)
        if native != host or native.startswith(b'ERROR\n') != rejection:
            raise RuntimeError(name + ': PHP/native result or rejection mismatch')
        if rejection:
            return
        (folder / 'native.json').write_bytes(native)
        return folder, probe_text(native)

    def execute(name, case, execution):
        folder, native = compare(name, case['source'])
        # Test-owned instrumentation may inspect internal identity/state. Only use it
        # after proving the complete uninstrumented native output is byte-identical.
        if native != case['raw']:
            raise RuntimeError(name + ': native output differs from the test-owned base')
        generated = folder / 'program.cpp'
        generated.write_bytes(Path(execution['path']).read_bytes())
        program = folder / 'program'
        compiler.compile(name + '-compile', generated, program)
        stdout = runner.run(name + '-execute', [program], expected=execution['exit_code'])
        if 'stdout' in execution and stdout != execution['stdout'].encode():
            raise RuntimeError(name + ': stdout mismatch')
        if 'stderr_contains' in execution:
            stderr = (logs / (name + '-execute.stderr')).read_text()
            if execution['stderr_contains'] not in stderr:
                raise RuntimeError(name + ': stderr mismatch')

    run_tasks([Task(name, lambda name=name, case=case, execution=execution: execute(name, case, execution))
               for name, case, execution in valid] +
              [Task(name, lambda name=name, text=text: compare(name, text, True))
               for name, text in rejected], jobs=args.jobs)
    edits = [
        ('error', '$e = error::make("old"); $m = $e->get_message(); return $m === "old" ? 0 : 1;',
         '$e = error::make(3);', 'return 7;'),
        ('throw', 'try { throw Exception::make("x"); } catch (Exception $e) { return 0; }',
         'try { throw 3; } catch (Exception $e) {}', 'return 7;'),
        ('finally', 'function f(): int { try { return 7; } finally {} } return f() === 7 ? 0 : 1;',
         'try {} finally { return 1; }', 'try {} finally {} return 7;'),
        ('syntax', 'try {} finally {} return 0;', 'try {} finally {} finally {}', 'return 7;'),
    ]

    def edited(name, valid_text, invalid_text, changed_text):
        name = 'edit-' + name
        folder = logs / name
        folder.mkdir()
        for stem, text in [('valid', valid_text), ('invalid', invalid_text), ('changed', changed_text)]:
            (folder / (stem + '.txt')).write_text(text)
        (folder / 'request.txt').write_text('s2s-edit:' + str(folder))
        host = runner.run(name + '-host', ['php', '-r', php_code], cwd=folder)
        native = runner.run(name + '-native', [executable], cwd=folder)
        if native != host or native.startswith(b'ERROR\n'):
            raise RuntimeError(name + ': incremental parity failed')
        generated = folder / 'repaired.cpp'
        generated.write_text(probe_text(native))
        compiler.compile(name + '-compile', generated, folder / 'program')
        runner.run(name + '-execute', [folder / 'program'])
    run_tasks([Task('edit-' + row[0], lambda row=row: edited(*row)) for row in edits], jobs=args.jobs)
    runner.run('incremental-build', ['php', args.target_checkout / 'bin/scpp.php', 'build', '--no-stan'],
               cwd=project, timeout=600, cli_reason='CLI build tool with process exit status')
    summary = dict(passed=True, scope='Chapter 13 bounded native closeout',
                   candidate_revision=args.candidate_revision, jobs=args.jobs,
                   native_compiler_rebuilt=True, phs_conversion=True, stan='skipped', llvm='skipped',
                   source_php_files=len(json.loads((results / 'source_hashes.json').read_text())),
                   artifact_byte_parity=True,
                   generated_programs=len(valid), rejection_recovery_cases=len(rejected),
                   host_suites=suites, incremental_repair_cases=len(edits), cli_failure_cases=len(diagnostics), cpp_pch=False,
                   executable_sha256=hashlib.sha256(executable.read_bytes()).hexdigest(),
                   wall_seconds=round(time.monotonic() - started, 3))
    (results / 'summary.json').write_text(json.dumps(summary, indent=2) + '\n')
