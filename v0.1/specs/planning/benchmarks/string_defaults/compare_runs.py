#!/usr/bin/env python3
"""Compare two retained runs of the same string corpus and timed source."""
import argparse
import csv
import json
from pathlib import Path


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('before', type=Path)
    parser.add_argument('after', type=Path)
    parser.add_argument('--backends', nargs='+')
    parser.add_argument('--title', default='Bulk comparison: before and after')
    args = parser.parse_args()
    old_env = json.loads((args.before/'environment.json').read_text())
    new_env = json.loads((args.after/'environment.json').read_text())
    assert old_env['corpus_sha256'] == new_env['corpus_sha256']
    for name in ['bench.cpp', 'bench.rs', 'bench.py', 'bench.php', 'bench.js', 'Bench.java', 'main.phs']:
        assert old_env['sources'][name] == new_env['sources'][name], name
    old = {(x['backend'], x['id']): x for x in json.loads((args.before/'summary.json').read_text())}
    new = {(x['backend'], x['id']): x for x in json.loads((args.after/'summary.json').read_text())}
    if args.backends:
        old = {k:v for k,v in old.items() if k[0] in args.backends}
        new = {k:v for k,v in new.items() if k[0] in args.backends}
    assert old.keys() == new.keys()
    with (args.after/'before_after.csv').open('w') as out:
        writer = csv.writer(out)
        writer.writerow(['backend', 'case', 'before_ns', 'after_ns', 'speedup'])
        for key in sorted(old):
            a, b = old[key]['median_ns'], new[key]['median_ns']
            writer.writerow([*key, a, b, a/b])
    lines = ['# ' + args.title, '', 'Doc Status: supporting', '',
             'Median microseconds/op; speedup is before divided by after. Same corpus and timed source, separate process runs. Full samples and ranges remain in each run’s summary.', '']
    for size in (64, 4096, 65536):
        lines += [f'## Nominal {size}-byte inputs', '',
                  '| Path | Input | Case | Before µs | After µs | Speedup |',
                  '| --- | --- | --- | ---: | ---: | ---: |']
        for backend in (args.backends or ['runtime', 'runtime_snapshot', 'mytry_concrete', 'mytry_ordinary', 'cpp']):
            for encoding in ['ascii', 'utf8']:
                for case in ['compare_equal', 'compare_late', 'compare_early']:
                    key = backend, f'{encoding}_{size}_{case}'
                    a, b = old[key]['median_ns']/1000, new[key]['median_ns']/1000
                    lines.append(f'| {backend} | {encoding} | {case} | {a:.3f} | {b:.3f} | {a/b:.2f}× |')
        lines.append('')
    (args.after/'before_after.md').write_text('\n'.join(lines)+'\n')


if __name__ == '__main__':
    main()
