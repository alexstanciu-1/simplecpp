"""Conditional PHP hash insertion uses a saved key and the current local hash."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


MAIN = """
function keyName(string &$name):string { echo "K"; return $name; }
function fail():int { echo "F"; throw new Exception("T"); }
function insert(hash<int,string> &$h, string &$name):int {
    $h[$name] = 4;
    $name = "changed";
    return 7;
}
function replace(hash<int,string> &$h):int {
    $other hash<int,string> = [];
    $other["keep"] = 3;
    $h = $other;
    return 9;
}
$h hash<int,string> = [];
$h["zero"] = 0;
$name = "zero";
$h[keyName($name)] ??= fail();
echo $h["zero"];
$name = "missing";
try { $h[keyName($name)] ??= fail(); }
catch (Exception $e) { echo $e->get_message(), count($h); }
$h[keyName($name)] ??= insert($h, $name);
echo $h["missing"], $name;
$h["last"] ??= replace($h);
echo count($h), $h["keep"], $h["last"];
$people = people();
$people["one"] ??= new Person(8);
echo $people["one"]->number;
return 8;
"""
MODEL = """
class Person {
    public int $number;
    public function __construct(int $n) { $this->number = $n; }
}
function people():hash<?Person,string> {
    $people hash<?Person,string> = [];
    $people["one"] = null;
    return $people;
}
"""

if __name__ == "__main__":
    with TemporaryDirectory(prefix="scpp-coalesce-hash-") as directory:
        required = MODEL.replace("?Person", "Person").replace("= null;", "= new Person(1);")
        prefix = b"K0KFT1K7changed239"
        revisions(Path(directory), "php", {"main": MAIN, "model": MODEL}, [
            (None, None, prefix + b"8"),
            ("model", required, prefix + b"1"),
            ("model", MODEL.replace("?Person", "int").replace("= null;", "= 0;"), "conversion"),
            ("model", MODEL, prefix + b"8"),
        ])
