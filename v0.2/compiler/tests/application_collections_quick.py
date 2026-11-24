"""Exercise application collection storage, signatures and edits in one native program."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


TYPES = """
struct Point { public int $x = 7; }
struct Bag { public vector<Point> $rows; public hash<Point,string> $lookup; }
"""
API = """
function copyRows(vector<Point> $rows): vector<Point> { return $rows; }
function update(vector<Point> &$rows): void {
    $item = $rows[0];
    $item->x += 2;
    $rows[0] = $item;
}
"""
MAIN = """
$bag Bag;
$item Point;
$bag->rows[] = $item;
$bag->lookup["first"] = $item;
$copy = copyRows($bag->rows);
update($copy);
foreach ($copy as $row) { echo $row->x, ":"; }
foreach ($bag->lookup as $key => $value) { echo $key, ":", $value->x, ":"; }
echo $bag->rows[0]->x, ":", (int)$bag->lookup->remove("first"), ":", count($bag->lookup);
return 8;
"""


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-application-collections-') as temporary:
        expected = b'9:first:7:7:1:0'
        revisions(Path(temporary), 'php', dict(main=MAIN, types=TYPES, api=API), [
            (None, None, expected),
            (None, None, expected),
            ('api', API.replace(' += 2', ' += "bad"'), None),
            ('api', API.replace(' += 2', ' += 3'), b'10:first:7:7:1:0'),
        ])
