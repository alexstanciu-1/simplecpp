"""Profile ready-worker source reads separately from launch, completion and publication."""
import argparse
import json
import os
import platform
from pathlib import Path
import statistics
import subprocess

PROJECT = Path(__file__).resolve().parents[1]
SAMPLES = {'accounting-small': 'accounting-small', 'accounting-large': 'accounting-large',
           'my-try': 'my-try/phpp'}


def run(root, jobs):
    output = subprocess.check_output([str(PROJECT / 'build/compiler/source-concurrency-profile'),
                                      str(root), str(jobs), 'profile'], text=True)
    row = {}
    for line in output.splitlines():
        phase, *fields = line.split()
        values = {key: int(value) for key, value in (field.split('=') for field in fields)}
        if phase == 'reading':
            row.update({'total_' + key: value for key, value in values.items()})
        elif phase == 'breakdown':
            row.update(values)
    assert row['work_ns'] > 0 and 0 < row['workers'] <= jobs, row
    assert row['read_callback_cpu_ns'] <= row['worker_cpu_ns'], row
    # Add only disjoint wall intervals, never overlapping per-worker sums.
    accounted = sum(row[key] for key in ('startup_ns', 'work_ns', 'completion_tail_ns',
                                         'join_tail_ns', 'gather_ns', 'request_ns', 'publication_ns'))
    assert accounted <= row['total_wall_us'] * 1000 + 3000, row
    row['active_cpu_percent'] = row['worker_cpu_ns'] / row['work_ns'] * 100
    row['callback_cpu_percent'] = row['read_callback_cpu_ns'] / row['work_ns'] * 100
    return row


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--jobs', type=int, choices=(8, 16), default=16)
    parser.add_argument('--trials', type=int, default=21)
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    result = {'host': platform.node(), 'platform': platform.platform(),
              'logical_cpus': len(os.sched_getaffinity(0)), 'jobs': args.jobs, 'trials': args.trials,
              'method': 'Fresh process, warm cache, gated ready workers, alternating 1/N jobs. '
                        'Worker CPU excludes launch/completion/join; callback clocks and queue clocks perturb timing.',
              'samples': {}}
    for name, path in SAMPLES.items():
        root = PROJECT / 'samples' / path
        for jobs in (1, args.jobs):
            run(root, jobs)
        trials = {1: [], args.jobs: []}
        for index in range(args.trials):
            for jobs in ((1, args.jobs) if index % 2 == 0 else (args.jobs, 1)):
                trials[jobs].append(run(root, jobs))
        summary = {jobs: {key: {'median': statistics.median(row[key] for row in rows),
                                'min': min(row[key] for row in rows), 'max': max(row[key] for row in rows)}
                          for key in rows[0]} for jobs, rows in trials.items()}
        result['samples'][name] = {'summary': summary, 'trials': trials}
        print(name, json.dumps({key: value['median'] for key, value in summary[args.jobs].items()}), flush=True)
    args.output.write_text(json.dumps(result, indent=2) + '\n')


if __name__ == '__main__':
    main()
