#!/usr/bin/env python3
"""Matched fresh-process compiler/publication and warm-builder timings."""
import argparse
import hashlib
import json
from pathlib import Path
import statistics
import sys
import time

ROOT = Path(__file__).resolve().parents[4]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--native-workspace', type=Path, required=True)
parser.add_argument('--acceptance', type=Path, required=True)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
workspace, acceptance = args.native_workspace.resolve(), args.acceptance.resolve()
binary = workspace / 'phpp/.prism/build/main'
accepted = json.loads((acceptance / 'result.json').read_text())
assert hashlib.sha256(binary.read_bytes()).hexdigest() == accepted['native_compiler_sha256']
for name, digest in accepted['sources'].items():
    assert hashlib.sha256((ROOT / 'examples/accounting_workload' / name).read_bytes()).hexdigest() == digest
report = dict(status='running', rounds=5, compiler_optimization='O2', generated_optimization='O0',
              native_compiler_sha256=accepted['native_compiler_sha256'], samples=[],
              scope='Fresh process incl startup/catalog/frontend/lowering/publication/encoding; prelowered also repeats preparation/emission. Warm build is native-cache reuse, not incremental source editing.')
with CommandRunner(out / 'logs', ROOT, jobs=12, php_executor='cli') as runner:
    report['revision'] = runner.run('revision', ['git', 'rev-parse', 'HEAD']).decode().strip()
    report['cpu'] = runner.run('cpu', ['lscpu']).decode()
    report['system'] = runner.run('system', ['uname', '-a']).decode()
    report['php'] = runner.run('php', ['php', '--version'], cli_reason='Toolchain identity').decode()
    signatures = {}
    for trial in range(-1, 5):
        cases = [(phase, mode, engine) for phase in ('publication', 'warm-build')
                 for mode in ('ordinary', 'prelowered') for engine in ('php', 'native')]
        if trial % 2: cases.reverse()
        for phase, mode, engine in cases:
            label = f'{phase}-{mode}-{engine}-r{trial}'
            request = out / label
            request.mkdir()
            folder = acceptance / mode
            values = dict(published=request / 'published',
                mode='publish-prelowered' if mode == 'prelowered' else 'publish',
                source=ROOT / 'examples/accounting_workload', catalog=ROOT / 'runtime/metadata/modules',
                module=json.dumps(['core', 'json', 'strings', 'datetime', 'filesystem']))
            if phase == 'warm-build':
                values.update(mode='build-recorded', published=folder / 'native-published', cache=folder / 'cache',
                              runtime=ROOT / 'runtime/include', cxx=folder / 'clang.py', pch='no')
            for key, value in values.items(): (request / (key + '.txt')).write_text(str(value))
            (request / 'request.txt').write_text('chapter14:' + str(request))
            code = ("require " + repr(str(ROOT / 'compiler/my-try/boot.php')) + "; require "
                    + repr(str(ROOT / 'compiler/my-try/tests/s2s_proof.php'))
                    + "; echo \\scpp\\compiler\\S2S_Proof::chapter14(" + repr(str(request)) + ");")
            command = [binary] if engine == 'native' else ['php', '-r', code]
            def run():
                start = time.monotonic()
                data = runner.run(label, command, cwd=request, timeout=180,
                                  cli_reason='Matched fresh-process benchmark')
                return time.monotonic() - start, data
            seconds, data = run_tasks([Task(label, run)], report=False)[0].value
            assert not data.startswith(b'ERROR\n'), data[:1000]
            result = json.loads(data)
            if phase == 'publication':
                digest = hashlib.sha256(data).hexdigest()
                signatures.setdefault(mode, digest)
                assert signatures[mode] == digest, 'PHP/native/repeated artifact output differs'
                actual = json.loads((request / 'published/.scpp-output.manifest').read_text())
                expected = json.loads((folder / 'native-published/.scpp-output.manifest').read_text())
                assert actual == expected
            else:
                assert result['compiled'] == result['linked'] == result['pch_built'] == 0, result
            if trial >= 0:
                report['samples'].append(dict(phase=phase, mode=mode, engine=engine, trial=trial, seconds=seconds))
            print(label, round(seconds, 3), flush=True)
            (out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
    for mode in ('ordinary', 'prelowered'):
        assert runner.run(mode + '-execute', [accepted['builds'][mode]['warm']['executable']]).decode() == accepted['builds'][mode]['output']
report['status'] = 'passed'
report['statistics'] = []
for phase, mode, engine in [(p, m, e) for p in ('publication', 'warm-build')
                           for m in ('ordinary', 'prelowered') for e in ('php', 'native')]:
    values = [s['seconds'] for s in report['samples'] if (s['phase'], s['mode'], s['engine']) == (phase, mode, engine)]
    report['statistics'].append(dict(phase=phase, mode=mode, engine=engine,
        median=statistics.median(values), minimum=min(values), maximum=max(values)))
(out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
