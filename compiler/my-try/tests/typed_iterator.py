"""Typed source Iterator protocol proof in ordinary and prelowered output."""
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
source = r'''interface IntCursor extends \Iterator {
 function current(): int; function key(): int; function valid(): bool; function next(): void; function rewind(): void;
}
class Cursor implements IntCursor {
 public int $position = 0;
 public int $rewinds = 0;
 public int $advances = 0;
 public function current(): int { return $this->position + 10; }
 public function key(): int { return $this->position; }
 public function valid(): bool { return $this->position < 3; }
 public function next(): void { $this->position++; $this->advances++; }
 public function rewind(): void { $this->position=0; $this->rewinds++; }
}
function make_cursor(): IntCursor { echo "make:"; return new Cursor(); }
foreach (make_cursor() as $key => $value) { if ($key === 1) { continue; } echo $key,":",$value,":"; }
$c = new Cursor();$retained=$c;
foreach ($c as $v) { $c=new Cursor();echo $v,":";break; }
echo $retained->rewinds,":",$retained->advances,":";
foreach ($retained as $v) { echo $v,":"; }
echo $retained->advances,":";
class Derived extends Cursor { public function current(): int { return 99; } }
$derived Cursor = new Derived();
foreach ($derived as $v) { echo $v,":"; break; }
class EmptyCursor extends Cursor { public function valid(): bool { return false; } }
foreach (new EmptyCursor() as $v) { echo "bad"; }
class Row { public int $value=7; }
class RowCursor implements \Iterator {
 public $row Row;
 public int $position=0;
 public function __construct(Row $row) { $this->row=$row; }
 public function current(): Row { return $this->row; }
 public function key(): string { return "row"; }
 public function rewind(): void { $this->position=0; }
 public function valid(): bool { return $this->position < 1; }
 public function next(): void { $this->position++; }
}
$row = new Row();
foreach (new RowCursor($row) as $key => $item) { $item->value=23;echo $key,":"; }
echo $row->value;
'''
expected = 'make:0:10:2:12:10:1:0:10:11:12:3:99:row:23'
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
                  ['php8.5', APP / 'tests/typed_iterator_contracts.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
