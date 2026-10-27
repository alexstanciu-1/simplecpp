"""Separate ordinary, ready-worker and allocation-counted tokenization measurements."""
import argparse
import json
import os
import platform
from pathlib import Path
import statistics
import subprocess

PROJECT = Path(__file__).resolve().parents[1]
SAMPLES = {'accounting-small': ('accounting-small', 19469), 'accounting-large': ('accounting-large', 86942),
           'my-try': ('my-try/phpp', 298589)}


def run(source, jobs, mode, expected):
    binary = 'tokenization-allocations' if mode == 'allocations' else 'tokenization-profile'
    result = subprocess.check_output([str(PROJECT / 'build/compiler' / binary), str(source), str(jobs),
                                      'profile' if mode == 'profile' else 'normal'], text=True)
    row = json.loads(result)
    assert row['tokens'] == expected and row['token_bytes'] == expected * 8, row
    assert row['capacity_bytes'] >= row['token_bytes'], row
    row['cpu_percent'] = row['cpu_ns'] / (row['wall_us'] * 1000) * 100
    row['mb_per_second'] = row['source_bytes'] / row['wall_us']
    if mode == 'profile':
        assert 0 < row['workers'] <= jobs and row['work_ns'] > 0
        row['active_cpu_percent'] = row['worker_cpu_ns'] / row['work_ns'] * 100
        row['scanner_cpu_percent'] = row['scan_callback_cpu_ns'] / row['work_ns'] * 100
    if mode == 'allocations':
        assert row['reuse_allocations'] == 0, row
    return row


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--jobs', type=int, choices=(16,), default=16)
    parser.add_argument('--trials', type=int, default=21)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    output = {'host': platform.node(), 'platform': platform.platform(),
              'logical_cpus': len(os.sched_getaffinity(0)), 'jobs': args.jobs, 'trials': args.trials,
              'method': 'Warm filesystem, sources loaded before timing; fresh process each trial. '
                        'Normal/gated/allocation runs separate; alternate 1/16 jobs. RSS is /proc/self/status VmRSS and VmHWM for this exec, including prior discovery/loading. '
                        'Ordinary new/new[] counters exclude malloc/aligned/allocator overhead.', 'samples': {}}
    for name, (relative, expected) in SAMPLES.items():
        root = PROJECT / 'samples' / relative
        output['samples'][name] = {}
        for mode in ('normal', 'profile', 'allocations'):
            trials = {1: [], args.jobs: []}
            for jobs in trials:
                run(root, jobs, mode, expected)
            for index in range(args.trials):
                for jobs in ((1, args.jobs) if index % 2 == 0 else (args.jobs, 1)):
                    trials[jobs].append(run(root, jobs, mode, expected))
            summary = {jobs: {key: {'median': statistics.median(r[key] for r in rows),
                                    'min': min(r[key] for r in rows), 'max': max(r[key] for r in rows)}
                              for key in rows[0]} for jobs, rows in trials.items()}
            output['samples'][name][mode] = {'summary': summary, 'trials': trials}
            print(name, mode, json.dumps({key: value['median'] for key, value in summary[args.jobs].items()}), flush=True)
    args.output.write_text(json.dumps(output, indent=2) + '\n')


if __name__ == '__main__':
    main()
