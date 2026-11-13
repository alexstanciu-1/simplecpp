#!/usr/bin/env python3
"""Full PHP-to-executable benchmark; separate Clang traces keep instrumentation out of build timings."""
import argparse
import json
import os
import re
from pathlib import Path
import shutil
import statistics
import subprocess
import sys
import time

SAMPLE = Path(__file__).resolve().parent
ROOT = SAMPLE.parents[1]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import qualified_ninja, write_build

PHP = r'''
require $argv[1];
$start = hrtime(true);
$session = (new \scpp\compiler\Module_Loader())->load($argv[2]);
$loaded = hrtime(true);
$prepared = (new \scpp\compiler\Program_Frontend())->prepare($session, new \scpp\compiler\entry_target(31));
$settled = hrtime(true);
$output = \scpp\compiler\Cpp_Program::emit($prepared);
$emitted = hrtime(true);
foreach ($output->files as $path => $text) {
    $dest = $argv[3] . '/' . $path;
    if (!is_dir(dirname($dest))) { mkdir(dirname($dest), 0777, true); }
    file_put_contents($dest, $text);
}
echo json_encode(['module_ms'=>($loaded-$start)/1e6, 'frontend_prepare_ms'=>($settled-$loaded)/1e6,
    'emit_ms'=>($emitted-$settled)/1e6, 'write_ms'=>(hrtime(true)-$emitted)/1e6,
    'units'=>$output->translation_units()], JSON_THROW_ON_ERROR);
'''


def run(command, cwd=None, expected=0):
    start = time.perf_counter()
    with qualified_ninja(command, cwd) as invocation:
        proc = subprocess.run(list(map(str, invocation)), cwd=cwd, capture_output=True, env={**os.environ, 'XDEBUG_MODE': 'off'}, timeout=120)
    ms = (time.perf_counter() - start) * 1000
    if proc.returncode != expected:
        raise RuntimeError(proc.stdout.decode(errors='replace') + proc.stderr.decode(errors='replace'))
    return ms, proc.stdout, proc.stderr


