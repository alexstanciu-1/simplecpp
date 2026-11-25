#!/usr/bin/env python3
"""Benchmark-only C++ grouping; compiler and shared native-build defaults stay unchanged."""
import argparse
import importlib.util
import json
from pathlib import Path
import re
import shutil
import time

HERE = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location('native_benchmark', HERE/'native-benchmark.py')
base = importlib.util.module_from_spec(spec)
spec.loader.exec_module(base)


def group(originals, directory, count):
    start = time.perf_counter()
    if count == len(originals):
        return originals, 0.0
    # Deterministic round-robin by emitted path; no call-graph packing or source rewriting.
    paths = []
    for i in range(count):
        selected = originals[i::count]
        path = directory/f'group-{i}.cpp'
        path.write_text('\n'.join(p.read_text() for p in selected))
        paths.append(path)
    return paths, (time.perf_counter()-start)*1000


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--samples', type=int, default=7)
    args = parser.parse_args()
    work = args.output.resolve()
    work.mkdir(parents=True, exist_ok=False)
    sources = work/'sources'
    sources.mkdir()
    manifest = json.loads((HERE/'module.json').read_text())
    for path in manifest['sources']:
        dest = sources/path
        dest.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(HERE/path, dest)
    shutil.copyfile(HERE/'module.json', sources/'module.json')
    generated = work/'generated'
    php = ['php','-r',base.PHP,'--',base.ROOT/'compiler/boot.php',sources,generated]
    _, out, _ = base.run(php)
    originals = sorted(generated/p for p in json.loads(out)['units'])
    assert len(originals) == 258
    rows = {}
    for count,jobs in [(1,8),(4,8),(8,8),(258,8),(258,4)]:
        name = f'{count}-units-j{jobs}'
        directory = work/name
        directory.mkdir()
        units, _ = group(originals, directory, count)
        ninja = base.write_build(directory, units, 'O2', False)
        ninja[ninja.index('-j')+1] = str(jobs)
        base.run(ninja,directory)
        rows[name] = dict(count=count,jobs=jobs,directory=str(directory),command=ninja,samples=[])
    for sample in range(args.samples):
        names = list(rows)
        names = names[sample % len(names):]+names[:sample % len(names)]
        for name in names:
            row = rows[name]
            directory = Path(row['directory'])
            for obj in directory.glob('unit-*.o'):
                obj.unlink()
            (directory/'program').unlink(missing_ok=True)
            php_ms, out, _ = base.run(php)
            units, grouping_ms = group(originals,directory,row['count'])
            native_ms, log, err = base.run(row['command'],directory)
            (directory/f'build-{sample}.log').write_bytes(log+err)
            latest = {}
            for line in (directory/'.ninja_log').read_text().splitlines():
                if line.startswith('#'):
                    continue
                start,end,_,output,_ = line.split('\t')
                latest[output] = dict(start=int(start),end=int(end))
            objs = [v for k,v in latest.items() if k.endswith('.o')]
            assert len(objs) == row['count']
            _,stdout,_ = base.run([directory/'program'],expected=8)
            assert stdout == b'my-try-simple: OK\n'
            _,sections,_ = base.run(['readelf','-S',directory/'program'])
            assert b'.debug_' not in sections
            stages = json.loads(out)
            stages.pop('units')
            row['samples'].append(dict(php_ms=php_ms,grouping_ms=grouping_ms,native_ms=native_ms,
                total_ms=php_ms+grouping_ms+native_ms,php_stages=stages,
                compile_window_ms=max(v['end'] for v in objs)-min(v['start'] for v in objs),
                compile_sum_ms=sum(v['end']-v['start'] for v in objs),
                link_ms=latest['program']['end']-latest['program']['start'],
                executable_bytes=(directory/'program').stat().st_size))
        print(f'Completed sample {sample+1}/{args.samples}',flush=True)
    for name,row in rows.items():
        directory = Path(row['directory'])
        for obj in directory.glob('unit-*.o'):
            obj.unlink()
        graph = directory/'build.ninja'
        original = graph.read_text()
        graph.write_text(original.replace(' -c $in',' -ftime-trace -c $in').replace(
            '-g0 -stdlib=libc++ --ld-path=', '-g0 -stdlib=libc++ -Wl,--perf --ld-path='))
        _,out,err = base.run(row['command'],directory)
        (directory/'trace-build.log').write_bytes(out+err)
        perf = re.search(r'^\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+all\s*$',(out+err).decode(),re.M)
        assert perf
        row['mold_trace_ms'] = dict(zip(('user','system','wall'),[float(v)*1000 for v in perf.groups()]))
        totals = {}
        traces = list(directory.glob('unit-*.json'))
        assert len(traces) == row['count']
        for trace in traces:
            for event in json.loads(trace.read_text())['traceEvents']:
                if event['name'].startswith('Total '):
                    key = event['name'][6:]
                    totals[key] = totals.get(key,0)+event['dur']/1000
        row['trace_sum_ms'] = totals
        _, symbols, _ = base.run(['nm', '-C', '--defined-only', directory/'program'])
        row['defined_sample_functions'] = len(re.findall(rb' T sample_unit_\d+_\(\)$', symbols, re.M))
        assert row['defined_sample_functions'] == 256
        row['summary_ms'] = {k:base.stats([s[k] for s in row['samples']]) for k in
            ('php_ms','grouping_ms','native_ms','total_ms','compile_window_ms','compile_sum_ms','link_ms')}
        graph.write_text(original)
        print(f'Profiled {name}',flush=True)
    result = dict(configs=rows,samples=args.samples,source_files=257,source_bytes=42195,
        versions={t:base.run([t,'--version'])[1].decode() for t in ('php','clang++','ninja','ld.mold')},
        notes='O2, g0, libc++, mold, no PCH/LTO/ccache; prebuilt runtime archive. Warm caches, fresh app objects. '
        'Round-robin concatenation of sorted generated paths, including entry adapter in the requested total. '
        'All cases j8 except explicit j4 control. Separate instrumented phase traces; cumulative elapsed, not CPU or wall time. '
        'Total includes PHP startup/loading/preparation/emission/writes plus grouping and native build. '
        'Grouping preserves emitted function definitions but allows ordinary intra-unit LLVM optimization across functions.')
    (work/'results.json').write_text(json.dumps(result,indent=2)+'\n')
    print(work/'results.json',flush=True)


if __name__ == '__main__':
    main()
