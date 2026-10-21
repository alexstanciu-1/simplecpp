"""Nullable null-default execution and recovery in ordinary and prelowered output."""
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
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = """function text(?string $value = null): string { return $value ?? "absent"; }
function number(?int $value = null): int { return $value ?? 7; }
class Reader {
 public function read(?string $value = null): string { return text($value); }
}
$r = new Reader();
echo text(), ":", text(null), ":", text(""), ":", text("value"), ":";
echo number(), ":", number(null), ":", number(0), ":", $r->read(), ":", $r->read("member");
"""
expected = 'absent:absent::value:7:7:0:absent:member'
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'compiler', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(mode + '-execute', [build['executable']]).decode()
        assert actual == expected, (actual, expected)
    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']] +
              [Task('contracts', lambda: runner.run('contracts',
                  ['php8.5', APP / 'tests/nullable_defaults.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
