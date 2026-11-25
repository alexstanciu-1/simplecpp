#!/usr/bin/env python3
"""Compare fresh Clang builds and collect separate instrumentation traces on the panel default sample."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess
import tempfile
import time

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]


def invoke(command, expected=0):
    start = time.perf_counter()
    result = subprocess.run(list(map(str, command)), capture_output=True, timeout=60)
    elapsed = (time.perf_counter() - start) * 1000
    if result.returncode != expected:
        raise RuntimeError(result.stderr.decode(errors='replace'))
    return elapsed, result.stdout


def stats(values):
    return {key: round(value, 3) for key, value in [('median', statistics.median(values)), ('min', min(values)), ('max', max(values))]}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--samples', type=int, default=5)
    args = parser.parse_args()
    work = args.output or Path(tempfile.mkdtemp(prefix='scpp-clang-profile-'))
    work.mkdir(parents=True, exist_ok=True)
    (work / 'sources').mkdir(exist_ok=True)
    source = '$ret int = 8;\necho "Result: ";\necho $ret;\necho "\\n";\nreturn $ret;'
    (work / 'sources/main.phs').write_text(source)
    request = {'entry': 'main.phs', 'files': [{'name': 'main.phs', 'flavor': 'php'}]}
    (work / 'request.json').write_text(json.dumps(request))
    _, raw = invoke(['php', HERE / 'report.php', work / 'request.json'])
    report = json.loads(raw)
    assert report['ok'], report
    (work / 'compiler-report.json').write_bytes(raw)
    units = [work / 'generated' / name for name in report['units']]
    common = ['clang++', '-g0', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror', '-I', ROOT / 'runtime/generated/public']
    library = ROOT / 'build/clang/libscpp_runtime.a'
    results = {level: {'combined': [], 'compile': [], 'link': [], 'units': [[] for _ in units]} for level in ['O0','O1','O2']}
    commands = []
    # Warm filesystem/header caches equally; every measured run still compiles fresh objects.
    for level in results:
        invoke([*common, '-' + level, *units, library, '-o', work / ('warm-' + level)])
    for sample in range(args.samples):
        levels = list(results)
        levels = levels[sample % 3:] + levels[:sample % 3]
        for level in levels:
            binary = work / ('program-' + level)
            command = [*common, '-' + level, *units, library, '-o', binary]
            commands.append(list(map(str, command)))
            elapsed, _ = invoke(command)
            results[level]['combined'].append(elapsed)
            _, stdout = invoke([binary], expected=8)
            assert stdout == b'Result: 8\n', stdout
            results[level]['bytes'] = binary.stat().st_size
            objects = []
            total = 0
            for index, unit in enumerate(units):
                obj = work / f'{level}-{index}.o'
                elapsed, _ = invoke([*common, '-' + level, '-c', unit, '-o', obj])
                results[level]['units'][index].append(elapsed)
                total += elapsed
                objects.append(obj)
            results[level]['compile'].append(total)
            elapsed, _ = invoke(['clang++', '-g0', '-stdlib=libc++', *objects, library, '-o', work / ('split-' + level)])
            results[level]['link'].append(elapsed)
            _, stdout = invoke([work / ('split-' + level)], expected=8)
            assert stdout == b'Result: 8\n'
    traces = {}
    for level in results:
        traces[level] = []
        for index, unit in enumerate(units):
            trace = work / f'trace-{level}-{index}.json'
            invoke([*common, '-' + level, '-ftime-trace=' + str(trace), '-c', unit, '-o', work / f'trace-{level}-{index}.o'])
            events = json.loads(trace.read_text())['traceEvents']
            totals = {event['name'].removeprefix('Total '): round(event['dur']/1000, 3) for event in events if event['name'].startswith('Total ')}
            headers = sorted([{'file':e.get('args',{}).get('detail'), 'ms':round(e['dur']/1000,3)} for e in events if e['name']=='Source'], key=lambda x:x['ms'], reverse=True)[:8]
            traces[level].append({'unit':str(unit.relative_to(work)), 'totals_ms':totals, 'largest_header_events':headers})
        _, sections = invoke(['readelf', '-S', work / ('program-' + level)])
        assert b'.debug_' not in sections, sections
    _, version = invoke(['clang++', '--version'])
    summary = {level: {**{k:stats(row[k]) for k in ['combined','compile','link']}, 'unit_compile': [stats(v) for v in row['units']], 'executable_bytes':row['bytes']} for level,row in results.items()}
    output = {'compiler':version.decode(), 'samples':args.samples, 'source':source, 'units':list(map(str,units)),
              'summary_ms':summary, 'raw_ms':results, 'traces':traces, 'commands':commands,
              'notes':'Warm filesystem caches, fresh builds; rotated optimization order. Split and combined builds measured separately. Trace runs are separate and instrumented; nested trace totals must not be added. All builds use -g0; no .debug_ sections; output and exit 8 verified.'}
    (work / 'results.json').write_text(json.dumps(output,indent=2))
    lines = ['# Clang native build profile', '', output['notes'], '', 'Panel default integer + echo sample; two translation units; existing runtime archive.', '', '| Level | Combined ms median [min, max] | Compile ms | Link ms | Bytes |','|---|---:|---:|---:|---:|']
    for level,row in summary.items():
        c=row['combined'];lines.append(f"| -{level} -g0 | {c['median']} [{c['min']}, {c['max']}] | {row['compile']['median']} | {row['link']['median']} | {row['executable_bytes']} |")
    (work / 'report.md').write_text('\n'.join(lines)+'\n')
    print(work)
    print('\n'.join(lines))

if __name__ == '__main__':
    main()
