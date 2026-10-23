"""Private-field preparation/recovery and independent struct subobjects."""
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
source = '''struct Base_Record {
 private int $position;
 function __construct(set:$position) {}
 function read_base(): int { return $this->position; }
}
struct Child_Record extends Base_Record {
 private string $position;
 function __construct(int $base_value, set:$position) { parent::__construct($base_value); }
 function read_child(): string { return $this->position; }
}
$value = new Child_Record(7, "child");
echo $value->read_base(), ":", $value->read_child();
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def preparation():
        runner.run('preparation', ['php8.5', APP / 'tests/private_fields.php', out / 'retained'])
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'core', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == b'7:child'
    results = run_tasks([Task('preparation', preparation)] + [Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
