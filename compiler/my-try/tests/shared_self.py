"""v0.2 construction binds self ownership; inherited acquisition retains the original object."""
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
source = r'''class Counts { public static int $destroyed = 0; }
interface Self_View { function keep(): Base_Record; }
class Base_Record implements Self_View {
 public int $value = 4;
 public function keep(): Base_Record { return $this; }
 public function pass(): int { return read_value($this); }
 public function copy_local(): Base_Record { $saved = $this; return $saved; }
 public function __destruct() { Counts::$destroyed++; }
}
class Derived_Record extends Base_Record { public int $other = 7; }
final class Constructed_Record extends Base_Record {
 public int $tag;
 public function __construct(set:$tag) { $this->value = $this->tag; }
}
function read_value(Base_Record $item): int { return $item->value; }
$owner ?Derived_Record = new Derived_Record();
$view ?Self_View = $owner;
$retained ?Base_Record = $view->keep();
$second ?Base_Record = $owner->copy_local();
$owner->value = 9;
echo $retained->value, ":", $owner->pass();
$owner = null;
$view = null;
$retained->value = 12;
echo ":", $second->value, ":", Counts::$destroyed;
$retained = null;
echo ":", $second->value;
$second = null;
echo ":", Counts::$destroyed;
$constructed ?Constructed_Record = new Constructed_Record(17);
$last ?Base_Record = $constructed->keep();
$constructed = null;
echo ":", $last->value;
$last = null;
echo ":", Counts::$destroyed;
'''
expected = '9:9:12:0:12:1:17:2'
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
    def retained():
        runner.run('retained', ['php8.5', APP / 'tests/shared_self.php', out / 'retained'])
    results = run_tasks([Task('retained', retained)] + [Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected, 'tasks': [{'name': r.name, 'seconds': r.seconds} for r in results]}, indent=2) + '\n')
