"""Indexed writes are immediate; an active vector cannot be structurally invalidated."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


provider = '''
function observe(const vector<int> &$v,int $k):int {return $v[$k];}
function touch(int &$n):void {$n=0;}
function copy_size(vector<int> $v):int {clean($v);return count($v);}
'''
main = '''
function early():vector<int> {
    $v vector<int> = [4,5];
    foreach ($v as &$n) {$n=7;return $v;}
    return $v;
}
$v vector<int> = [1,2,3];$alias =& $v;
$clear=function() use (&$alias):void {clean($alias);};
foreach ($v as $k => &$n) {
    $n+=10;echo observe($v,$k);
    $alias[$k]=20;echo $n;
    $f=fn():int=>$n;
    $n++;echo $f(),$v[$k],copy_size($alias),";";
    if ($k===0) {continue;}break;
}
echo $v[2],":";
try {foreach ($v as &$n) {$n=8;throw new Exception("stop");}}
catch (Exception $e) {echo $v[0];}
$other=early();echo $other[0],$other[1];
$flags vector<bool> = [false];foreach ($flags as &$flag) {$flag=true;}
if ($flags[0]) {echo "B";}
$clear();echo count($v);return 8;
'''
trace = b'112020210;122020210;3:875B0'

with TemporaryDirectory(prefix='scpp-writable-foreach-') as directory:
    edits = [(None, None, trace)]
    for write, message in [
        ('$alias[]=1;', 'during writable foreach'),
        ('clean($v);', 'during writable foreach'),
        ('$v=$alias;', 'during writable foreach'),
        ('$alias->remove(0);', 'during writable foreach'),
        ('$v->reserve(100);', 'during writable foreach'),
        ('$clear();', 'during writable foreach'),
        ('touch($n);', 'cannot bind a reference'),
        ('$a =& $n;', 'Cannot alias'),
        ('$f=function() use (&$n):void {};', 'Cannot capture'),
    ]:
        edits.append(('main', main.replace('$n+=10;', write), message))
    edits += [
        ('main', main, trace),
        ('provider', provider.replace('const vector<int> &$v', 'vector<int> &$v'), 'during writable foreach'),
        ('provider', provider, trace),
    ]
    revisions(Path(directory), 'php', dict(main=main, provider=provider), edits)

records = '''
struct Point {public int $x=1;}
class Node {public int $n=0;}
$p Point;$points vector<Point> = [$p];
foreach ($points as &$point) {$copy=$point;$copy->x=8;$point=$copy;}
$out=$points[0];echo $out->x,$p->x;
$node=new Node();$nodes vector<Node> = [$node];
foreach ($nodes as &$entry) {$entry->n=4;$entry=new Node();}
$replacement=$nodes[0];echo $node->n,$replacement->n;
$labels vector<string> = ["a","b"];
$nums vector<int> = [0];
foreach ($labels as $i => &$label) {
    $i=10;
    foreach ($nums as &$num) {$num++;$label.="x";}
}
echo $labels[0],$labels[1],$nums[0];
$empty vector<int> = [];foreach ($empty as &$unused) {return 1;}
return 8;
'''
with TemporaryDirectory(prefix='scpp-writable-foreach-values-') as directory:
    revisions(Path(directory), 'php', dict(main=records), [(None, None, b'8140axbx2')])
