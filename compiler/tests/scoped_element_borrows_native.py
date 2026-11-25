"""Scoped mutable helper arguments preserve selected collection storage through calls."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


provider = """
function mutate(int &$slot, int &$alias, callback<void,int&> $op):void {
    $slot=7;$op($alias);echo $slot;throw new Exception("stop");
}
function advance(int &$value):void {$value+=2;}
function &origin(int &$value):int {return $value;}
function clearBoth(int &$value,vector<int> &$owner):void {clean($owner);}
class Point {public int $x=1;}
function editPoint(Point &$point):void {$point=new Point();$point->x=12;}
function replaceText(string &$slot,function<void(string&)> $op):void {$op($slot);$slot.="!";}
"""
main = """
$v vector<int> = [3];$h hash<int,int> = [];$h[1]=4;$h[5]=2;$h->remove(1);
foreach ($v as &$n) {
    try {mutate(alias:$n,slot:$n,op:advance(...));} catch (Exception $e) {echo $v[0];}
}
foreach ($h as $key => &$n) {
    $key=1;try {mutate($n,$n,advance(...));} catch (Exception $e) {echo $h[5];}
}
$p=new Point();$points vector<Point> = [$p];
foreach ($points as &$point) {editPoint($point);}
$updated=$points[0];echo $updated->x,$p->x;
$text hash<string,int> = [];$text[3]="old";
$replacement="new heap-backed string inside the callback";
$op function<void(string&)> = function(&$slot) use ($replacement) {$slot=$replacement;};
foreach ($text as &$s) {replaceText($s,$op);}
echo $text[3];return 8;
"""
trace = b'9999121new heap-backed string inside the callback!'
site = 'mutate(alias:$n,slot:$n,op:advance(...));'
with TemporaryDirectory(prefix='scpp-scoped-element-borrows-') as work:
    edits = [(None, None, trace)]
    for call, diagnostic in [
        ('$alias =& origin($n);', 'cannot bind a reference parameter'),
        ('clearBoth($n,$v);', 'during writable foreach'),
        ('mutate($n,$n,function(int &$x) use (&$v):void {clean($v);});', 'during writable foreach'),
        ('$op=function(int &$x):void {$x++;};$op($n);', 'cannot bind a reference parameter'),
    ]:
        edits.append(('main', main.replace(site, call), diagnostic))
    edits.extend([
        ('main', main, trace),
        ('provider', provider.replace('int &$alias', 'int $alias'), trace.replace(b'9999', b'7777')),
        ('provider', provider.replace('int &$alias', 'string &$alias').replace('$op($alias);', '$op($slot);'), 'exact storage type'),
        ('provider', None, None),
        ('provider', provider, trace),
    ])
    revisions(Path(work), 'php', dict(main=main, provider=provider), edits)
