"""PHP nullable instance field conditional assignment, ownership and retained edits."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


MODEL = """
class Holder {
    public ?int $value;
    public function __construct(?int $initial) { $this->value = $initial; }
    public function __destruct() { echo "D", ($this->value ?? 99); }
}
"""
MAIN = """
function fail():int { throw new Exception("unexpected fallback"); }
function replaceOwner(Holder &$holder):int {
    $holder = new Holder(90);
    echo "R";
    return 7;
}
function selected(Holder $holder):Holder { echo "S"; return $holder; }
function writeThenThrow(Holder $holder):int {
    $holder->value = 12;
    throw new Exception("T");
}
function writeThenReturn(Holder $holder):int {
    $holder->value = 2;
    return 3;
}
$holder = new Holder(0);
selected($holder)->value ??= fail();
echo (int)$holder->value;
$holder = new Holder(null);
$holder->value ??= replaceOwner($holder);
echo "E", (int)$holder->value;
$holder = new Holder(null);
try { $holder->value ??= writeThenThrow($holder); }
catch (Exception $e) { echo $e->get_message(); }
echo (int)$holder->value;
$holder->value = null;
$holder->value ??= writeThenReturn($holder);
echo (int)$holder->value;
return 8;
"""

PRESENT = """
$holder = new Holder(0);
$holder->value ??= 8;
echo (int)$holder->value;
return 8;
"""

if __name__ == "__main__":
    with TemporaryDirectory(prefix="scpp-coalesce-field-") as directory:
        expected = b"S0D0RD7E90D90T123D3"
        revisions(Path(directory), "php", {"model": MODEL, "main": MAIN}, [
            (None, None, expected),
            ("main", PRESENT, b"0D0"),
            ("model", MODEL.replace("public ?int", "public readonly ?int"), "readonly"),
            ("model", MODEL, b"0D0"),
        ])
