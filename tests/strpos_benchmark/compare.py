"""Run four executables named {old,new}-{ordinary,thin} in balanced, paired rounds."""
import argparse
import json
import os
import platform
import random
import subprocess
from pathlib import Path
from statistics import median, quantiles


def measure(binary):
    output = subprocess.check_output([binary], text=True)
    return {name: float(value) for name, value in (line.split() for line in output.splitlines())}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('directory', type=Path)
    parser.add_argument('--cpu', type=int, required=True)
    args = parser.parse_args()
    os.sched_setaffinity(0, {args.cpu})
    folder = args.directory.resolve()
    modes = ['ordinary', 'thin']
    rng = random.Random(247)
    rows = []
    for mode in modes:
        for version in ['old', 'new']:
            measure(folder / f'{version}-{mode}')
    for index in range(12):
        rng.shuffle(modes)
        for mode in modes:
            order = ['new', 'old'] if index % 2 else ['old', 'new']
            row = {'pair': index, 'mode': mode, 'order': order}
            for version in order:
                row[version] = measure(folder / f'{version}-{mode}')
            rows.append(row)
    summary = {}
    for mode in modes:
        pairs = [r for r in rows if r['mode'] == mode]
        summary[mode] = {}
        for case in pairs[0]['old']:
            ratios = [100 * (r['new'][case] / r['old'][case] - 1) for r in pairs]
            quartiles = quantiles(ratios, n=4, method='inclusive')
            summary[mode][case] = {'old_ns': median(r['old'][case] for r in pairs),
                                   'new_ns': median(r['new'][case] for r in pairs),
                                   'paired_delta_pct': median(ratios), 'paired_delta_iqr_pct': quartiles[::2]}
    print(json.dumps({'host': platform.node(), 'cpu': args.cpu, 'samples': rows, 'summary': summary}, indent=2))


if __name__ == '__main__':
    main()
