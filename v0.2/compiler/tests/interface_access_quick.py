"""Sibling interface views share an object and reject an incompatible receiver."""
from pathlib import Path
import tempfile

from collection_goal_native import guard, revisions


TYPES = """
interface Left { public function readValue():int; }
interface Right { public function writeValue(int $n):int; }
class Both implements Left, Right {
    public int $x=1;
    public override function readValue():int { return $this->x; }
    public override function writeValue(int $n):int { $this->x=$n; return $n; }
}
class Single implements Left { public override function readValue():int { return 0; } }
function right(Left $left):Right { return object_cast($left, Right::class); }
function supports(?Left $left):bool { return $left instanceof Right; }
"""
ENTRY = """
$both = new Both();
$right = right($both);
echo (int)supports($both), (int)supports(new Single()), (int)supports(null);
echo $right->writeValue(7), $both->readValue();
return 8;
"""

if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-interface-access-') as tmp:
        work = Path(tmp)
        revisions(work, 'php', dict(main=ENTRY, types=TYPES), [(None, None, b'10077')])
        guard(work, 'php', 'mismatch', TYPES + 'right(new Single()); return 8;', 'invalid_object_cast')
