"""Whole-variable clean preserves copies, writes through references and recovers after type edits."""
from pathlib import Path
from tempfile import TemporaryDirectory

from application_vectors_native import measure
from collection_goal_native import revisions
from entry_probe import raw_entry


def prove(work):
    provider = 'function number(): int { return 19; }'
    main = """
        function resetMixed(mixed &$slot): void { clean($slot); }
        function resetDynamic(dynamic &$slot): void { clean($slot); }
        function cleanCopy(vector<int> $items): int { clean($items); return count($items); }
        $n = number();
        clean($n);
        if ($n !== 0) { return 1; }
        $small byte = 4;
        $alias =& $small;
        clean($alias);
        $flag = true;
        clean($flag);
        $f = 2.5;
        clean($f);
        $text = "saved";
        $oldText = $text;
        clean($text);
        if ((int)$small !== 0 || $flag || $f !== 0.0 || $text !== "" || $oldText !== "saved") { return 2; }
        $items vector<int> = [4];
        if (cleanCopy($items) !== 0) { return 3; }
        $oldItems = $items;
        $itemsAlias =& $items;
        clean($itemsAlias);
        $itemsAlias[] = 9;
        if ($items[0] !== 9 || $oldItems[0] !== 4) { return 4; }
        $map hash<int,string> = ["key" => 7];
        $oldMap = $map;
        clean($map);
        if (count($map) !== 0 || $oldMap["key"] !== 7) { return 5; }
        $m mixed = ["key" => 3];
        $oldMixed = $m;
        resetMixed($m);
        if ((int)$oldMixed["key"] !== 3) { return 6; }
        $d dynamic = {name: "Alex"};
        $oldDynamic = $d;
        $r =& $d;
        resetDynamic($r);
        clean($r);
        if ((string)$oldDynamic["name"] !== "Alex") { return 7; }
        $r = {name: "new"};
        if ((string)$d["name"] !== "new") { return 1; }
        return 8;
    """
    edits = [(None, None, b''), (None, None, b''),
             ('provider', provider.replace(': int', ': ?int'), 'Cannot clean storage of type'),
             ('provider', None, 'Unknown function'), ('provider', provider, b'')]
    for declaration in ['$x ?int = 7;', '$x result<int> = 7;', 'struct Box { public int $n; } $x Box;',
                        'class Node {} $x = new Node();', 'class Node {} $x ?Node = null;',
                        'class Node {} $n ?Node = new Node(); $x = weak($n);',
                        '$x = function (int $n): int { return $n; };']:
        edits.append(('main', declaration + ' clean($x); return 8;', 'Cannot clean storage of type'))
    edits.extend([('main', '$v vector<int> = [1]; clean($v[0]); return 8;', 'single whole-variable clean operand'),
                  ('main', 'clean($missing); return 8;', 'requires an existing local'),
                  ('main', main, b'')])
    revisions(work, 'php', {'main': main, 'provider': provider}, edits)

    # Observe the exact boxed tag through the native API.
    output = work / 'php-out'
    entry = output / 'entry.cpp'
    entry.write_text(raw_entry(entry.read_text()) + """
#include "scpp/dynamic.hpp"
#include "scpp/mixed.hpp"
namespace App { void resetMixed_(scpp::mixed&); void resetDynamic_(scpp::dynamic&); }
int main() {
    auto m = scpp::mixed::from_boolean(false);
    auto d = scpp::dynamic::container();
    App::resetMixed_(m);
    App::resetDynamic_(d);
    return m.kind() == scpp::boxed_kind::null && d.kind() == scpp::boxed_kind::null ? 8 : 1;
}
""")
    measure(output, work / 'boxed-tags', b'')


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-clean-') as directory:
        prove(Path(directory))
