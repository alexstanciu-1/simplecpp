"""An upcast retains the original object through its base slot."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


TYPES = """
class Base { public int $value = 3; }
class Child extends Base {}
function view(Child $x):Base { return $x; }
function optional(?Child $x):?Base { return $x; }
"""
ENTRY = """
$child = new Child();
$watch = weak($child);
$base = view($child);
echo (int)($base === $child), (int)(optional(null) === null);
$base->value = 7;
echo $child->value;
$child = new Child();
echo (int)(lock($watch) !== null);
$base = new Base();
echo (int)(lock($watch) === null);
return 8;
"""

if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-upcast-') as tmp:
        revisions(Path(tmp), 'php', dict(main=ENTRY, types=TYPES), [
            (None, None, b'11711'),
            ('types', TYPES + 'function down(Base $x):Child { return $x; }', 'Unsupported explicit conversion'),
            ('types', TYPES, b'11711'),
        ])
