"""Native callback invocation, lexical captures and typed callback admission."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


APPLY = 'function apply(callback<int,int> $op):int { return $op(4); }'
ENTRY = """
$offset = 3;
$copy = function(int $n) use ($offset):int { return $offset + $n; };
$ref = function(int $n) use (&$offset):int { $offset += $n; return $offset; };
$offset = 9;
echo apply(fn(int $n):int => $n * 2), ":", $copy(4), ":", $ref(2), ":", $offset;
return 8;
"""


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-callbacks-') as work:
        revisions(Path(work), 'php', dict(main=ENTRY, api=APPLY), [
            (None, None, b'8:7:11:11'),
            ('main', ENTRY.replace('$copy(4)', 'apply($copy)'), 'Typed callbacks require a capture-free callable'),
            ('main', ENTRY.replace('$offset = 3', '$offset = 5'), b'8:9:11:11'),
        ])
