"""Shared input/result identity and API-scoped task admission in both lowering modes."""
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
cases = {
    'identity': ('''class Item { public int $visits=0; }
class Totals { public int $published=0; public int $matched=0; }
$item=new Item();$totals=new Totals();$items vector<Item> = [$item,$item];
$work function<Item(Item)> = function(Item $value):Item {
 task_synchronize(function() use ($value):void {$value->visits++;});return $value;
};
$publish function<void(Item)> = function(Item $value) use ($item,$totals):void {
 $totals->published++;if($value === $item){$totals->matched++;}
};
echo task_run_publish_unordered($items,2,$work,$publish),":",$item->visits,":",$totals->published,":",$totals->matched;
''', '2:2:2:2', None),
    'isolated-payload': ('class Item{}$item=new Item();$items vector<Item> = [$item];task_run($items,1,fn(Item $value):Item=>$value);', '', 'Task payload type is not transferable'),
    'joined-borrow': ('$count=0;$items vector<int> = [1];task_run_publish_unordered($items,1,function(int $n)use(&$count):int{$count++;return $n;},function(int $n):void{});', '', 'Task callback captures are not transferable'),
}
with CommandRunner(out/'logs', ROOT, jobs=12) as runner:
    def check(mode, name, source, expected, rejection):
        tag = mode+'-'+name
        folder = out/tag
        (folder/'source').mkdir(parents=True)
        (folder/'source/main.phs').write_text(source)
        output = runner.run(tag+'-publish', ['php8.5', APP/'tests/async_tasks_publish.php', folder,
                            ROOT/'runtime/metadata/modules', 'tasks', mode], expected=255 if rejection else 0)
        if rejection:
            diagnostic = output.decode() + (out/'logs'/ (tag+'-publish.stderr')).read_text()
            assert rejection in diagnostic, diagnostic
            return
        build = json.loads(runner.run(tag+'-build', ['php8.5', APP/'tools/build_cpp.php', folder/'published', folder/'cache'], timeout=300))
        assert runner.run(tag+'-run', [build['executable']]) == expected.encode()
    run_tasks([Task(mode+'-'+name, lambda m=mode,n=name,s=source,e=expected,r=rejection: check(m,n,s,e,r))
               for mode in ['ordinary','concrete'] for name,(source,expected,rejection) in cases.items()])
(out/'summary.json').write_text(json.dumps({'passed':True,'cases':list(cases),'modes':['ordinary','concrete']},indent=2)+'\n')
