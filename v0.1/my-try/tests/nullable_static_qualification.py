"""Nullable static qualifiers preserve nominal selection and ordered effects in both output modes."""
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
source = """class Base {
 public static int $number = 4;
 const LIMIT = 5;
 public static function read(): int { return self::$number; }
}
class Derived extends Base {}
function absent(int &$stage): ?Derived { $stage = $stage * 10 + 1; return null; }
function rhs(int &$stage): int { $stage = $stage * 10 + 2; return 8; }
$none ?Derived = null;
echo $none::$number, ":", $none::LIMIT, ":", $none::read();
$stage = 0;
absent($stage)::$number = rhs($stage);
echo ":", $stage, ":", $none::$number;
$stage = 0;
$constant = absent($stage)::LIMIT;
echo ":", $stage, ":", $constant;
$stage = 0;
$called = absent($stage)::read();
echo ":", $stage, ":", $called;
$none::$number += 1;
echo ":", $none::$number;
"""
expected = '4:5:4:12:8:1:5:1:8:9'
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
    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
