"""Carrier subject evaluation, exact payload labels, and native default/fallthrough."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    functions = '''function text(?string $v):?string { echo "T"; return $v; }
function countResult(int $v):result<int> { echo "R"; return $v; }
function pick(?string $s):string {
    return match(text($s)) { "" => "empty", "a", "abc" => "abc", default => "miss" };
}
function writeText(?string $s):void {
    switch(text($s)) {
        case "": echo "E"; break;
        default: echo "D";
        case "abc": echo "A"; break;
    }
}
function number(int $n):string {
    return match(countResult($n)) { 0 => "zero", -2, -1 => "negative", default => "other" };
}
function writeNumber(int $n):void {
    switch(countResult($n)) { case 0: echo "Z"; break; default: echo "D"; case -1: echo "N"; }
}
'''
    functions += '''enum Mode { case Read=1; case Write=2; }
function enumPick(?Mode $mode):int { return match($mode) { Mode::Write, Mode::Read => 1, default => 2 }; }
class Box { public ?string $content = "field"; }
function box():Box { echo "B"; return new Box(); }
'''
    main = '''echo pick(""), pick(null), pick("abc"); writeText(""); writeText(null);
echo number(0), number(-1), number(4); writeNumber(0); writeNumber(4);
switch(text(null)) { case "": echo "wrong"; }
switch(text(null)) { default: echo "d"; }
echo match(countResult(0)) { default => "m" };
echo enumPick(Mode::Read), enumPick(null);
echo match(box()->content) { "field" => "F", default => "wrong" }; return 8;'''
    expected = b'TemptyTmissTabcTETDARzeroRnegativeRotherRZRDNTTdRm12BF'
    revisions(work, 'php', dict(api=functions, main=main), [
        (None, None, expected),
        ('api', functions.replace('0 => "zero"', '"" => "zero"'), 'Match label type must equal subject type'),
        ('api', functions.replace('function countResult(int $v):result<int>', 'function countResult(int $v):result_or_false<int>'), 'constant-label dispatch'),
        ('api', functions, expected),
    ])


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        prove(Path(tmp))
