#!/usr/bin/env python3
"""Fresh public-CLI accounting behavior and complete template-free closure, O0 only."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--native-compiler', type=Path, help='Converted native validation request driver')
    options = parser.parse_args()
    out = options.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    source = ROOT / 'examples/accounting_workload'
    inventory = {str(path.relative_to(source)): hashlib.sha256(path.read_bytes()).hexdigest()
                 for path in sorted(source.rglob('*.phs'))}
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        revision = runner.run('revision', ['git', 'rev-parse', 'HEAD']).decode().strip()
        clang = runner.run('clang-version', ['/usr/bin/clang++', '--version']).decode()
        metadata = dict(revision=revision, clang=clang, sources=inventory,
                        optimization='O0', native_debug=False, pch=False,
                        scope='Behavior/closure acceptance; not an isolated timing benchmark',
                        compiler_execution='native' if options.native_compiler else 'PHP',
                        native_compiler_sha256=(hashlib.sha256(options.native_compiler.read_bytes()).hexdigest()
                                               if options.native_compiler else None))
        (out / 'inputs.json').write_text(json.dumps(metadata, indent=2) + '\n')

        def native_request(name, folder, mode, *, expect_error=False, **values):
            folder.mkdir(parents=True, exist_ok=True)
            for key, value in dict(mode=mode, **values).items():
                (folder / (key + '.txt')).write_text(str(value))
            (folder / 'request.txt').write_text('chapter14:' + str(folder))
            output = runner.run(name, [options.native_compiler.resolve()], cwd=folder, timeout=900)
            if output.startswith(b'ERROR\n') != expect_error:
                raise RuntimeError(name + ': unexpected native result: ' + output.decode()[:1000])
            return output

        if options.native_compiler:
            # A failed concrete request must leave a non-ready publication, never an ordinary fallback.
            folder = out / 'strict-recovery'
            source_probe = folder / 'source'
            source_probe.mkdir(parents=True)
            path = source_probe / 'main.phs'
            path.write_text('$f function<int()> = fn(): int => 1; return $f();')
            published = folder / 'published'
            error = native_request('native-unsupported', folder / 'request', 'publish-prelowered',
                expect_error=True, published=published, source=source_probe,
                catalog=ROOT / 'runtime/metadata/modules', module='core')
            assert b'prelower_templates: callable storage requires a concrete callback transport' in error, error
            assert (published / '.scpp-output.status').read_text().startswith('scpp-output-v1:pending:')
            error = native_request('native-refuse-failed', folder / 'request', 'build',
                expect_error=True, published=published, cache=folder / 'cache',
                runtime=ROOT / 'runtime/include', pch='no')
            assert b'Output has no ready revision' in error or b'pending or failed' in error, error
            path.write_text('$items vector<int> = [2,3]; $items[] = 4; $items[0] = 5; '
                            'return $items[0] + $items[1] + $items[2] - 12;')
            native_request('native-repair', folder / 'request', 'publish-prelowered',
                published=published, source=source_probe,
                catalog=ROOT / 'runtime/metadata/modules', module='core')
            assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
            error = native_request('native-reject-pch', folder / 'request', 'build',
                expect_error=True, published=published, cache=folder / 'cache',
                runtime=ROOT / 'runtime/include', pch='yes')
            assert b'prelower_templates: runtime PCH is incompatible' in error, error
            built = json.loads(native_request('native-repair-build', folder / 'request', 'build',
                published=published, cache=folder / 'cache', runtime=ROOT / 'runtime/include', pch='no'))
            runner.run('native-repair-execute', [built['executable']])

        def build(mode):
            folder = out / mode
            folder.mkdir()
            published = folder / 'published'
            recorder = folder / 'clang.py'
            shutil.copyfile(APP / 'tests/fixtures/timed_clang.py', recorder)
            recorder.chmod(0o700)
            command = ['php', APP / 'main.php', '--s2s', source, '--entry', 'main.phs',
                       '--output', published]
            if mode == 'ordinary':
                command.append('--no-prelower-templates')
            runner.run(mode + '-publish', command, timeout=600, cli_reason='Public CLI acceptance')
            if options.native_compiler:
                host = published
                published = folder / 'native-published'
                native_request(mode + '-native-publish', folder / 'request',
                               'publish-prelowered' if mode == 'prelowered' else 'publish',
                               published=published, source=source,
                               catalog=ROOT / 'runtime/metadata/modules',
                               module=json.dumps(['core', 'json', 'strings', 'datetime', 'filesystem']))
                host_manifest = json.loads((host / '.scpp-output.manifest').read_text())
                native_manifest = json.loads((published / '.scpp-output.manifest').read_text())
                assert {a['path'] for a in native_manifest['files']} == {a['path'] for a in host_manifest['files']}, mode
                for artifact in host_manifest['files']:
                    relative = artifact['path']
                    assert (host / relative).read_bytes() == (published / relative).read_bytes(), 'PHP/native artifact differs: ' + relative
                assert native_manifest == host_manifest, 'PHP/native publication manifest differs: ' + mode
            manifest = json.loads((published / '.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('forbidden' if mode == 'prelowered' else 'allowed')
            command = ['php', APP / 'tests/fixtures/native_cache_driver.php', published,
                       folder / 'cache', ROOT / 'runtime/include', recorder, '--no-pch']
            def compile_native(temperature):
                if options.native_compiler:
                    return json.loads(native_request(mode + '-' + temperature, folder / 'request',
                        'build-recorded', published=published, cache=folder / 'cache',
                        runtime=ROOT / 'runtime/include', cxx=recorder, pch='no'))
                return json.loads(runner.run(mode + '-' + temperature, command, timeout=900, cli_reason='Native build'))
            cold = compile_native('cold')
            output = runner.run(mode + '-execute', [cold['executable']], timeout=120)
            assert b'CHECKS passed=998 failed=0\n' in output, output
            assert b'ACCOUNTING_WORKLOAD_PASS\n' in output and b'FAIL ' not in output, output
            warm = compile_native('warm')
            assert warm['compiled'] == warm['linked'] == warm['pch_built'] == 0, warm
            assert runner.run(mode + '-warm-execute', [warm['executable']]) == output
            records = [json.loads(line) for line in recorder.with_suffix('.jsonl').read_text().splitlines()]
            for record in records:
                flags = record['arguments']
                assert not any(flag.startswith('-O') and flag != '-O0' for flag in flags), flags
                assert '-g' not in flags and '-include-pch' not in flags, flags
            result = dict(cold=cold, warm=warm, output=output.decode(),
                          output_sha256=hashlib.sha256(output).hexdigest())
            (folder / 'result.json').write_text(json.dumps(result, indent=2) + '\n')
            return result

        results = run_tasks([Task(mode, lambda mode=mode: build(mode))
                             for mode in ('ordinary', 'prelowered')])
        assert results[0].value['output'] == results[1].value['output'], 'Accounting behavior differs'
        runner.run('closure', [sys.executable, APP / 'tests/prelower_template_closure.py',
                   '--published', out / ('prelowered/native-published' if options.native_compiler else 'prelowered/published'),
                   '--commands', out / 'prelowered/clang.jsonl', '--results', out / 'closure'],
                   timeout=600)
        audit = json.loads((out / 'closure/audit.json').read_text())
        assert len(audit) == 16, 'Accounting application-unit count changed; review full manifest coverage'
        validations = [json.loads(line) for line in (out / 'prelowered/clang.jsonl').read_text().splitlines()]
        assert sum(row['phase'] == 'template_validation' for row in validations) == len(audit)
        (out / 'result.json').write_text(json.dumps(dict(
            **metadata, behavior_identical=True, php_native_artifact_parity=bool(options.native_compiler),
            native_strict_rejection_recovery=bool(options.native_compiler),
            checks_passed=998, checks_failed=0,
            application_units=len(audit), template_declarations=sum(row['template_lines'] for row in audit),
            instantiations=sum(row['instantiations'] for row in audit),
            builds={row.name: row.value for row in results}), indent=2) + '\n')


if __name__ == '__main__':
    main()
