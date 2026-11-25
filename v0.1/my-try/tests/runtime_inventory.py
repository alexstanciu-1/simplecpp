#!/usr/bin/env python3
"""Runtime catalog composition and demand-only concrete preparation (PHP host)."""
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
    output = args.results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    with CommandRunner(output / 'logs', ROOT, jobs=12) as runner:
        run_tasks([Task(name, lambda name=name: runner.run(name,
            ['php', APP / 'tests' / (name + '.php')]))
            for name in ['runtime_inventory', 'runtime_types', 'runtime_protocols']])


if __name__ == '__main__':
    main()
