"""Scoped helper reference arguments preserve selected collection storage through calls."""
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

row_helpers = """
function width(const vector<int> &$row):int {return count($row);}
function grow(const vector<int> &$view,vector<int> &$slot):int {
    $slot[]=8;return width($view);
}
"""
rows = """
$rows vector<vector<int>> = [[1,2],[3]];
foreach ($rows as &$row) {echo width($row),grow($row,$row),";";}
$map hash<vector<int>,int> = [];$map[2]=[1];$map[7]=[3,4];$map->remove(2);
foreach ($map as $key => &$row) {echo $key,":",grow($row,$row);}
return 8;
"""
with TemporaryDirectory(prefix='scpp-readonly-element-borrows-') as work:
    shared = b'23;12;7:3'
    revisions(Path(work), 'php', dict(main=rows, provider=row_helpers), [
        (None, None, shared),
        ('provider', row_helpers.replace('const vector<int> &$view', 'vector<int> $view'), b'22;11;7:2'),
        ('provider', row_helpers.replace('$slot[]=8;', '$view[]=8;'), 'read-only reference'),
        ('provider', None, None),
        ('provider', row_helpers, shared),
    ])

record = 'struct Sample {public int $x=1; public int $y=2;}'
field_writes = """
function nextValue():int {echo "R";return 12;}
$initial Sample;$points vector<Sample> = [$initial];
foreach ($points as &$point) {
    $before=$point;$point->x=nextValue();$after=$points[0];echo $before->x,",",$after->x,",",$after->y,";";
}
$map hash<Sample,int> = [];$map[1]=$initial;$map[9]=$initial;$map->remove(1);
foreach ($map as $k => &$point) {
    $k=1;$before=$point;$point->x=nextValue();$after=$map[9];echo $before->x,",",$after->x,",",$after->y;
}
return 8;
"""
with TemporaryDirectory(prefix='scpp-selected-record-field-') as work:
    visible = b'R1,12,2;R1,12,2'
    revisions(Path(work), 'php', dict(main=field_writes, record=record), [
        (None, None, visible),
        ('record', record.replace('public int $x', 'public readonly int $x'), 'Cannot modify readonly property'),
        ('record', record.replace('public int $x', 'private int $x'), 'Cannot access private field'),
        ('record', None, None),
        ('record', record, visible),
        ('main', field_writes.replace('$point->x=nextValue();', '$point->x+=1;'), 'Assign a whole writable foreach element'),
        ('main', field_writes, visible),
    ])
