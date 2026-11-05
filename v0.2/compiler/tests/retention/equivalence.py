"""100 resident invocations per flavor, clean checkpoints, bounded retention and native equivalence."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import statistics
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build
ENV = {**os.environ, 'XDEBUG_MODE': 'off'}
PHP = os.environ.get('PHP_EXECUTABLE', 'php')
WORKER = Path(__file__).with_suffix('.php')


def command(args, cwd=None, input=None):
    return subprocess.run(list(map(str, args)), cwd=cwd, input=input, capture_output=True,
                          text=True, env=ENV, timeout=60)


def artifacts(directory):
    """Compare all emitted source/header bytes and inventory, excluding host manifest bookkeeping."""
    return {str(p.relative_to(directory)): p.read_bytes() for p in directory.rglob('*')
            if p.suffix in ('.cpp', '.hpp')}


def native(output, build, expected, log):
    """Trace actual native work; fresh checkpoints have their own empty object directory."""
    build.mkdir(parents=True, exist_ok=True)
    manifest = json.loads((output / 'manifest.json').read_text())
    before = {p.name: p.stat().st_mtime_ns for p in build.glob('unit-*.o')}
    exe = build / 'program'
    old_exe = exe.stat().st_mtime_ns if exe.exists() else None
    args = write_build(build, [output / p for p in manifest['translation_units']], 'O2')
    args[2] = '8'
    graph = build / 'build.ninja'
    graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
    start = time.perf_counter()
    result = command(args, build)
    elapsed = (time.perf_counter() - start) * 1000
    log.write_text(result.stdout + result.stderr)
    assert result.returncode == 0, result.stdout + result.stderr
    changed = [p for p in build.glob('unit-*.o') if before.get(p.name) != p.stat().st_mtime_ns]
    totals = {}
    for obj in changed:
        for event in json.loads(obj.with_suffix('.json').read_text())['traceEvents']:
            if event['name'].startswith('Total '):
                key = event['name'][6:]
                totals[key] = totals.get(key, 0) + event['dur'] / 1000
    link = 0
    if old_exe != exe.stat().st_mtime_ns:
        row = [r.split('\t') for r in (build / '.ninja_log').read_text().splitlines()[1:]
               if r.split('\t')[3] == 'program'][-1]
        link = int(row[1]) - int(row[0])
    run = command([exe])
    assert (run.returncode, run.stdout) == (8, expected), (run.returncode, run.stdout, expected)
    return dict(native_ms=elapsed, objects=len(changed), trace_sum_ms=totals, link_ms=link,
                stdout=run.stdout, exit=run.returncode)


def prove(work):
    """Five fixed 20-step cycles; fresh checks for the first cycle and every subsequent cycle end."""
    work.mkdir(parents=True, exist_ok=False)
    report = {}
    for flavor in ('cpp', 'php', 'js'):
        base = work / flavor
        app = base / 'app'
        output = base / 'incremental'
        app.mkdir(parents=True)
        (app / 'module.json').write_text('{}')
        fn = lambda name, typ, body: (f'{typ} {name}()' if flavor == 'cpp'
                                     else f'function {name}(): {typ}') + '{' + body + '}'
        glob = '$stored' if flavor == 'php' else 'stored'
        def provider(c=8, gt='int', gv='8', ft='int', value='8'):
            const = f'const LIMIT: int = {c};' if flavor == 'js' else f'const int LIMIT = {c};'
            global_decl = {'cpp': f'global {gt} stored = {gv};', 'php': f'global $stored {gt} = {gv};',
                           'js': f'global let stored: {gt} = {gv};'}[flavor]
            return const + global_decl + fn('value', ft, 'return ' + value + ';')
        local = lambda name, expr: {'cpp': f'int {name} = {expr};', 'php': f'${name} int = {expr};',
                                    'js': f'let {name}: int = {expr};'}[flavor]
        var = lambda name: '$' + name if flavor == 'php' else name
        reader = fn('reader', 'void', 'echo LIMIT; ' + local('g', glob) + 'echo ' + var('g') + '; '
                    + local('f', 'value()') + 'echo ' + var('f') + ';')
        baseline = {'main': 'reader(); return 8;', 'provider': provider(), 'reader': reader,
                    'quiet': fn('quiet', 'void', '')}
        stamp = int(time.time())
        def install(sources):
            nonlocal stamp
            for p in app.glob('*.' + flavor):
                if p.stem not in sources:
                    p.unlink()
            for name, text in sources.items():
                p = app / (name + '.' + flavor)
                if not p.exists() or p.read_text() != text:
                    p.write_text(text)
                    stamp += 1
                    os.utime(p, (stamp, stamp))
        process = subprocess.Popen([PHP, str(WORKER), str(app), str(output)], stdin=subprocess.PIPE,
                                   stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=ENV)
        rows = []
        try:
            sources = dict(baseline)
            for step in range(100):
                phase = step % 20
                if phase in (0, 8, 11, 16, 18):
                    sources = dict(baseline)
                elif phase == 2:
                    sources['provider'] = provider(value='9')
                elif phase == 3:
                    sources['provider'] = provider(c=9, value='9')
                elif phase == 4:
                    sources['provider'] = provider(c=9, gt='bool', gv='false', value='9')
                elif phase == 5:
                    sources['provider'] = provider(c=9, gt='bool', gv='false', ft='bool', value='true')
                elif phase == 6:
                    del sources['provider']
                elif phase in (9, 10):
                    sources['duplicate'] = provider() + (' ' if phase == 10 else '')
                elif phase == 12:
                    sources['provider'] = 'int value(){' if flavor == 'cpp' else 'function value(): int {'
                elif phase == 14:
                    sources['provider'] = provider(value='missing()')
                elif phase == 17:
                    sources['reader'] = fn('reader', 'void', 'echo 0;')
                install(sources)
                old = artifacts(output)
                process.stdin.write('run\n')
                process.stdin.flush()
                line = process.stdout.readline()
                assert line, process.stderr.read()
                result = json.loads(line)
                expected_ok = phase not in (6, 7, 9, 10, 12, 13, 14, 15)
                assert result['ok'] == expected_ok, (flavor, step, result)
                if not expected_ok:
                    assert artifacts(output) == old, 'Failed compilation changed published C++'
                if expected_ok:
                    assert result['retention']['pending_bodies'] == 0
                    assert result['retention']['pending_emission_sources'] == 0
                if phase in (1, 19) or (phase == 0 and step > 0):
                    assert result['work']['preparation']['prepared'] == 0
                    assert result['work']['emitted_sources'] == []
                checkpoint = step < 20 or phase == 19
                row = dict(step=step, phase=phase, checkpoint=checkpoint, incremental=result)
                if checkpoint:
                    fresh_output = base / f'fresh-{step}' / 'generated'
                    clean = command([PHP, WORKER, app, fresh_output], input='run\n')
                    assert clean.returncode == 0, clean.stderr
                    fresh = json.loads(clean.stdout)
                    row['fresh'] = fresh
                    assert result['ok'] == fresh['ok'], (flavor, step, 'success mismatch')
                    assert result['diagnostics'] == fresh['diagnostics'], (flavor, step, 'diagnostic mismatch', result['diagnostics'], fresh['diagnostics'])
                    # A rejected frontend leaves incremental live symbols from the prior version; fresh has none.
                    if fresh['retention'] is not None:
                        assert result['symbols'] == fresh['symbols'], (flavor, step, 'live symbols mismatch')
                    if expected_ok:
                        assert artifacts(output) == artifacts(fresh_output), (flavor, step, 'C++ mismatch')
                        current_manifest = json.loads((output / 'manifest.json').read_text())
                        fresh_manifest = json.loads((fresh_output / 'manifest.json').read_text())
                        for field in ('sources', 'translation_units', 'headers'):
                            assert current_manifest[field] == fresh_manifest[field], (flavor, step, field)
                        row['artifact_hashes'] = {k: hashlib.sha256(v).hexdigest() for k, v in artifacts(output).items()}
                        if phase in (0, 5, 16, 19):
                            expected = '901' if phase == 5 else '888'
                            row['native_incremental'] = native(output, base / 'native', expected, base / f'native-{step}.log')
                            row['native_fresh'] = native(fresh_output, fresh_output.parent / 'native', expected,
                                                         fresh_output.parent / 'native.log')
                            assert row['native_incremental']['stdout'] == row['native_fresh']['stdout']
                rows.append(row)
                (base / 'results.json').write_text(json.dumps(rows, indent=2))
            print(f'{flavor}: 100 runs, 24 fresh checkpoints, retention and native equivalence passed', flush=True)
        finally:
            process.stdin.close()
            process.wait(timeout=15)
            assert process.returncode == 0, process.stderr.read()
        report[flavor] = rows
    (work / 'results.json').write_text(json.dumps(report, indent=2))
    write_report(work, report)
    return report


def write_report(work, report):
    """Keep measured compiler work and selected native checkpoints separate from test/protocol overhead."""
    lines = ['# Incremental versus fresh: 100 runs per flavor', '',
             '300 resident invocations, 72 fresh checkpoints, 48 native build/execution checks. '
             'O2/g0, Ninja8, mold, no PCH/LTO, prebuilt runtime. Native builds only occur at selected '
             'checkpoints, so their work can include changes since an earlier checkpoint. '
             'These are validation timings, not a controlled speedup benchmark. Clang trace phase sums '
             'are cumulative over compiled objects, not parallel wall time.', '',
             '| Flavor | PHP total, 100 runs (ms) | Incremental checkpoint avg (ms) | Fresh checkpoint avg (ms) | Prepared / reused bodies on successful runs | Final retained sessions / roots |',
             '|---|---:|---:|---:|---:|---:|']
    for flavor, rows in report.items():
        checkpoints = [r for r in rows if r['checkpoint']]
        work_rows = [r['incremental']['work']['preparation'] for r in rows if r['incremental']['ok']]
        state = rows[-1]['incremental']['retention']
        lines.append(f"| {flavor} | {sum(r['incremental']['php_ms'] for r in rows):.3f} | "
                     f"{statistics.mean(r['incremental']['php_ms'] for r in checkpoints):.3f} | "
                     f"{statistics.mean(r['fresh']['php_ms'] for r in checkpoints):.3f} | "
                     f"{sum(r['prepared'] for r in work_rows)} / {sum(r['reused'] for r in work_rows)} | "
                     f"{state['session_depth']} / {state['retained_ast_roots']} |")
    lines += ['', '| Flavor / mode | Native avg (min–max), ms | Frontend sum avg, ms | LLVM sum avg, ms | Link avg, ms | Objects total |',
              '|---|---:|---:|---:|---:|---:|']
    for flavor, rows in report.items():
        for mode in ('native_incremental', 'native_fresh'):
            a = [r[mode] for r in rows if mode in r]
            lines.append(f"| {flavor} / {mode} | {statistics.mean(x['native_ms'] for x in a):.3f} "
                         f"({min(x['native_ms'] for x in a):.3f}–{max(x['native_ms'] for x in a):.3f}) | "
                         f"{statistics.mean(x['trace_sum_ms'].get('Frontend', 0) for x in a):.3f} | "
                         f"{statistics.mean(x['trace_sum_ms'].get('Backend', 0) for x in a):.3f} | "
                         f"{statistics.mean(x['link_ms'] for x in a):.3f} | {sum(x['objects'] for x in a)} |")
    lines += ['', 'Every incremental invocation checks retention. All checked diagnostics, live declarations, '
              'generated C++/headers and inventories agree with fresh compilation. Native output is also checked '
              'against independent expected stdout and exit 8. A failed frontend preserves the prior incremental '
              'symbols, while fresh has no published state; only diagnostics are compared for that boundary.', '',
              'PHP timings exclude process startup, test inspection and explicit test GC. Prepared/reused totals '
              'cover successful runs only. Memory samples include PHP/test overhead and are not exclusive compiler '
              'RSS; boundedness is verified by object reachability and weak observations. Raw results, logs, '
              'generated output and latest Clang traces remain alongside this report.']
    (work / 'report.md').write_text('\n'.join(lines) + '\n')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, help='New evidence directory; otherwise use temporary storage')
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-equivalence-') as directory:
            prove(Path(directory) / 'proof')
