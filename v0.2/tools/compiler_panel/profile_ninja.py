#!/usr/bin/env python3
"""Ninja -j4 / mold comparison: fresh builds and full source recompilation with reusable PCH."""
import argparse
import json
from pathlib import Path
import tempfile

from native_build import write_build
from profile_native import HERE, ROOT, invoke, stats


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--samples', type=int, default=5)
    args = parser.parse_args()
    if args.samples < 1:
        parser.error('--samples must be positive')
    work = Path(tempfile.mkdtemp(prefix='scpp-ninja-profile-'))
    results = {}
    for count in [1, 8]:
        source_dir = work / f'{count}-sources'
        source_dir.mkdir()
        (source_dir / 'sources').mkdir()
        files = [{'name':'main.phs','flavor':'php'}]
        (source_dir / 'sources/main.phs').write_text('$ret int = 8;\necho "Result: ";\necho $ret;\necho "\\n";\nreturn $ret;')
        for i in range(count - 1):
            name = f'support{i}.cpp'
            files.append({'name':name,'flavor':'cpp'})
            (source_dir / 'sources' / name).write_text(f'void helper{i}() {{ echo "support{i}"; return; }}')
        request = source_dir / 'request.json'
        request.write_text(json.dumps({'entry':'main.phs','files':files}))
        _, raw = invoke(['php',HERE/'report.php',request])
        report = json.loads(raw)
        assert report['ok'], report
        (source_dir/'compiler-report.json').write_bytes(raw)
        units = [source_dir / 'generated' / u for u in report['units']]
        configurations = []
        for level in ['O0','O1','O2']:
            for pch in [False, True]:
                name = f'{count}-sources-{level}-pch-{pch}'
                directory = work / name
                directory.mkdir()
                command = write_build(directory, units, level, pch)
                command = [command[0], '-C', directory, *command[1:]]
                results[name] = {'sources':count, 'translation_units':len(units), 'optimization':level,
                                 'pch':pch, 'fresh_ms':[], 'recompile_ms':[], 'commands':list(map(str,command))}
                configurations.append((name,directory,command))
                # One unmeasured build warms filesystem caches for each configuration.
                invoke(command)
        for sample in range(args.samples):
            order = configurations[sample % 6:] + configurations[:sample % 6]
            for name,directory,command in order:
                invoke([command[0], '-C', directory, '-t', 'clean'])
                ms, log = invoke(command)
                (directory / f'fresh-{sample}.log').write_bytes(log)
                results[name]['fresh_ms'].append(ms)
                for output in [*directory.glob('unit-*.o'), directory/'program']:
                    output.unlink()
                ms, log = invoke(command)
                (directory / f'recompile-{sample}.log').write_bytes(log)
                results[name]['recompile_ms'].append(ms)
                if results[name]['pch']:
                    assert b'-x c++-header' not in log, 'PCH was rebuilt in reuse measurement'
                _, output = invoke([directory/'program'],expected=8)
                assert output == b'Result: 8\n'
                _, sections = invoke(['readelf','-S',directory/'program'])
                assert b'.debug_' not in sections
                results[name]['bytes'] = (directory/'program').stat().st_size
        for name,directory,command in configurations:
            _, log = invoke(command)
            assert b'no work to do' in log
            if results[name]['pch']:
                header = directory/'runtime_pch.hpp'
                header.write_text(header.read_text()+'// invalidation proof\n')
                _, log = invoke(command)
                assert b'-x c++-header' in log and b'-include-pch' in log
                _, output = invoke([directory/'program'], expected=8)
                assert output == b'Result: 8\n'
        print(f'Completed {count} source(s)',flush=True)
    for row in results.values():
        row['fresh'] = stats(row['fresh_ms'])
        row['recompile'] = stats(row['recompile_ms'])
    tools = {name:invoke([name,'--version'])[1].decode() for name in ['clang++','ninja','ld.mold']}
    notes = (f'Ninja -j4, mold, -g0, existing runtime archive. {args.samples} rotated fresh-build samples per configuration, '
             'filesystem caches warm. Fresh includes PCH creation. Recompile deletes all objects and executable '
             'but retains PCH; no-op and PCH-change invalidation verified. Output and exit 8 verified. '
             'The 1/8-source workloads have 2/9 translation units including the entry adapter, which does not use the PCH.')
    (work/'results.json').write_text(json.dumps({'notes':notes,'tools':tools,'samples':args.samples,'results':results},indent=2))
    lines = ['# Ninja / mold / PCH comparison','',notes,'', '| Sources | Opt | PCH | Fresh median ms [min–max] | Recompile median ms [min–max] |', '|---:|---|---|---:|---:|']
    for row in results.values():
        f=row['fresh'];r=row['recompile'];lines.append(f"| {row['sources']} | {row['optimization']} | {row['pch']} | {f['median']} [{f['min']}–{f['max']}] | {r['median']} [{r['min']}–{r['max']}] |")
    (work/'report.md').write_text('\n'.join(lines)+'\n')
    print(work)
    print('\n'.join(lines))

if __name__ == '__main__':
    main()
