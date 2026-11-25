"""Constructor borrows retain the caller slot through allocation, staging and base forwarding."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


helpers = """
function sizeOf(const vector<int> &$v):int {return count($v);}
function clearSize(vector<int> &$v):int {clean($v);return 7;}
function fresh():vector<int> {$v vector<int> = [1];return $v;}
"""
base = """
class Snapshot {
    public vector<int> $saved;
    public int $size;
    public function __construct(int $tag,const vector<int> &$items) {
        $this->size=sizeOf($items);$this->saved=$items;
    }
}
"""
derived = """
class Forward extends Snapshot {
    public function __construct(const vector<int> &$items) {parent::__construct(1,$items);}
}
class Reordered extends Snapshot {
    public function __construct(vector<int> $copy) {parent::__construct(items:$copy,tag:clearSize($copy));}
}
class Inherited extends Snapshot {}
"""
cell = """
struct Point {public int $x=6;}
class Points {
    public vector<Point> $saved;
    public function __construct(const vector<Point> &$items) {$this->saved=$items;}
}
struct Cell {
    public hash<int,string> $saved;
    public function __construct(const hash<int,string> &$items) {$this->saved=$items;}
}
"""
main = """
$point Point;$points vector<Point> = [$point];$snapshot=new Points($points);clean($points);
$copied=$snapshot->saved[0];echo $copied->x,":";
$v vector<int> = [3,4];$map hash<int,string> = ["k"=>8];
$a=new Snapshot(1,$v);$b=new Forward($v);$c=new Inherited(2,$v);
$r=new Reordered($v);$cell Cell($map);$ptr=new Cell($map);
$observed=new Snapshot(items:$v,tag:clearSize($v));clean($map);
echo $a->size,$b->size,$c->size,$r->size,$observed->size,":",
     $a->saved[0],$b->saved[1],$c->saved[0],$cell->saved["k"],$ptr->saved["k"];
return 8;
"""
trace = b'6:22200:34388'

with TemporaryDirectory(prefix='scpp-readonly-constructors-') as directory:
    revisions(Path(directory), 'php', dict(main=main, helpers=helpers, base=base, derived=derived, cell=cell), [
        (None, None, trace),
        ('helpers', helpers.replace('const vector', 'vector'), 'Cannot write through read-only reference'),
        (None, None, 'Cannot write through read-only reference'),
        ('helpers', helpers, trace),
        ('base', base.replace('$this->saved=$items;', 'clean($items);'), 'Cannot write through read-only reference'),
        ('base', base.replace('const vector', 'vector'), 'Constructors cannot take writable reference'),
        ('base', base, trace),
        ('cell', cell.replace('hash<int,string> &$items) {$this->saved=$items;', 'hash<int,string> &$items) {$items["k"]=4;'), 'Cannot write through read-only reference'),
        ('cell', cell, trace),
        ('main', main.replace('new Forward($v)', 'new Forward(fresh())'), 'stable variable storage'),
        ('main', main.replace('Cell($map)', 'Cell($v)'), 'exact storage type'),
        ('main', main, trace),
    ])
