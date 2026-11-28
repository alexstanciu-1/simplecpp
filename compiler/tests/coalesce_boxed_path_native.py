"""Same-family conditional paths resolve the saved keys again after fallback effects."""
import json
from pathlib import Path
import subprocess
import sys
from tempfile import TemporaryDirectory

from app_namespace_native import invoke, service
from native_build import write_build

SOURCE = """
function keyName(string $s):string { echo $s; return $s; }
function fill():int { echo "F"; return 7; }
function fail():int { echo "T"; throw new Exception("stop"); }
function change(BOX &$root, bool $drop):int {
    echo "R"; $new BOX = ["keep" => 9, "C" => 4];
    if ($drop) { $root->remove("A"); } else { $root["A"]["B"] = $new; }
    return 8;
}
function initial():BOX {
    $leaf BOX = ["zero" => 0, "nil" => null];
    $middle BOX = ["B" => $leaf]; $outer BOX = ["A" => $middle];
    return $outer;
}
function selected(BOX $root):BOX { $middle = $root["A"]; return $middle["B"]; }
class Holder {
    public BOX $data;
    public function __construct() { $this->data = initial(); }
    public function __destruct() { $leaf = selected($this->data); echo "D", count($leaf); }
}
function rebind(Holder &$h):string { echo "H"; $h = new Holder(); return "C"; }
$root = initial(); $alias = selected($root); $copy = $root;
if (cli_argc() === 2) { $root->remove("A"); }
if (cli_argc() === 3) { $root["A"]["B"] = null; }
if (cli_argc() === 4) { $root["A"] = 3; }
if (cli_argc() === 5) { $root = null; }
if (cli_argc() === 6) {
    $root FIRST SECOND LAST ??= change($root, true); return 90;
}
$root FIRST SECOND LAST ??= fill();
$root FIRST SECOND LAST ??= fail();
$root["A"]["B"]["zero"] ??= fail();
$root["A"]["B"]["nil"] ??= fill();
try { $root["A"]["B"]["bad"] ??= fail(); }
catch (Exception $e) { $leaf = selected($root); echo count($leaf); }
$leaf = selected($root); $prior = selected($copy);
echo (int)$leaf["C"], (int)$leaf["nil"], count($alias), count($prior);
$root FIRST SECOND ["new"] ??= change($root, false);
$leaf = selected($root); echo (int)$leaf["keep"], (int)$leaf["new"];
$h = new Holder();
$h->data FIRST SECOND REBIND ??= fill();
echo "E"; return 0;
"""

if __name__ == "__main__":
    family = sys.argv[1]
    dynamic = family == "dynamic"
    source = SOURCE.replace("BOX", family)
    for token, expr, computed in [
        ("FIRST", 'keyName("A")', dynamic), ("SECOND", 'keyName("B")', False),
        ("LAST", 'keyName("C")', dynamic), ("REBIND", 'rebind($h)', dynamic),
    ]:
        source = source.replace(token, "->{" + expr + "}" if computed else "[" + expr + "]")
    with TemporaryDirectory(prefix="scpp-boxed-path-") as tmp:
        work = Path(tmp)
        app, out = work / "app", work / "out"
        app.mkdir()
        (app / "module.json").write_text("{}")
        (app / "main.php").write_text(source)
        proc = service(app, out)
        try:
            result = invoke(proc)
            assert "error" not in result, result
        finally:
            proc.stdin.close()
            proc.wait()
        manifest = json.loads((out / "manifest.json").read_text())
        subprocess.run(write_build(work, [out / unit for unit in manifest["translation_units"]]), cwd=work, check=True)
        aliases = b"33" if dynamic else b"22"
        for args, trace, error in [
            (0, b"ABCFABCFT377" + aliases + b"ABR98ABHFD3ED2", b""),
            (1, b"ABC", b"boxed_missing_key\n"),
            (2, b"ABC", b"boxed_expected_container\n"),
            (3, b"ABC", b"boxed_expected_container\n"),
            (4, b"ABC", b"boxed_expected_container\n"),
            (5, b"ABCR", b"boxed_missing_key\n"),
        ]:
            result = subprocess.run([work / "program", *(["x"] * args)], capture_output=True)
            assert (result.stdout, result.stderr, result.returncode) == (trace, error, int(bool(error))), result
