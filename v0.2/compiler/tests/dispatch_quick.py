"""Native dispatch through a concrete class, an abstract base and an interface."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


CONTRACT = 'interface Reader { public function read(int $n=1):int; }'
BASE = """
abstract class Base {
    protected int $offset=3;
    abstract public function read(int $n=1):int;
    public function adjusted(int $n):int { return $this->offset + $n; }
}
"""
LEAF = """
class Leaf extends Base implements Reader {
    public override function read(int $n=2):int { return parent::adjusted($n); }
}
"""
ENTRY = """
function throughInterface(Reader $r):int { return $r->read(); }
function throughBase(Base $r):int { return $r->read(); }
$item = new Leaf();
echo $item->read(), throughInterface($item), throughBase($item);
return 8;
"""


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-method-dispatch-') as work:
        revisions(Path(work), 'php', dict(main=ENTRY, base=BASE, leaf=LEAF, contract=CONTRACT), [
            (None, None, b'544'),
            (None, None, b'544'),
            ('leaf', 'class Leaf extends Base implements Reader {}', 'must implement'),
            ('leaf', LEAF.replace('adjusted($n)', 'adjusted($n + 10)'), b'151414'),
        ])
