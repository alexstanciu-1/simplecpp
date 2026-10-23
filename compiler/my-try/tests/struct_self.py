"""Struct self values use ordinary copies and preserve receiver mutation in both modes."""
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
source = r'''class Link { public int $value = 3; }
struct Point {
 public int $x = 2;
 public vector<int> $items = [5];
 public Link $link;
 public function __construct(set:$link) {}
 public function snapshot(): Point { return $this; }
 public function local_copy(): Point { $copy = $this; $copy->x = 8; return $copy; }
 public function pass_copy(): int { return change($this); }
 public function closure_copy(): Point { $f = fn(): Point => $this; return $f(); }
 public function mutate(): void { $this->x = 6; }
}
struct Derived_Point extends Point {}
function change(Point $point): int { $point->x = 20; return $point->x; }
$link = new Link();
$point = new Point($link);
$copy = $point->snapshot();
$local = $point->local_copy();
$point->mutate();
$copy->items[0] = 9;
$copy->link->value = 11;
echo $point->x, ":", $copy->x, ":", $local->x, ":", $point->pass_copy(), ":", $point->x, ":", $point->items[0], ":", $point->link->value;
$shared shared<Point> = new Point($link);
$shared_copy = $shared->snapshot();
$shared_copy->x = 42;
echo ":", $shared->x;
$from_closure = $point->closure_copy();
$from_closure->x = 19;
$derived = new Derived_Point($link);
$base_copy = $derived->snapshot();
$base_copy->x = 21;
echo ":", $point->x, ":", $derived->x;
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def preparation():
        runner.run('preparation', ['php8.5', APP / 'tests/struct_self.php', out / 'retained'])
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'core', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == b'6:2:8:20:6:5:11:2:6:2'
    results = run_tasks([Task("preparation", preparation)] + [Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
