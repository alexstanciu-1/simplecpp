"""Indexed ??= holds the original receiver while probing and updating its current field."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


HASH = """
class Holder {
    public hash<int,string> $items;
    public function __construct(int $n) { $this->items = ["keep" => $n]; }
    public function __destruct() { echo "D", $this->items["keep"]; if (hash_contains($this->items, "new")) { echo "N", $this->items["new"]; } }
}
"""
HASH_MAIN = """
function keyName(Holder &$h):string { $h = new Holder(90); echo "K"; return "new"; }
function fail():int { throw new Exception("unexpected"); }
function replaceField(Holder $h):int {
    $h->items = ["keep" => 3, "new" => 4];
    echo "R";
    return 7;
}
$h = new Holder(0);
$h->items["keep"] ??= fail();
echo $h->items["keep"];
$h->items[keyName($h)] ??= 6;
echo "E", count($h->items);
$h->items["new"] ??= replaceField($h);
echo $h->items["keep"], $h->items["new"];
return 8;
"""

VECTOR = """
class Item { public int $n; public function __construct(int $n) { $this->n = $n; } }
class Holder {
    public vector<?Item> $items;
    public vector<int> $numbers;
    public function __construct(?Item $first) { $this->items = [$first]; $this->numbers = [0]; }
    public function __destruct() { echo "D", $this->items[0]->n; }
}
"""
VECTOR_MAIN = """
function fail():int { throw new Exception("unexpected"); }
function indexOf():int { echo "I"; return 0; }
function replaceOwner(Holder &$h):Item { $h = new Holder(new Item(90)); echo "R"; return new Item(7); }
function replaceField(Holder $h):Item {
    $h->items = [new Item(4), new Item(5)];
    return new Item(3);
}
$h = new Holder(null);
$h->numbers[0] ??= fail();
echo $h->numbers[0];
$h->items[indexOf()] ??= replaceOwner($h);
echo "E", $h->items[0]->n;
$h->items = [null];
$h->items[indexOf()] ??= replaceField($h);
echo $h->items[0]->n, $h->items[1]->n;
return 8;
"""

if __name__ == "__main__":
    if sys.argv[1] == "hash":
        model, main, expected = HASH, HASH_MAIN, b"0KD0N6E1R37D3N7"
        only_store = '$h = new Holder(0); $h->items["keep"] ??= 9; return 8;'
        present = b"D0"
    else:
        model, main, expected = VECTOR, VECTOR_MAIN, b"0IRD7E90I35D3"
        only_store = '$h = new Holder(new Item(0)); $h->items[0] ??= new Item(9); return 8;'
        present = b"D0"
    with TemporaryDirectory(prefix="scpp-collection-field-coalesce-") as directory:
        readonly = model.replace("public hash<", "public readonly hash<").replace("public vector<", "public readonly vector<", 1)
        revisions(Path(directory), "php", {"model": model, "main": main}, [
            (None, None, expected),
            ("main", only_store, present),
            ("model", readonly, "readonly"),
            ("model", model, present),
        ])
