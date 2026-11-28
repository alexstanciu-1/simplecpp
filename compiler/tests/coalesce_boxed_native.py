"""PHP single-key boxed conditional writes distinguish null from falsey values."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

HELPERS = """
function keyName(string $s):string { echo "K", $s; return $s; }
function fill(int $n):int { echo "F", $n; return $n; }
function fail():int { echo "X"; throw new Exception("fallback"); }
"""
ENTRIES = """
function replaceBox(BOX &$box):int {
    $fresh BOX = ["sibling" => 3, "saved" => 4];
    $box = $fresh;
    return 6;
}
$box BOX = [];
$box[keyName("missing")] ??= fill(7);
$box["nil"] = null;
$box[keyName("nil")] ??= fill(8);
$box["0"] = 0;
$box["false"] = false;
$box["empty"] = "";
$box[keyName("0")] ??= fail();
$box[keyName("false")] ??= fail();
$box[keyName("empty")] ??= fail();
echo (int)$box["missing"], (int)$box["nil"], (int)$box[0], count($box);
$box[keyName("saved")] ??= replaceBox($box);
echo (int)$box["sibling"], (int)$box["saved"], count($box);
"""
BOOTSTRAP = """
$blank mixed = null;
try { $blank[keyName("later")] ??= fail(); }
catch (Exception $e) { echo is_null($blank) ? "N" : "bad"; }
$blank[keyName("01")] ??= fill(9);
echo (int)$blank["01"], count($blank);
"""

if __name__ == "__main__":
    case = sys.argv[1]
    with TemporaryDirectory(prefix="scpp-coalesce-boxed-") as directory:
        work = Path(directory)
        if case == "null_dynamic":
            guard(work, "php", case, HELPERS + '$box dynamic = null; $box[keyName("n")] ??= fill(9); return 8;',
                  "boxed_expected_container", expected_stdout=b"Kn", exception_type="std::runtime_error")
        else:
            main = HELPERS + ENTRIES.replace("BOX", case)
            expected = b"KmissingF7KnilF8K0KfalseKempty7805Ksaved362"
            if case == "mixed":
                main += BOOTSTRAP
                expected += b"KlaterXNK01F991"
            revisions(work, "php", {"main": main + "return 8;"}, [(None, None, expected)])
