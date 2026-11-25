"""A record copies its own fields and keeps shared class fields alive across method calls."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


NODE = 'class Node { public int $value = 3; }'
RECORD = """
struct Row {
    public int $count;
    public Node $node;
    public function __construct(int $start) {
        $this->count = $start;
        $this->node = new Node();
    }
    public function advance():int {
        $this->node->value += 2;
        $this->count++;
        return $this->count;
    }
}
"""
ENTRY = """
$row Row(4);
$copy = $row;
echo $copy->advance(), ":", $row->count, ":", $row->node->value, ":";
$watch = weak($row->node);
$copy->node = new Node();
echo (int)($row->node !== $copy->node), ":", (int)(lock($watch) !== null), ":";
$row->node = new Node();
echo (int)(lock($watch) === null);
return 8;
"""


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-record-ownership-') as work:
        revisions(Path(work), 'php', dict(main=ENTRY, row=RECORD, node=NODE), [
            (None, None, b'5:4:5:1:1:1'),
            ('node', NODE.replace('public', 'private'), 'Cannot access private field'),
            ('node', NODE.replace('= 3', '= 8'), b'5:4:10:1:1:1'),
        ])
