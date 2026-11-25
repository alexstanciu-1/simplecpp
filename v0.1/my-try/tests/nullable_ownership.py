"""Explicit ownership stays possibly empty; redundant nullable spelling rejects."""
import argparse
import json
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = r'''struct S { public int $n = 9; }
$empty shared<S> = null;
$observer weak<S> = null;
$absent = lock($observer);
if ($empty) { return 1; }
if ($absent) { return 1; }
$owner shared<S> = new S();
$weak = weak($owner);
$held = lock($weak);
$owner = null;
$unique unique<S> = new S();
$moved unique<S> = move($unique);
if ($unique) { return 2; }
echo $held->n, ":", $moved->n;
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def preparation():
        runner.run('model', ['php8.5', APP / 'tests/ownership_model.php'])
        runner.run('preparation', ['php8.5', APP / 'tests/nullable_ownership.php', out / 'retained'])
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'core', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == b'9:9'
    results = run_tasks([Task("preparation", preparation)] + [Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
