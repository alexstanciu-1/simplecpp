"""Variadic construction preserves ordered operands, private packs and default construction."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


base = '''
class Scores {
    public int $sum=0;
    public function __construct(int $base=7,int ...$items) {
        $this->sum=$base;
        foreach ($items as $x) {$this->sum=$this->sum+$x;}
        if (count($items)>0) {$items[0]=99;}
        $items[]=100;
    }
}
'''
cell = '''
struct Cell {
    public int $sum=0;
    public function __construct(int ...$xs) {
        $this->sum=10;
        foreach ($xs as $x) {$this->sum=$this->sum+$x;}
        $xs[]=25;
    }
}
'''
children = '''
class Forward extends Scores {
    public function __construct(int $n,int ...$xs) {
        parent::__construct(mark($n),...watch($xs));
        echo count($xs);
    }
}
class Individual extends Scores {
    public function __construct() {parent::__construct(mark(1),mark(2),mark(3));}
}
class Implicit extends Scores {public function __construct() {}}
class Inherited extends Scores {}
'''
entry = '''
function mark(int $x):int {echo $x;return $x;}
function watch(vector<int> $xs):vector<int> {echo "V";return $xs;}
struct Meter {
    public int $n=0;
    public function __construct(int $base=8,int ...$xs) {$this->n=$base+count($xs);}
}
struct Box {public Cell $cell;public Meter $meter;}
$v vector<int> = [2,3];
$a=new Scores(mark(1),mark(2),mark(3));echo "=", $a->sum,":";
$b=new Scores(mark(1),...watch($v));echo "=", $b->sum,":",$v[0],count($v),";";
$f=new Forward(1,...$v);echo "=", $f->sum,":",$v[0],count($v),";";
$i=new Individual();echo "=", $i->sum,";";
$plain=new Scores();$implicit=new Implicit();$inherited=new Inherited();echo $plain->sum,$implicit->sum,$inherited->sum,";";
$c Cell(...$v);$def Cell;$box Box;$ptr=new Cell();echo $c->sum,":",$def->sum,":",$box->cell->sum,":",$ptr->sum,":",count($v),":",$box->meter->n;
return 8;
'''
trace = b'123=6:1V=6:22;1V2=6:22;123=6;777;15:10:10:10:2:8'

with TemporaryDirectory(prefix='scpp-variadic-ctors-') as directory:
    revisions(Path(directory), 'php', dict(main=entry, base=base, cell=cell, children=children), [
        (None, None, trace),
        ('cell', cell.replace('int ...$xs', 'vector<int> $xs'), 'zero-argument constructor'),
        ('cell', cell, trace),
    ])

# Keep both signatures well-formed and isolate the marker edit from default construction.
parent = 'class ParentScore {public int $n=0;public function __construct(int ...$xs) {$this->n=count($xs);$xs[]=9;}}'
derived = 'class ChildScore extends ParentScore {public function __construct(int ...$xs) {parent::__construct(...$xs);echo count($xs);}}'
main = '$v vector<int> = [4,5];$c=new ChildScore(...$v);echo $c->n,count($v);return 8;'
with TemporaryDirectory(prefix='scpp-parent-pack-') as directory:
    revisions(Path(directory), 'php', dict(main=main, parent=parent, derived=derived), [
        (None, None, b'222'),
        ('parent', parent.replace('int ...$xs', 'vector<int> $xs'), 'Unpacking requires'),
        ('parent', parent, b'222'),
        ('derived', derived.replace('int ...$xs', 'vector<int> $xs'), 'Unpacking requires'),
        ('derived', derived, b'222'),
    ])

readonly_cell = cell.replace('int ...$xs', 'int &...$xs').replace('$xs[]=25;', '')
readonly_derived = derived.replace('int ...$xs', 'int &...$xs')
with TemporaryDirectory(prefix='scpp-readonly-ctors-') as directory:
    revisions(Path(directory), 'php', dict(main=entry, base=base, cell=readonly_cell, children=children), [
        (None, None, trace),
        ('cell', readonly_cell.replace('int &...$xs', 'vector<int> $xs'), 'zero-argument constructor'),
        ('cell', readonly_cell, trace),
    ])
with TemporaryDirectory(prefix='scpp-readonly-parent-') as directory:
    revisions(Path(directory), 'php', dict(main=main, parent=parent, derived=readonly_derived), [
        (None, None, b'222'),
        ('parent', parent.replace('int ...$xs', 'int &...$xs'), 'read-only variadic pack'),
        ('parent', parent.replace('int ...$xs', 'int &...$xs').replace('$xs[]=9;', ''), b'222'),
    ])
