"""PHP whole-slot reset, checked reads and incremental owner-type changes."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def prove(work):
    node = 'class Node { public int $value = 8; public virtual function tag(): int { return 0; } }'
    factory = 'function makeNode(): ?Node { return new Node(); }'
    main = """
        enum Kind { case First=1; }
        $kind ?Kind = Kind::First;
        $words vector<string> = ["one"];
        $list nullable<vector<string>> = $words;
        unset($list);
        unset($kind);
        if ($list !== null || $kind !== null) { return 6; }
        struct Point { public int $x; }
        $point shared<Point> = new Point();
        unset($point);
        if ($point !== null) { return 7; }
        $owner = makeNode();
        $alias =& $owner;
        $copy = $owner;
        $watch = weak($owner);
        $other = $watch;
        unset($watch);
        if (!$watch->is_empty() || lock($other) !== $owner) { return 1; }
        unset($owner);
        unset($owner);
        if ($alias !== null || lock($other) !== $copy) { return 2; }
        unset($copy);
        if ($other->is_empty() || lock($other) !== null) { return 3; }
        $alias = new Node();
        if ($owner->value !== 8) { return 4; }
        $n ?int = 7;
        unset($n);
        if ($n !== null) { return 5; }
        $n = 8;
        return (int)$n;
    """
    bad_type = 'Cannot unset storage of type'
    bad_alias = 'Cannot unset a native alias or reference parameter'
    edits = [(None, '', b''), (None, '', b''),
             ('factory', factory.replace('?Node', 'Node'), bad_type),
             ('factory', factory, b''),
             ('main', main.replace('unset($owner)', 'unset($alias)'), bad_alias),
             ('main', 'function f(?int &$v): void { unset($v); } $v ?int = 1; f($v); return 8;', bad_alias)]
    edits.append(('main', '$x ?int = 7; $alias =& $x; { $x int = 1; unset($alias); } return 8;', bad_alias))
    guarded = """
        interface Marker {}
        class Marked extends Node implements Marker {}
        function accept(Node&Marker $n): int { return $n->value; }
        function check(?Node $n): int {
            if ($n instanceof Marker) { return accept($n); }
            return 1;
        }
        return check(new Marked());
    """
    edits.extend([('main', guarded, b''),
                  ('main', guarded.replace('return accept', 'unset($n); return accept'), 'statically satisfy')])
    for declaration in ['$x = 8;', '$x result<int> = 1;', '$x = function (int $i): int { return $i; };', '$x mixed = [];', '$x dynamic = {};', '$x vector<int> = [];',
                        '$x = new Node();', 'struct Point { public int $x; } $x Point;']:
        edits.append(('main', declaration + ' unset($x); return 8;', bad_type))
    edits.append(('main', main, b''))
    revisions(work, 'php', {'node': node, 'factory': factory, 'main': main}, edits)
    guard(work, 'php', 'stale-presence', node + """
        function readNode(?Node $v): int {
            if ($v !== null) { unset($v); return $v->value; }
            return 1;
        }
        return readNode(new Node());
    """, 'ownership_empty_access')


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-unset-') as directory:
        prove(Path(directory))
