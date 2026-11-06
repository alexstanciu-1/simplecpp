#!/usr/bin/env python3
"""Stable capability workloads: PHP stages, retained-session edits, fresh native builds and separate Clang traces."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import platform
import re
import shutil
import statistics
import subprocess
import sys
import time

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build


def run(command, cwd=None, expected=0):
    start = time.perf_counter()
    result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True,
                            env={**os.environ, 'XDEBUG_MODE': 'off'}, timeout=180)
    elapsed = (time.perf_counter() - start) * 1000
    if result.returncode != expected:
        raise RuntimeError(f'{command}: exit {result.returncode}\n' + result.stdout.decode(errors='replace') + result.stderr.decode(errors='replace'))
    return elapsed, result.stdout, result.stderr


def summary(values):
    return {'average': statistics.mean(values), 'median': statistics.median(values), 'min': min(values), 'max': max(values)}


def summarize_rows(rows, fields):
    return {key: summary([row[key] for row in rows]) for key in fields}


def clean(work):
    for obj in work.glob('unit-*.o'):
        obj.unlink()
    (work / 'program').unlink(missing_ok=True)


def link_ms(work):
    rows = [line.split('\t') for line in (work / '.ninja_log').read_text().splitlines()[1:]]
    link = [row for row in rows if row[3] == 'program'][-1]
    return int(link[1]) - int(link[0])


def benchmark(name, work, php_samples, native_samples):
    print(f'{name}: PHP stages and incremental checks', flush=True)
    app = work / 'sources'
    app.mkdir(parents=True)
    source = HERE / name
    manifest = json.loads((source / 'module.json').read_text())
    expected = json.loads((source / 'expected.json').read_text())
    for path in [*manifest['sources'], 'module.json', 'expected.json']:
        destination = app / path
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source / path, destination)
    php_ms, raw, stderr = run(['php', HERE / 'stages.php', app, work / 'cpp', php_samples])
    (work / 'php.json').write_bytes(raw)
    (work / 'php.stderr.log').write_bytes(stderr)
    php = json.loads(raw)
    units = [work / 'cpp' / path for path in php['cpp_files']]
    emitted_root = (work / 'cpp').resolve()
    for unit in units:
        for delimiter, include in re.findall(r'^#include ([<"])([^>"]+)', unit.read_text(), re.M):
            if include.startswith('scpp/'):
                assert (ROOT / 'runtime/generated/public' / include).is_file(), include
            else:
                header = (unit.parent / include).resolve()
                assert delimiter == '"' and header.is_relative_to(emitted_root), (unit, include)
                assert header.suffix == '.hpp' and header.is_file(), (unit, include)
    emitted_text = ''.join(unit.read_text() for unit in units)
    generated = {'cpp_bytes': sum(unit.stat().st_size for unit in units),
                 'cpp_lines': sum(len(unit.read_text().splitlines()) for unit in units),
                 'sequencing_lambdas': emitted_text.count('([&]()'),
                 'total_lambdas': len(re.findall(r'\(\[(?:&)?\]\(', emitted_text)),
                 'binary_snapshots': emitted_text.count('const auto binary_left'),
                 'rvalue_casts': len(re.findall(r'static_cast<[^>]*&&>', emitted_text))}
    native = work / 'native'
    native.mkdir()
    command = write_build(native, units, 'O2', False)
    command[2] = '8'
    run(command, native)  # Warm tool/filesystem state; each measured sample rebuilds every application object.
    measurements = []
    for index in range(native_samples):
        print(f'{name}: native build {index + 1}/{native_samples}', flush=True)
        clean(native)
        elapsed, stdout, stderr = run(command, native)
        (native / f'build-{index}.log').write_bytes(stdout + stderr)
        execute_ms, stdout, stderr = run([native / 'program'], expected=expected['exit'])
        assert stdout == expected['stdout'].encode(), (name, stdout, expected['stdout'])
        assert not stderr, stderr
        measurements.append({'native_ms': elapsed, 'link_ms': link_ms(native), 'execution_ms': execute_ms,
                             'executable_bytes': (native / 'program').stat().st_size})
    _, sections, _ = run(['readelf', '-S', native / 'program'])
    assert b'.debug_' not in sections
    graph = native / 'build.ninja'
    original = graph.read_text()
    graph.write_text(original.replace(' -MD ', ' -ftime-trace -MD '))
    trace_measurements = []
    for index in range(native_samples):
        print(f'{name}: instrumented build {index + 1}/{native_samples}', flush=True)
        clean(native)
        trace_wall, stdout, stderr = run(command, native)
        trace_dir = native / f'trace-{index}'
        trace_dir.mkdir()
        (trace_dir / 'build.log').write_bytes(stdout + stderr)
        _, stdout, _ = run([native / 'program'], expected=expected['exit'])
        assert stdout == expected['stdout'].encode()
        totals = {}
        traces = list(native.glob('unit-*.json'))
        assert len(traces) == len(units)
        for path in traces:
            for event in json.loads(path.read_text())['traceEvents']:
                if event['name'].startswith('Total '):
                    key = event['name'][6:]
                    totals[key] = totals.get(key, 0) + event['dur'] / 1000
            shutil.move(path, trace_dir / path.name)
        trace_measurements.append({'wall_ms': trace_wall, 'frontend_ms': totals['Frontend'],
            'llvm_backend_ms': totals['Backend'], 'optimizer_ms': totals.get('Optimizer', 0),
            'codegen_ms': totals.get('CodeGenPasses', 0), 'link_ms': link_ms(native)})
    graph.write_text(original)
    trace_summary = summarize_rows(trace_measurements, trace_measurements[0].keys())
    native_summary = summarize_rows(measurements, ['native_ms', 'link_ms', 'execution_ms', 'executable_bytes'])
    stages = summarize_rows(php['stages'], php['stages'][0].keys())
    incremental = {key: {'times': summarize_rows(rows, ['frontend_ms', 'prepare_ms', 'total_ms']),
                         'source_reads': rows[0]['source_reads'], 'reused_asts': rows[0]['reused_asts'],
                         'bodies': rows[0]['bodies'], 'diagnostic': rows[0]['diagnostic']}
                   for key, rows in php['incremental'].items()}
    report = {'source_units': php['source_units'], 'source_bytes': php['source_bytes'], 'tokens': php['tokens'],
              'cpp_units': php['cpp_units'], 'generated': generated, 'peak_php_bytes': php['peak_php_bytes'], 'php_process_ms': php_ms,
              'stages': stages, 'incremental': incremental, 'native_samples': measurements, 'native': native_summary,
              'trace_samples': trace_measurements, 'trace_summary': trace_summary,
              'trace': {k: v['average'] for k, v in trace_summary.items()}, 'expected': expected,
              'compile_plus_native_medians_ms': stages['total_ms']['median'] + native_summary['native_ms']['median']}
    (work / 'results.json').write_text(json.dumps(report, indent=2) + '\n')
    return report


def markdown(report):
    def pair(value):
        return f'{value["average"]:.3f} / {value["min"]:.3f}'
    lines = ['# Capability benchmarks', '', 'Doc Status: supporting', '',
        'Times in milliseconds, **average / minimum** of five samples per workload.',
        'Five warmed PHP compilations, five fresh application native builds, and five separate instrumented builds.',
        'O2/g0, libc++, Ninja -j8, mold; no PCH/LTO/ccache; prebuilt runtime. One C++ TU per source, plus the entry adapter.', '',
        '| Sample | Source KiB / C++ TUs | Generated C++ KiB | PHP total | Native wall | Linker |',
        '| --- | ---: | ---: | ---: | ---: | ---: |']
    for name, r in report['samples'].items():
        lines.append(f'| {name} | {r["source_bytes"]/1024:.1f} / {r["cpp_units"]} | {r["generated"]["cpp_bytes"]/1024:.1f} | {pair(r["stages"]["total_ms"])} | {pair(r["native"]["native_ms"])} | {pair(r["native"]["link_ms"])} |')
    lines += ['', '| Sample | Module | Read | Tokenize | Parse/collect | Symbols | Prepare | Lower/emit |', '| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |']
    for name, r in report['samples'].items():
        lines.append('| ' + name + ' | ' + ' | '.join(pair(r['stages'][k]) for k in ['module_ms','read_ms','tokenize_ms','parse_collect_ms','symbol_index_ms','prepare_ms','lower_emit_ms']) + ' |')
    lines += ['', 'PHP total includes measured orchestration, excludes process startup and writing emitted C++ files.', '',
        '| Sample | Clang frontend | LLVM backend | LLVM optimizer | Code generation | Instrumented wall |',
        '| --- | ---: | ---: | ---: | ---: | ---: |']
    for name, r in report['samples'].items():
        lines.append('| ' + name + ' | ' + ' | '.join(pair(r['trace_summary'][k]) for k in ['frontend_ms','llvm_backend_ms','optimizer_ms','codegen_ms','wall_ms']) + ' |')
    lines += ['', 'Trace categories accumulate across parallel TUs and overlap (optimizer/code generation are within backend). Do not sum them or compare their sum to build wall time. Native wall above excludes tracing overhead.', '',
        '| Sample / edit | Frontend | Prepare | Total | Source reads | ASTs reused | Bodies prepared / reused |',
        '| --- | ---: | ---: | ---: | ---: | ---: | --- |']
    for name, r in report['samples'].items():
        for scenario, case in r['incremental'].items():
            bodies = case['bodies']
            label = f'{bodies["prepared"]} / {bodies["reused"]}' if bodies else 'expected diagnostic'
            lines.append(f'| {name} / {scenario} | ' + ' | '.join(pair(case['times'][k]) for k in ['frontend_ms','prepare_ms','total_ms']) + f' | {case["source_reads"]} | {case["reused_asts"]} | {label} |')
    lines += ['', 'Five retained-session edit sequences per workload: full, no-op, body edit, incompatible signature, recovery, final no-op. Assertions verify source reads, AST/token identity, body reuse and caller invalidation. Edits use disposable copies.',
        'Incremental timings stop after preparation; no incremental native-build speedup is claimed.', '',
        'All 30 measured/traced executables matched expected stdout and exit code 8; measured executables contain no debug sections. Runtime includes use the public API.',
        'The frozen mixed-flavor workloads are not exhaustive coverage of the new scalar operations. Dedicated compiler proofs cover those operations. The large workload repeats invoice functions; this is synthetic data.',
        'This run measures the unchanged workload; it is not a controlled before/after speed comparison.',
        'Raw samples, ranges, versions, source hashes, compiler hashes, logs, generated C++, and per-run Clang traces are retained in the benchmark output directory.', '']
    return '\n'.join(lines)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    output = args.output.resolve(); output.mkdir(parents=True, exist_ok=False)
    versions = {}
    for name, command in [('php',['php','--version']),('clang',['clang++','--version']),('ninja',['ninja','--version']),('mold',['mold','--version'])]:
        _, stdout, _ = run(command); versions[name] = stdout.decode().splitlines()[0]
    _, cpu, _ = run(['lscpu'])
    (output / 'cpu.txt').write_bytes(cpu)
    _, head, _ = run(['git', 'rev-parse', 'HEAD'], ROOT)
    source_hashes = {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest()
                     for p in sorted(HERE.rglob('*')) if p.is_file() and p.suffix in ('.phs','.scpp','.jss','.php','.py','.json') and '__pycache__' not in p.parts and not p.name.startswith('benchmark-')}
    compiler_hashes = {str(p.relative_to(ROOT)): hashlib.sha256(p.read_bytes()).hexdigest()
                       for p in sorted((ROOT/'compiler').rglob('*.php'))}
    report = {'platform': platform.platform(), 'versions': versions, 'git_head': head.decode().strip(),
              'note': 'Current capability workload including increment/decrement; exact compiler/sample hashes recorded.',
              'sample_hashes': source_hashes, 'compiler_hashes': compiler_hashes,
              'runtime_archive_sha256': hashlib.sha256((ROOT/'build/clang/libscpp_runtime.a').read_bytes()).hexdigest(),
              'metadata_sha256': hashlib.sha256((ROOT/'runtime/metadata/runtime.json').read_bytes()).hexdigest(), 'samples': {}}
    for name in ['small','medium','large']:
        report['samples'][name] = benchmark(name, output/name, 5, 5)
        (output/'results.json').write_text(json.dumps(report,indent=2)+'\n')
    for relative, digest in compiler_hashes.items():
        assert hashlib.sha256((ROOT / relative).read_bytes()).hexdigest() == digest, f'Compiler changed during benchmark: {relative}'
    (output/'report.md').write_text(markdown(report))
    print(output/'report.md')

if __name__ == '__main__':
    main()
