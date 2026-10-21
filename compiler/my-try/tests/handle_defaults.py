"""Object handle null-default execution and recovery in ordinary and prelowered output."""
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
source = """interface View { public function get(): int; }
class Row implements View { public int $value = 7; public function get(): int { return $this->value; } }
function read(?Row $row = null): int {
 if ($row) { return $row->value; }
 return 0;
}
function bare(Row $row = null): int { return read($row); }
function viewed(?View $view = null): int {
 if ($view) { return $view->get(); }
 return 2;
}
function weak_default(weak<Row> $row = null): int { return 3; }
class Reader {
 public function read(?Row $row = null): int { return read($row); }
}
$row = new Row(); $empty Row = null; $reader = new Reader();
echo read(), ":", read($empty), ":", read($row), ":", bare(), ":", $reader->read(), ":", $reader->read($row), ":", viewed(), ":", viewed($row), ":", weak_default();
"""
expected = '0:0:7:0:0:7:2:7:3'
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
                  ['php8.5', APP / 'tests/handle_defaults.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
