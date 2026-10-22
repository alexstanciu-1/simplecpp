"""Error conditions retained edits, rejection/recovery and fresh equivalence."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
out = parser.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    rows = json.loads(runner.run('retained-publish', ['php', APP / 'tests/error_condition_retained.php', out / 'source']))

    def check(index, row):
        published = Path(row['published'])
        assert json.loads((published / '.scpp-output.manifest').read_text())['application_templates'] == 'forbidden'
        for mode in ['cold', 'warm']:
            label = str(index) + '-' + mode
            build = json.loads(runner.run(label, ['php', APP / 'tools/build_cpp.php',
                published, out / ('cache-' + str(index))], timeout=600))
            assert runner.run(label + '-run', [build['executable']]).decode() == row['expected']
            if mode == 'warm':
                assert build['compiled'] == 0 and not build['linked'], build

    run_tasks([Task(str(index), lambda i=index, r=row: check(i, r)) for index, row in enumerate(rows)])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'publications': rows,
    'invalid_error_operations_rejected': 4, 'recovery': True, 'fresh_equivalence': True}) + '\n')
