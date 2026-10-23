"""Metadata-directed cursor record widening in ordinary and concrete output."""
import argparse
import json
import copy
import shutil
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
interface Readable { function read(): int; }
class Derived extends Base implements Readable { public function read(): int { return $this->number; } }
$rows Storage<Derived> = [];
$child = new Derived();
$rows[] = $child;
$cursor = new Storage_Cursor<Base>($rows);
$current Base = $cursor->current();
$current->number = 8;
echo $child->number, ":", $cursor->key(), ":", $cursor->valid();
$cursor->next();
echo ":", $cursor->valid();
$exact = new Storage_Cursor<Derived>($rows);
$interface = new Storage_Cursor<Readable>($rows);
$readable Readable = $interface->current();
echo ":", $readable->read(), ":", $exact->current()->number;
"""
expected = '8:0:1::8:8'
negative = {
 'reverse': ('class Base {} class Derived extends Base {} $rows Storage<Base> = []; $c = new Storage_Cursor<Derived>($rows);', 'proven safe same-family upcast'),
 'unrelated': ('class Base {} class Other {} $rows Storage<Other> = []; $c = new Storage_Cursor<Base>($rows);', 'proven safe same-family upcast'),
 'nullable-record': ('class Base {} $rows Storage<?Base> = [];', 'shared_record'),
 'unique-record': ('class Base {} $rows Storage<unique<Base>> = [];', 'shared_record'),
}

# Private catalogs prove source spelling is not a semantic dispatch key.
catalog = json.loads((ROOT / 'runtime/metadata/modules/compiler.json').read_text())
def private_catalog(label, data):
    folder = out / 'metadata' / label
    folder.mkdir(parents=True)
    shutil.copyfile(ROOT / 'runtime/metadata/modules/core.json', folder / 'core.json')
    (folder / 'compiler.json').write_text(json.dumps(data))
    return folder

def renamed(value):
    if isinstance(value, dict):
        return {k: (renamed(v) if k != 'symbol' else v) for k, v in value.items()}
    if isinstance(value, list):
        return [renamed(v) for v in value]
    if isinstance(value, str) and value in ['Storage', 'Storage_Cursor']:
        return {'Storage': 'Record_Bag', 'Storage_Cursor': 'Record_View'}[value]
    return value
renamed_catalog = private_catalog('renamed', renamed(catalog))
malformed = {}
for label, relation, diagnostic in [
    ('unknown-name', {'from': 'Missing', 'to': 'Record'}, 'Unknown shared conversion type parameter'),
    ('missing-to', {'from': 'Source'}, 'Shared conversion requires from and to'),
    ('unknown-field', {'from': 'Source', 'to': 'Record', 'variance': True}, 'Unknown shared conversion field'),
]:
    data = copy.deepcopy(catalog)
    next(f for f in data['functions'] if f['id'] == 'compiler.Storage_Cursor.make')['shared_conversions'] = [relation]
    malformed[label] = (private_catalog(label, data), diagnostic)

with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode, rename=False):
        label = mode + ('-renamed' if rename else '')
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source.replace('Storage_Cursor', 'Record_View').replace('Storage', 'Record_Bag') if rename else source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, renamed_catalog if rename else ROOT / 'runtime/metadata/modules', 'compiler', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']]).decode()
        assert actual == expected, (actual, expected)
    def reject(name, mode, program, diagnostic):
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'compiler', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic in error, error
    def reject_metadata(label, metadata, diagnostic):
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text('echo 1;')
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder, metadata, 'compiler', 'ordinary'], expected=255)
        assert diagnostic in (out / 'logs' / (label + '.stderr')).read_text()
    run_tasks([Task('retained', lambda: runner.run('retained', ['php8.5', APP / 'tests/storage_cursor_retained.php', out / 'retained']))] +
              [Task(mode + ('-renamed' if rename else ''), lambda mode=mode, rename=rename: check(mode, rename))
               for rename in [False, True] for mode in ['ordinary', 'concrete']] +
              [Task(label, lambda label=label, pair=pair: reject_metadata(label, *pair)) for label, pair in malformed.items()] +
              [Task(name + '-' + mode, lambda name=name, mode=mode, pair=pair: reject(name, mode, *pair))
               for name, pair in negative.items() for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
