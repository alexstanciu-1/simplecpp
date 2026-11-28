"""Boxed field conditional assignment, owner lifetime and readonly declaration edits."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

MODEL = """
class Holder {
    public BOX $data;
    public function __construct(BOX $initial) { $this->data = $initial; }
    public function __destruct() { echo "D", (int)$this->data["id"]; }
}
"""
MAIN = """
function rebind(Holder &$h):string {
    $next BOX = ["id" => 9]; $h = new Holder($next);
    echo "K"; return "id";
}
function replaceField(Holder $h):int {
    $fresh BOX = ["id" => 4, "keep" => 3]; $h->data = $fresh; echo "R"; return 7;
}
$empty BOX = [];
$h = new Holder($empty);
$h->data[rebind($h)] ??= 6;
echo "E", (int)$h->data["id"];
$old = $h->data;
$h->data["saved"] ??= replaceField($h);
echo (int)$h->data["saved"], (int)$h->data["keep"], (int)$old["id"], count($old);
return 8;
"""
PROBE = """
$initial BOX = ["id" => 2];
$h = new Holder($initial);
$alias = $h->data;
$h->data["saved"] ??= 7;
echo (int)$h->data["saved"], count($alias);
return 8;
"""

if __name__ == "__main__":
    case = sys.argv[1]
    with TemporaryDirectory(prefix="scpp-box-field-") as tmp:
        work = Path(tmp)
        if case == "null_dynamic":
            src = """
class Holder { public dynamic $data; }
function keyName():string { echo "K"; return "x"; }
function fill():int { echo "F"; return 3; }
$h = new Holder(); $h->data[keyName()] ??= fill(); return 8;
"""
            guard(work, "php", case, src, "boxed_expected_container", expected_stdout=b"K")
        else:
            model = MODEL.replace("BOX", case)
            main = MAIN.replace("BOX", case)
            probe = PROBE.replace("BOX", case)
            if case == "mixed":
                main = main.replace("return 8;", """
$blank = new Holder(null);
$blank->data["id"] ??= 5;
echo (int)$blank->data["id"];
return 8;
""")
            expected = b"72D2" if case == "dynamic" else b"71D2"
            revisions(work, "php", {"model": model, "main": main}, [
                (None, None, b"KD6E9R73915D5D4" if case == "mixed" else b"KD6E9R7391D4"),
                ("main", probe, expected),
                ("model", model.replace("public " + case, "public readonly " + case),
                 expected if case == "dynamic" else "readonly"),
                ("model", model, expected),
            ])