def stats(values):
    return dict(median=statistics.median(values), min=min(values), max=max(values))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--samples', type=int, default=5)
    args = parser.parse_args()
    work = args.output.resolve()
    work.mkdir(parents=True, exist_ok=False)
    sources = work / 'sources'
    sources.mkdir()
    manifest = json.loads((SAMPLE / 'module.json').read_text())
    for path in manifest['sources']:
        dst = sources / path
        dst.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(SAMPLE / path, dst)
    shutil.copyfile(SAMPLE / 'module.json', sources / 'module.json')
    generated = work / 'generated'
    command = ['php', '-r', PHP, '--', ROOT / 'compiler/boot.php', sources, generated]
    _, raw, _ = run(command)
    units = [generated / p for p in json.loads(raw)['units']]
    assert len(units) == 258
    configs = {}
    for level in ('O0', 'O1', 'O2'):
        for mode in ('no-pch', 'fresh-pch', 'reused-pch'):
            name = level + '-' + mode
            directory = work / name
            directory.mkdir()
            ninja = write_build(directory, units, level, mode != 'no-pch')
            configs[name] = dict(directory=directory, command=ninja, samples=[])
            run(ninja, directory)  # Warm compiler/filesystem; create reusable PCH outside timed runs.
    for sample in range(args.samples):
        names = list(configs)
        names = names[sample % len(names):] + names[:sample % len(names)]
        for name in names:
            row = configs[name]
            directory = row['directory']
            for obj in directory.glob('unit-*.o'):
                obj.unlink()
            (directory / 'program').unlink(missing_ok=True)
            if name.endswith('fresh-pch'):
                (directory / 'runtime.pch').unlink(missing_ok=True)
            # Keep graph/dependency metadata warm, but every application object is rebuilt.
            artifact = directory / 'runtime.pch'
            pch_before = artifact.stat().st_mtime_ns if artifact.exists() else None
            php_ms, raw, _ = run(command)
            native_ms, stdout, stderr = run(row['command'], directory)
            (directory / f'build-{sample}.log').write_bytes(stdout + stderr)
            latest = {}
            for line in (directory / '.ninja_log').read_text().splitlines():
                if line.startswith('#'):
                    continue
                start, end, _, output, _ = line.split('\t')
                latest[output] = dict(start_ms=int(start), end_ms=int(end), output=output)
            edges = list(latest.values())
            pch_changed = artifact.exists() and artifact.stat().st_mtime_ns != pch_before
            links = [e for e in edges if e['output'] == 'program']
            objects = [e for e in edges if e['output'].endswith('.o')]
            pch = [e for e in edges if e['output'] == 'runtime.pch' and pch_changed]
            assert len(objects) == 258 and len(links) == 1
            assert bool(pch) == name.endswith('fresh-pch')
            _, result, _ = run([directory / 'program'], expected=8)
            assert result == b'my-try-simple: OK\n', result
            _, sections, _ = run(['readelf', '-S', directory / 'program'])
            assert b'.debug_' not in sections
            row['samples'].append(dict(php_process_ms=php_ms, native_wall_ms=native_ms,
                end_to_end_ms=php_ms + native_ms, php_stages=json.loads(raw),
                compile_window_ms=max(e['end_ms'] for e in objects)-min(e['start_ms'] for e in objects),
                compile_sum_ms=sum(e['end_ms']-e['start_ms'] for e in objects),
                link_driver_ms=links[0]['end_ms']-links[0]['start_ms'],
                pch_ms=sum(e['end_ms']-e['start_ms'] for e in pch),
                binary_bytes=(directory / 'program').stat().st_size, edges=edges))
        print(f'Completed measured sample {sample+1}/{args.samples}', flush=True)
    # Independent instrumented rebuild per configuration. Total Frontend and Backend
    # are disjoint broad phases; Optimizer/CodeGenPasses are nested backend detail.
    for name, row in configs.items():
        directory = row['directory']
        for obj in directory.glob('unit-*.o'):
            obj.unlink()
        if name.endswith('fresh-pch'):
            (directory / 'runtime.pch').unlink()
        graph = directory / 'build.ninja'
        original = graph.read_text()
        traced = original.replace(' -c $in', ' -ftime-trace -c $in').replace(' -x c++-header', ' -ftime-trace -x c++-header')
        traced = traced.replace('-g0 -stdlib=libc++ --ld-path=', '-g0 -stdlib=libc++ -Wl,--perf --ld-path=')
        graph.write_text(traced)
        _, out, err = run(row['command'], directory)
        (directory / 'trace-build.log').write_bytes(out + err)
        perf = re.search(r'^\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+all\s*$',
                         (out + err).decode(), re.MULTILINE)
        assert perf, 'Missing mold performance summary'
        row['mold_trace_ms'] = dict(zip(('user', 'system', 'wall'), [float(v)*1000 for v in perf.groups()]))
        totals = {}
        for trace in directory.glob('unit-*.json'):
            events = json.loads(trace.read_text())['traceEvents']
            for event in events:
                if event['name'].startswith('Total '):
                    key = event['name'][6:]
                    totals[key] = totals.get(key, 0) + event['dur']/1000
        assert len(list(directory.glob('unit-*.json'))) == 258
        row['trace_sum_ms'] = totals
        graph.write_text(original)
        row['summary_ms'] = {key:stats([s[key] for s in row['samples']]) for key in
            ('php_process_ms','native_wall_ms','end_to_end_ms','compile_window_ms','compile_sum_ms','link_driver_ms','pch_ms')}
        row['directory'] = str(directory)
        print(f'Profiled {name}', flush=True)
    versions = {tool:run([tool,'--version'])[1].decode() for tool in ('php','clang++','ninja','ld.mold')}
    result = dict(versions=versions, configs=configs, samples=args.samples, translation_units=len(units),
        runtime_archive=str(ROOT/'build/clang/libscpp_runtime.a'),
        notes='Ninja -j4, mold, -g0, libc++, no LTO/ccache. Existing runtime archive excluded from rebuild. '
        'Warm filesystem caches; fresh application objects each run. PCH validation enabled. '
        'Independent trace runs; trace sums are cumulative elapsed compiler phases, not CPU or parallel wall time. '
        'Link timings include the Clang driver. Ninja edge timestamps have millisecond resolution. '
        'End-to-end is measured PHP process plus native build, including module loading and emitted-file writes.')
    (work/'results.json').write_text(json.dumps(result,indent=2))
    print(work/'results.json', flush=True)


if __name__ == '__main__':
    main()
