"""Metadata-directed cursor record widening in ordinary and concrete output."""
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
source = """class Base { public int $number = 3; }
class Derived extends Base {}
$rows Storage<Derived> = [];
$child = new Derived();
$rows[] = $child;
$cursor = new Storage_Cursor<Base>($rows);
$current Base = $cursor->current();
$current->number = 8;
echo $child->number, ":", $cursor->key(), ":", $cursor->valid();
$cursor->next();
echo ":", $cursor->valid();
"""
expected = '8:0:1:'
negative = {
 'reverse': ('class Base {} class Derived extends Base {} $rows Storage<Base> = []; $c = new Storage_Cursor<Derived>($rows);', 'proven safe same-family upcast'),
 'unrelated': ('class Base {} class Other {} $rows Storage<Other> = []; $c = new Storage_Cursor<Base>($rows);', 'proven safe same-family upcast'),
 'nullable-record': ('class Base {} $rows Storage<?Base> = [];', 'shared_record'),
 'unique-record': ('class Base {} $rows Storage<unique<Base>> = [];', 'shared_record'),
}

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
    def reject(name, mode, program, diagnostic):
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'compiler', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic in error, error
    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['ordinary', 'concrete']] +
              [Task(name + '-' + mode, lambda name=name, mode=mode, pair=pair: reject(name, mode, *pair))
               for name, pair in negative.items() for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
