"""Two-key conditional stores probe and recheck the current parent using saved keys."""
import json
from pathlib import Path
import subprocess
from tempfile import TemporaryDirectory

from app_namespace_native import invoke, service
from native_build import write_build

SOURCE = """
function indexKey():int { echo "I"; return 0; }
function textKey():string { echo "K"; return "k"; }
function fail():int { echo "F"; throw new Exception("unexpected fallback"); }
function replaceRow(vector<hash<int,string>> &$rows):int {
    echo "R"; $new hash<int,string> = ["sibling" => 4, "k" => 8]; $rows[0] = $new; return 7;
}
class Item { public int $n; public function __construct(int $n) { $this->n = $n; } }
class Grid {
    public hash<vector<?Item>,string> $rows;
    public function __construct(?Item $x) { $this->rows["k"] = [$x]; }
    public function __destruct() { echo "D", $this->rows["k"][0]->n; }
}
function rebind(Grid &$grid):string { echo "B"; $grid = new Grid(new Item(90)); return "k"; }
function fill():Item { echo "F"; return new Item(6); }
function dropRow(hash<vector<?Item>,string> &$rows):Item { echo "R"; $rows->remove("k"); return new Item(7); }
function replaceItems(hash<vector<?Item>,string> &$rows, bool $short):Item {
    echo "R"; $rows->remove("k");
    $new vector<?Item> = []; if (!$short) { $new[] = new Item(2); $new[] = new Item(8); }
    $rows["k"] = $new; return new Item(1);
}
class Table { public vector<hash<int,string>> $rows; }
function clearRows(Table $t):int { echo "C"; $t->rows = []; return 7; }
$values hash<vector<int>,string> = [];
$rows vector<hash<int,string>> = [];
$t = new Table();
if (cli_argc() === 2) { $values[textKey()][indexKey()] ??= fail(); return 91; }
if (cli_argc() === 3) { $t->rows[indexKey()][textKey()] ??= fail(); return 92; }
$empty hash<int,string> = [];
$t->rows[] = $empty;
if (cli_argc() === 4) { $t->rows[indexKey()][textKey()] ??= clearRows($t); return 93; }
$slots hash<vector<?Item>,string> = []; $slots["k"] = [null];
if (cli_argc() === 6) { $slots[textKey()][indexKey()] ??= replaceItems($slots, true); return 95; }
if (cli_argc() === 5) {
    $slots[textKey()][indexKey()] ??= dropRow($slots); return 94;
}
$slots[textKey()][indexKey()] ??= replaceItems($slots, false); echo $slots["k"][0]->n, $slots["k"][1]->n;
$values["k"] = [0];
$values[textKey()][indexKey()] ??= fail(); echo $values["k"][0];
$rows[] = $empty;
$rows[indexKey()][textKey()] ??= replaceRow($rows); echo $rows[0]["sibling"], $rows[0]["k"];
$grid = new Grid(null);
$grid->rows[rebind($grid)][indexKey()] ??= fill(); echo $grid->rows["k"][0]->n;
return 0;
"""

with TemporaryDirectory(prefix="scpp-nested-coalesce-") as temp:
    work = Path(temp)
    app, out = work / "app", work / "out"
    app.mkdir()
    (app / "module.json").write_text("{}")
    (app / "main.php").write_text(SOURCE)
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert "error" not in result, result
    finally:
        proc.stdin.close()
        proc.wait()
    manifest = json.loads((out / "manifest.json").read_text())
    subprocess.run(write_build(work, [out / file for file in manifest["translation_units"]]), cwd=work, check=True)
    for extra, trace, error in [
        (0, b"KIR18KI0IKR47BIFD690D90", b""),
        (1, b"KI", b"hash_missing_key\n"),
        (2, b"IK", b"sequence_index_out_of_range\n"),
        (3, b"IKC", b"sequence_index_out_of_range\n"),
        (4, b"KIR", b"hash_missing_key\n"),
        (5, b"KIR", b"sequence_index_out_of_range\n"),
    ]:
        result = subprocess.run([work / "program", *(["x"] * extra)], capture_output=True)
        assert (result.returncode, result.stdout, result.stderr) == (int(bool(error)), trace, error), result
