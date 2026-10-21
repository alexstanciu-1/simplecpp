"""Focused async semantic preparation proof; run on dev002."""
import argparse
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    run_tasks([Task('async-preparation', lambda: runner.run('async-preparation', ['php', APP / 'tests/async_preparation.php', out / 'source']))])
