#!/usr/bin/env python3
"""Adapt the retained O0 unit benchmark to native-published accounting; PCH baseline vs concrete."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import statistics
import sys
import time

ROOT = Path(__file__).resolve().parents[4]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--acceptance', type=Path, required=True)
parser.add_argument('--results', type=Path, required=True)
parser.add_argument('--pch-record', type=Path, help='Reuse an existing successful ordinary PCH capture')
args = parser.parse_args()
source = args.acceptance.resolve()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
accepted = json.loads((source / 'result.json').read_text())
assert accepted['behavior_identical'] and accepted['checks_failed'] == 0
assert accepted['template_declarations'] == accepted['instantiations'] == 0
for name, digest in accepted['sources'].items():
    assert hashlib.sha256((ROOT / 'examples/accounting_workload' / name).read_bytes()).hexdigest() == digest
expected_output = accepted['builds']['ordinary']['output'].encode()
report = dict(status='running', optimization='O0', native_debug=False, rounds=3,
              source_acceptance_revision=accepted['revision'], sources=accepted['sources'],
              scope='Application object recompilation; support/PCH/layout/publication/dependency validation excluded',
              samples=[])
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    report['revision'] = runner.run('revision', ['git', 'rev-parse', 'HEAD']).decode().strip()
    report['clang'] = runner.run('clang', ['/usr/bin/clang++', '--version']).decode()
    report['cpu'] = runner.run('cpu', ['lscpu']).decode()
    report['system'] = runner.run('system', ['uname', '-a']).decode()
    # Capture the normal reusable-PCH baseline before any timed batches.
    if args.pch_record:
        pch_record = args.pch_record.resolve()
    else:
        recorder = out / 'clang.py'
        shutil.copyfile(ROOT / 'compiler/my-try/tests/fixtures/timed_clang.py', recorder)
        recorder.chmod(0o700)
        runner.run('pch-capture', ['php', ROOT / 'compiler/my-try/tests/fixtures/native_cache_driver.php',
                   source / 'ordinary/native-published', out / 'pch-cache', ROOT / 'runtime/include', recorder],
                   timeout=900, cli_reason='Capture ordinary PCH compilation commands')
        pch_record = recorder.with_suffix('.jsonl')
    inputs = {'ordinary-pch': (pch_record, source / 'ordinary/native-published'),
              'prelowered': (source / 'prelowered/clang.jsonl', source / 'prelowered/native-published')}
    plans = {}
    for mode, (record, published) in inputs.items():
        rows = [json.loads(line) for line in record.read_text().splitlines()]
        compiles = {}
        for row in rows:
            if row['phase'] != 'object_compile':
                continue
            flags = row['arguments']
            unit = Path(flags[flags.index('-c') + 1])
            if unit.is_relative_to(published / 'units') or unit == published / 'entry.cpp':
                assert row['exit_code'] == 0 and '-O0' in flags
                assert not any(f.startswith('-O') and f != '-O0' for f in flags)
                assert '-g' not in flags and ('-include-pch' in flags) == (mode == 'ordinary-pch')
                compiles[unit] = flags
        manifest = json.loads((published / '.scpp-output.manifest').read_text())
        expected = {published / f['path'] for f in manifest['files']
                    if f['path'] == 'entry.cpp' or (f['path'].startswith('units/') and f['path'].endswith('.cpp'))}
        assert set(compiles) == expected and len(compiles) == 16
        links = [r['arguments'] for r in rows if r['phase'] == 'link' and r['exit_code'] == 0]
        link = links[-1]
        # Native_CPP_Build links provider objects first, then translation units in
        # manifest order. Compile outputs are staged and renamed before linking.
        published_units = [published / f['path'] for f in manifest['files'] if f['kind'] == 'translation_unit']
        objects = [f for f in link if f.endswith('.o')]
        assert len(objects) >= len(published_units) and all(Path(f).is_file() for f in objects)
        object_by_unit = dict(zip(published_units, objects[-len(published_units):]))
        assert set(compiles).issubset(object_by_unit)
        plans[mode] = (compiles, link, object_by_unit)
    clean = ['/usr/bin/env', '-i', 'PATH=/usr/bin:/bin', 'LC_ALL=C', 'TZ=UTC',
             'SOURCE_DATE_EPOCH=0', '/usr/bin/clang++']
    for trial in range(3):
        order = [(mode, jobs) for jobs in (1, 12) for mode in plans]
        if trial % 2:
            order.reverse()
        for mode, jobs in order:
            compiles, link, object_by_unit = plans[mode]
            replacements, tasks = {}, []
            for index, (unit, original) in enumerate(compiles.items()):
                flags = original.copy()
                obj = str(out / f'{mode}-j{jobs}-r{trial}-u{index}.o')
                replacements[object_by_unit[unit]] = obj
                flags[flags.index('-o')+1] = obj
                label = bytes.fromhex(unit.stem[1:]).decode() if unit.stem.startswith('n') else unit.name
                name = f'{mode}-j{jobs}-r{trial}-u{index}'
                def compile_unit(flags=flags, label=label, name=name):
                    start = time.monotonic()
                    runner.run(name, [*clean, *flags], timeout=180)
                    return dict(source=label, seconds=time.monotonic()-start)
                tasks.append(Task(name, compile_unit))
            start = time.monotonic()
            outcomes = run_tasks(tasks, jobs=jobs, report=False)
            elapsed = time.monotonic()-start
            flags = [replacements.get(f, f) for f in link]
            binary = str(out / f'{mode}-j{jobs}-r{trial}.bin')
            flags[flags.index('-o')+1] = binary
            start = time.monotonic()
            runner.run(f'{mode}-{jobs}-{trial}-link', [*clean, *flags], timeout=180)
            linking = time.monotonic()-start
            assert runner.run(f'{mode}-{jobs}-{trial}-execute', [binary]) == expected_output
            report['samples'].append(dict(mode=mode, jobs=jobs, trial=trial,
                compilation_wall_seconds=elapsed, link_seconds=linking, units=[o.value for o in outcomes]))
            (out / 'summary.json').write_text(json.dumps(report, indent=2)+'\n')
            print(mode, jobs, trial, round(elapsed, 3), flush=True)
report['status'] = 'passed'
report['medians'] = []
for mode in plans:
    for jobs in (1, 12):
        samples = [s for s in report['samples'] if s['mode'] == mode and s['jobs'] == jobs]
        units = {u['source']: statistics.median(next(v['seconds'] for v in s['units'] if v['source'] == u['source'])
                 for s in samples) for u in samples[0]['units']}
        report['medians'].append(dict(mode=mode, jobs=jobs,
            compile_seconds=statistics.median(s['compilation_wall_seconds'] for s in samples),
            link_seconds=statistics.median(s['link_seconds'] for s in samples),
            units=units, slowest_unit=max(units, key=units.get)))
(out / 'summary.json').write_text(json.dumps(report, indent=2)+'\n')
print(json.dumps(report['medians'], indent=2), flush=True)
