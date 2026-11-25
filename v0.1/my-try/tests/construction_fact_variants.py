"""Construction variants preserve evaluation order and independent defaults in both v0.2 modes."""
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
source = r'''class Item { public int $value = 7; }
data class Pair { public int $first; public int $second; }
class Defaults { public $items vector<int> = []; public $records Storage<Item> = []; }
function mark(int &$trace, int $digit): int { $trace = $trace * 10 + $digit; return $digit; }
function size(vector<int> $items = []): int { return count($items); }
$trace = 0;
$pair Pair = {second: mark($trace, 1), first: mark($trace, 2)};
$items vector<int> = [mark($trace, 3), mark($trace, 4)];
$records Storage<Item> = [];
$item = new Item(); $records->append($item);
$alias = $records[0]; $alias->value = 9;
$one = new Defaults(); $two = new Defaults();
$one_records = $one->records; $one_records->append($item);
$two_records = $two->records;
echo $trace, ":", $pair->first, ":", $pair->second, ":", $items[1], ":", $item->value, ":", $two_records->count(), ":", size();
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
            ROOT / 'runtime/metadata/modules', 'compiler', mode, '0'])
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == b'1234:2:1:4:9:0:0'
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'seconds': r.seconds, 'passed': r.error is None} for r in results], indent=2) + '\n')
