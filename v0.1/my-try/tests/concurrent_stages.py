"""Shared-runner checks for preparation and emission scheduling/recovery."""
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
with CommandRunner(out / 'logs', ROOT) as runner:
    run_tasks([Task(name, lambda name=name: runner.run(name,
        ['php8.5', APP / 'tests' / (name + '.php'), out / name], timeout=300))
        for name in ['layout_depths', 'concurrent_stages', 'callable_instances', 'trait_instances', 'async_retained', 'result_model', 'contextual_closures']])
