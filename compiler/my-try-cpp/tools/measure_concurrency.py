"""Compare CPU utilization and elapsed time at one and configurable parallel jobs, without allocation instrumentation."""
import argparse
import platform
import json
import os
from pathlib import Path
import statistics
import subprocess

PROJECT = Path(__file__).resolve().parents[1]
TRIALS = 21
SAMPLES = {'accounting-small': 'accounting-small', 'accounting-large': 'accounting-large',
           'my-try': 'my-try/phpp'}


def run(source, jobs):
    output = subprocess.check_output([str(PROJECT / 'build/compiler/source-concurrency-profile'),
                                      str(source), str(jobs)], text=True)
    stages = {}
    for line in output.splitlines():
        stage, *fields = line.split()
        row = {key: int(value) for key, value in (field.split('=') for field in fields)}
        row['average_cores'] = row['cpu_ns'] / (row['wall_us'] * 1000)
        row['cpu_percent'] = row['average_cores'] * 100
        row['worker_budget_percent'] = row['cpu_percent'] / jobs
        stages[stage] = row
    return stages


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--jobs', type=int, choices=(8, 16), default=8)
    parser.add_argument('--output', type=Path, default=PROJECT / 'samples/concurrency.json')
    args = parser.parse_args()
    parallel = args.jobs
    result = {'host': platform.node(), 'platform': platform.platform(), 'parallel_jobs': parallel, 'trials': TRIALS, 'available_logical_cpus': len(os.sched_getaffinity(0)),
              'method': 'Warm filesystem, fresh process each trial; alternating 1/N jobs. '
                        'CLOCK_PROCESS_CPUTIME_ID sums CPU across all threads. '
                        '100% CPU equals one fully occupied logical CPU. '
                        'No allocation instrumentation. Whole-stage intervals include coordinator work.',
              'samples': {}}
    for name, relative in SAMPLES.items():
        source = PROJECT / 'samples' / relative
        for jobs in (1, parallel):
            run(source, jobs)
        trials = {1: [], parallel: []}
        for trial in range(TRIALS):
            for jobs in ((1, parallel) if trial % 2 == 0 else (parallel, 1)):
                trials[jobs].append(run(source, jobs))
        summary = {}
        for jobs, observations in trials.items():
            summary[jobs] = {}
            for stage in ('discovery', 'reading'):
                summary[jobs][stage] = {key: {'median': statistics.median([r[stage][key] for r in observations]),
                                             'min': min(r[stage][key] for r in observations),
                                             'max': max(r[stage][key] for r in observations)}
                                       for key in observations[0][stage]}
        result['samples'][name] = {'summary': summary, 'trials': trials}
        for stage in ('discovery', 'reading'):
            one, many = summary[1][stage], summary[parallel][stage]
            print(name, stage, 'cpu_percent', round(many['cpu_percent']['median'], 1),
                  'wall_us', many['wall_us']['median'],
                  'speedup', round(one['wall_us']['median'] / many['wall_us']['median'], 2),
                  'peak', many['peak_workers']['median'])
    args.output.write_text(json.dumps(result, indent=2) + '\n')


if __name__ == '__main__':
    main()
