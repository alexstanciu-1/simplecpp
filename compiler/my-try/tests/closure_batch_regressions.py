"""Focused preparation regressions for checked runtime payloads and provider copy contracts."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
names = ['runtime_signatures', 'conversions', 'nullable', 'results', 'references', 'reference_model',
         'result_model', 'callable_storage', 'callable_variance', 'callable_inputs']
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def execute(name):
        destination = out / name
        destination.mkdir()
        runner.run(name, ['php', APP / 'tests' / (name + '.php'), destination])
    run_tasks([Task(name, lambda name=name: execute(name)) for name in names])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'preparation_suites': names,
    'native_compiler_rebuild': False, 'full_matrix_run': False}, indent=2) + '\n')
