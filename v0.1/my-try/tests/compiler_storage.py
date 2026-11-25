"""Compiler collection identity/protocol proof in ordinary and prelowered output."""
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
source = '''class Row { public int $value = 7; }
class Holder { public $rows Storage<Row>; }
$rows = new Storage<Row>(4);
$r = new Row();
$position = $rows->append($r);
$alias = $rows;
$old = $rows[$position];
$replacement = new Row();$replacement->value=12;
$alias[$position] = $replacement;
echo $old->value,":",$rows[$position]->value,":";
unset($rows[$position]);
echo count($alias),":",isset($rows[$position]),":";
$next = $rows->append($old);
echo $next,":",$rows->count(),":",$rows->is_empty(),":";
foreach ($rows as $key => $record) { $record->value=19; echo $key,":"; }
echo $old->value,":";
$rows->replace($next,$replacement);$rows->remove($next);
$rows->reserve(20);echo empty($rows),":";
$named = new Keyed_Storage<Row>();
$named->add("a",$old);$copy=$named;$copy["b"]=$replacement;
echo isset($named["b"]),":",count($named),":";
foreach ($named as $key => $record) { echo $key,":",$record->value,":"; }
unset($named["missing"]);unset($copy["a"]);
echo count($named),":",$old->value,":";
$list = new Key_Storage_List<Row>();
$list->add("x",$old);$list->add("x",$replacement);
$matches=$list->named("x");$list->remove("x",$old);
echo count($matches),":",$matches[0]->value,":",count($list->items()),":";
$a=new Holder();$b=new Holder();$a->rows[]=$old;
echo count($a->rows),":",count($b->rows);
'''
expected = '7:12:0::1:1::1:19:1:1:2:a:19:b:12:1:19:2:19:1:1:0'
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
                  ['php8.5', APP / 'tests/compiler_storage_contracts.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
