"""Real shared runtime assets through module metadata, publication and native build."""
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
source = out / 'source'
source.mkdir()
text = (APP / 'tests/fixtures/runtime_assets.phs').read_text()
(source / 'main.phs').write_text(text.replace('LOCK_PATH', json.dumps(str(out / 'lock'))))
with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
    def check(name):
        target = out / name
        target.mkdir()
        runner.run(name, ['php', APP / 'tests' / (name + '.php'), target])
    run_tasks([Task(name, lambda name=name: check(name))
               for name in ['runtime_assets', 'runtime_catalog']])
    def proof():
        runner.run('publish', ['php', APP / 'tests/fixtures/runtime_assets_publish.php', source, out / 'published'])
        report = json.loads(runner.run('build', ['php', APP / 'tests/fixtures/native_cache_driver.php',
            out / 'published', out / 'cache', ROOT / 'runtime/include', '/usr/bin/clang++'], timeout=300))
        actual = runner.run('execute', [report['executable']])
        if actual != b'array:items|17:ok|duplicate_key|hello:':
            raise RuntimeError('Runtime asset outputs differ: ' + repr(actual))
        (out / 'summary.json').write_text(json.dumps(report, indent=2))
    run_tasks([Task('runtime-assets', proof)])
