"""Keyed literal construction preserves bytes, copies record values and rejects duplicate keys."""
from pathlib import Path
import tempfile
from collection_goal_native import guard, revisions

API = """
struct Point { public int $x = 7; }
function key():string { echo "K"; return "a\\0b"; }
function value():int { echo "V"; return 2; }
"""
ENTRY = """
$p Point;
$table hash<Point,string> = ["x" => $p];
$nums hash<int,string> = [key() => value(), "a" => 4];
$p->x = 9;
echo $table["x"]->x, $nums["a\\0b"], $nums["a"];
return 8;
"""

if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        work = Path(tmp)
        revisions(work, 'php', dict(main=ENTRY, api=API), [(None, None, b'KV724')])
        guard(work, 'php', 'duplicate', API + '$n hash<int,string> = [key()=>value(),key()=>value()]; return 8;',
              'duplicate_initializer_key', expected_stdout=b'KVKV')
