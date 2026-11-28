"""A PHP conditional vector write checks the saved index before and after fallback effects."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


INDEX = """
function indexOf(int $i):int { echo "I", $i; return $i; }
function fail():int { echo "F"; throw new Exception("unexpected"); }
"""
OWNERS = """
class Item {
    public int $n;
    public function __construct(int $n) { $this->n = $n; }
}
function replaceVector(vector<?Item> &$items, int &$index):Item {
    echo "R";
    $other vector<?Item> = [new Item(3), new Item(4)];
    $items = $other;
    $index = 0;
    return new Item(7);
}
function clearVector(vector<?Item> &$items):Item {
    clean($items);
    echo "C", count($items);
    return new Item(8);
}
"""
SUCCESS = """
$numbers vector<int> = [0];
$numbers[indexOf(0)] ??= fail();
echo $numbers[0];
$items vector<?Item> = [new Item(5), null];
$i = 1;
$items[indexOf($i)] ??= replaceVector($items, $i);
echo $i, $items[0]->n, $items[1]->n;
return 8;
"""

if __name__ == "__main__":
    case = sys.argv[1]
    with TemporaryDirectory(prefix="scpp-vector-coalesce-") as directory:
        work = Path(directory)
        if case == "replace":
            revisions(work, "php", {"main": INDEX + OWNERS + SUCCESS}, [(None, None, b"I00I1R037")])
        else:
            if case == "clear":
                program = OWNERS + "$items vector<?Item> = [null]; $items[indexOf(0)] ??= clearVector($items);"
                trace = b"I0C0"
            else:
                i = {"negative": -1, "end": 1, "empty": 0}[case]
                initial = "[]" if case == "empty" else "[0]"
                program = f"$v vector<int> = {initial}; $v[indexOf({i})] ??= fail();"
                trace = f"I{i}".encode()
            guard(work, "php", case, INDEX + program + "return 8;", "sequence_index_out_of_range",
                  expected_stdout=trace, exception_type="std::out_of_range")
