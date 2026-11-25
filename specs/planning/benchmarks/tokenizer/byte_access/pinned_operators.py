"""Compare tokenizer executables in alternating pairs on a pinned dev002 CPU."""
import argparse
import hashlib
import json
import os
import platform
import subprocess
from pathlib import Path


def measure(root, work, cpu, pairs, modes=('slot', 'handwritten'), flavor='cpp'):
    binaries = {(cc, mode): work / (cc + '-' + flavor) / mode / 'clean'
                for cc in ('clang++', 'g++') for mode in modes}
    return measure_binaries(root, work, cpu, pairs, modes, flavor, binaries)


def measure_binaries(root, work, cpu, pairs, modes, flavor, binaries):
    os.sched_setaffinity(0, {cpu})
    corpus = root / flavor
    source = corpus / 'input9'
    expected = (corpus / 'expected9').read_bytes()
    prior = work / 'prior-operators'
    prior.write_bytes(source.read_bytes() + b' previous')
    compilers = list(dict.fromkeys(cc for cc, _ in binaries))
    output = dict(flavor=flavor, host=platform.node(), cpu=cpu, sibling_list=Path(
        f'/sys/devices/system/cpu/cpu{cpu}/topology/thread_siblings_list'
    ).read_text().strip(), bytes=source.stat().st_size, tokens=len(expected.splitlines()),
                  batches_per_run=9, scans_per_batch=128, binaries={}, rows=[])
    for cc in compilers:
        for mode in modes:
            binary = binaries[cc, mode]
            assert subprocess.check_output([binary, 'dump', source, prior]) == expected
            output['binaries'][cc+' '+mode] = hashlib.sha256(binary.read_bytes()).hexdigest()
            subprocess.check_output([binary, 'time', source, prior])
    for i in range(pairs):
        for cc in compilers[::(-1 if (i//2)%2 else 1)]:
            for mode in modes[::(-1 if i%2 else 1)]:
                data = subprocess.check_output([binaries[cc, mode], 'time', source, prior])
                samples = list(map(float, data.split()))
                assert len(samples) == 9 and min(samples) > 0
                output['rows'].append(dict(pair=i, compiler=cc, scanner=mode,
                                           seconds_per_scan=samples))
    return output


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('fixtures', type=Path)
    parser.add_argument('work', type=Path)
    parser.add_argument('--cpu', type=int, required=True)
    parser.add_argument('--pairs', type=int, default=24)
    parser.add_argument('--signed-work', type=Path, help='Compare the nonnegative Clang builds with handwritten for every flavor')
    args = parser.parse_args()
    if args.signed_work:
        from measure import verify
        modes = ('nonnegative', 'handwritten')
        results = []
        for flavor in ('cpp', 'php', 'js'):
            binaries = {('clang++', 'nonnegative'): args.signed_work / flavor / 'nonnegative' / 'clean',
                        ('clang++', 'handwritten'): args.work / ('clang++-' + flavor) / 'handwritten' / 'clean'}
            for binary in binaries.values():
                verify(binary, args.fixtures / flavor)
            results.append(measure_binaries(args.fixtures, args.work, args.cpu, args.pairs, modes, flavor, binaries))
    else:
        results = measure(args.fixtures, args.work, args.cpu, args.pairs)
    print(json.dumps(results, indent=2))
