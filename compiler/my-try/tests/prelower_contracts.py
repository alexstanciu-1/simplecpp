#!/usr/bin/env python3
"""Focused backend contract rejection/recovery through the existing shared runner."""
import argparse
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        run_tasks([Task('callable-boundary', lambda: runner.run('callable-boundary',
            ['php', APP / 'tests/prelower_callable_boundary.php', out / 'source']))])


if __name__ == '__main__':
    main()
