"""Unpacking binds an owned vector after the fixed prefix, including indirect calls."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


target = '''
function edit(int $prefix,int ...$items):int {
    echo $prefix;
    if (count($items) === 0) {return 0;}
    echo $items[0];$items[0]=99;$items[]=100;return count($items);
}
'''
source = '''
function prefix():int {echo "P";return 1;}
function make_items():vector<int> {echo "V";$xs vector<int> = [7];return $xs;}
function through(callback<int,int,int...> $op,vector<int> $xs):int {
    echo $op(prefix(),...make_items());return $op(2,...$xs)+$xs[0];
}
$xs vector<int> = [3];$empty vector<int> = [];
echo edit(prefix(),...make_items()),":",edit(2,...$xs),$xs[0],":",edit(0,...$empty),":";
echo through(edit(...),$xs),":";
$f function<int(int,int...)> = edit(...);
echo $f(prefix(),...make_items()),":",$f(2,...$xs),$xs[0],":";
$count=fn(int ...$items):int=>count($items);echo $count(...$empty),$count(...$xs);
class Holder {public function<int(int...)> $op;}
function replace_op(Holder $h):vector<int> {
    $h->op=fn(...$xs)=>count($xs)+10;
    $out vector<int> = [8];echo "R";return $out;
}
$h=new Holder();$h->op=fn(...$xs)=>$xs[0];
echo ":",($h->op)(...replace_op($h)),":",($h->op)(...$xs);
return 8;
'''
expected = b'PV172:2323:00:PV172235:PV172:2323:01:R8:11'
with TemporaryDirectory(prefix='scpp-variadic-unpack-') as directory:
    revisions(Path(directory), 'php', dict(main=source, target=target), [
        (None, None, expected),
        ('target', target.replace('int ...$items', 'vector<int> $items'), 'Unpacking requires'),
        ('target', target, expected),
        ('main', source.replace('edit(2,...$xs)', 'edit(2,...$xs[0])'), 'Unpacked argument requires'),
        ('main', source, expected),
    ])
