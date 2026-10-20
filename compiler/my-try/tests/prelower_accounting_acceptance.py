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
                        scope='Behavior/closure acceptance; not an isolated timing benchmark')
        (out / 'inputs.json').write_text(json.dumps(metadata, indent=2) + '\n')

        def build(mode):
            folder = out / mode
            folder.mkdir()
            published = folder / 'published'
            recorder = folder / 'clang.py'
            shutil.copyfile(APP / 'tests/fixtures/timed_clang.py', recorder)
            recorder.chmod(0o700)
            command = ['php', APP / 'main.php', '--s2s', source, '--entry', 'main.phs',
                       '--output', published]
            if mode == 'prelowered':
                command.append('--prelower-templates')
            runner.run(mode + '-publish', command, timeout=600, cli_reason='Public CLI acceptance')
            manifest = json.loads((published / '.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('forbidden' if mode == 'prelowered' else 'allowed')
            command = ['php', APP / 'tests/fixtures/native_cache_driver.php', published,
                       folder / 'cache', ROOT / 'runtime/include', recorder, '--no-pch']
            cold = json.loads(runner.run(mode + '-cold', command, timeout=900, cli_reason='Native build'))
            output = runner.run(mode + '-execute', [cold['executable']], timeout=120)
            assert b'CHECKS passed=998 failed=0\n' in output, output
            assert b'ACCOUNTING_WORKLOAD_PASS\n' in output and b'FAIL ' not in output, output
            warm = json.loads(runner.run(mode + '-warm', command, timeout=600, cli_reason='Native build'))
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
                   '--published', out / 'prelowered/published',
                   '--commands', out / 'prelowered/clang.jsonl', '--results', out / 'closure'],
                   timeout=600)
        audit = json.loads((out / 'closure/audit.json').read_text())
        assert len(audit) == 16, 'Accounting application-unit count changed; review full manifest coverage'
        validations = [json.loads(line) for line in (out / 'prelowered/clang.jsonl').read_text().splitlines()]
        assert sum(row['phase'] == 'template_validation' for row in validations) == len(audit)
        (out / 'result.json').write_text(json.dumps(dict(
            **metadata, behavior_identical=True, checks_passed=998, checks_failed=0,
            application_units=len(audit), template_declarations=sum(row['template_lines'] for row in audit),
            instantiations=sum(row['instantiations'] for row in audit),
            builds={row.name: row.value for row in results}), indent=2) + '\n')


if __name__ == '__main__':
    main()
