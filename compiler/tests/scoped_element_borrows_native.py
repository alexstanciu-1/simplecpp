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

capture_loop = """
function invoke(callback<void> $fn):void {$fn();}
function store(function<void()> $fn):void {}
function bump(int &$n):void {$n+=2;}
function &refValue(int &$n):int {return $n;}
$ints vector<int> = [3];
foreach ($ints as &$n) {
    bump($n);$snap=fn():int=>$n;
    $add=function() use (&$n):void {bump($n);};
    $ints[0]=11;$add();$add();echo $snap(),":",$ints[0],";";
}
$p Sample;$v vector<Sample> = [$p,$p];$h hash<Sample,int> = [];
$h[2]=$p;$h[5]=$p;$h->remove(2);
foreach ($v as $i => &$point) {
    $f=function() use (&$point):void {$point->x=12;};
    $f();$copy=$v[$i];echo $copy->x;
    if ($i===0) {continue;}break;
}
try {
    foreach ($h as $k => &$point) {
        $f=function() use (&$point):void {$point->x=20;throw new Exception("stop");};
        $k=2;$f();
    }
} catch (Exception $e) {$copy=$h[5];echo $copy->x;}
return 8;
"""
with TemporaryDirectory(prefix='scpp-selected-element-capture-') as work:
    trace = b'5:15;121220'
    edits = [(None, None, trace)]
    for operation in ['$point->x+=2;', '$point->x++;']:
        for outside in [False, True]:
            invalid = (capture_loop.replace('$f();$copy=', operation+'$copy=') if outside else
                       capture_loop.replace('$point->x=12;', operation))
            edits.append(('main', invalid, 'Assign a whole writable foreach element'))
    for call in ['bump($n);$snap', '{bump($n);}']:
        edits.append(('main', capture_loop.replace(call, call.replace('bump($n)', 'refValue($n)')), 'cannot bind a reference parameter'))
    for replacement, error in [
        ('$bad=function() use (&$v):void {clean($v);};', 'during writable foreach'),
        ('invoke($f);', 'direct local invocation'),
        ('store($f);', 'direct local invocation'),
        ('$alias =& $f;', 'direct local invocation'),
        ('invoke(function() use (&$point):void {});', 'requires an inferred local closure'),
        ('$nested=function() use (&$point):void {$g=function() use ($point):void {};};', 'Nested by-value capture'),
    ]:
        edits.append(('main', capture_loop.replace('$f();$copy=', replacement+'$copy='), error))
    edits += [
        ('main', capture_loop.replace('return 8;', '$f();return 8;'), None),
        ('main', '''function escaped(vector<int> &$rows):function<void()> {
            foreach ($rows as &$row) {
                $op=function() use (&$row):void {$row++;};return $op;
            }
            return function():void {};
        }return 8;''', 'direct local invocation'),
        ('main', capture_loop, trace),
        ('record', record.replace('public int $x', 'public readonly int $x'), 'Cannot modify readonly property'),
        ('record', None, None),
        ('record', record, trace),
    ]
    revisions(Path(work), 'php', dict(main=capture_loop, record=record), edits)

nested_loop = """
function invoke(callback<void> $op):void {$op();}
function store(function<void()> $op):void {}
function update(vector<Sample> &$rows,vector<Sample> &$alias):void {
    foreach ($rows as $key => &$point) {
        $outer=function() use (&$point):void {
            $inner=function() use (&$point):void {$point->x=12;};
            $inner();
        };
        $key=1;$outer();
        break;
    }
}
$p Sample;$v vector<Sample> = [$p,$p];update($v,$v);
$first=$v[0];$second=$v[1];echo $first->x,",",$second->x,";";
$h hash<Sample,int> = [];$h[2]=$p;$h[5]=$p;
try {
    foreach ($h as $key => &$point) {
        $outer=function() use (&$point):void {
            $inner=function() use (&$point):void {$point->x=20;throw new Exception("stop");};
            $inner();
        };
        $key=5;$outer();
    }
} catch (Exception $e) {$first=$h[2];$second=$h[5];echo $first->x,",",$second->x;}
return 8;
"""
with TemporaryDirectory(prefix='scpp-nested-element-borrow-') as work:
    expected = b'12,1;20,1'
    edits = [(None, None, expected)]
    for call in ('invoke($inner);', 'store($inner);', '$copy=$inner;', '$saved function<void()> = $inner;'):
        edits.append(('main', nested_loop.replace('$inner();', call, 1), 'direct local invocation'))
    edits += [
        ('main', nested_loop.replace('$inner();', 'return $inner;', 1), 'direct local invocation'),
        ('main', nested_loop.replace('$inner=function() use (&$point):void {$point->x=12;};',
                                     'invoke(function() use (&$point):void {$point->x=12;});'), 'requires an inferred local closure'),
        ('main', nested_loop.replace('$outer=function() use (&$point)', '$outer=function() use (&$point,&$alias)', 1)
                            .replace('$inner=function() use (&$point):void {$point->x=12;',
                                     '$inner=function() use (&$point,&$alias):void {clean($alias);$point->x=12;', 1),
         'during writable foreach'),
        ('main', nested_loop, expected),
        ('record', record.replace('public int $x', 'public readonly int $x'), 'Cannot modify readonly property'),
        ('record', None, None),
        ('record', record, expected),
    ]
    revisions(Path(work), 'php', dict(main=nested_loop, record=record), edits)

consumer = 'function applyTwice(callback_view<void> $op):void {applyOnce($op);applyOnce(action:$op);}'
leaf = 'function applyOnce(callback_view<void> $action):void {$action();}'
view_loop = """
function &keep(int &$n):int {
    $f=function() use (&$n):void {$n+=1;};applyTwice($f);return $n;
}
$counter=0;$ref =& keep($counter);echo $ref,";";
function update(vector<int> &$rows,vector<int> &$alias):void {
    $clear=function() use (&$alias):void {clean($alias);};
    foreach ($rows as $key => &$n) {
        $step=function() use (&$n):void {$n+=2;};
        $key=1;applyTwice($step);break;
    }
}
$v vector<int> = [4,3];update($v,$v);echo $v[0],",",$v[1],";";
$h hash<int,int> = [];$h[2]=5;$h[8]=1;
try {
    foreach ($h as $key => &$n) {
        $step=function() use (&$n):void {$n+=1;if ($n===7) {throw new Exception("stop");}};
        $key=8;applyTwice(op:$step);
    }
} catch (Exception $e) {echo "caught:";}
echo $h[2],",",$h[8];return 8;
"""
with TemporaryDirectory(prefix='scpp-borrowed-callback-consumer-') as work:
    expected = b'2;8,3;caught:7,1'
    edits = [(None, None, expected),
             ('leaf', leaf.replace('$action();', '$saved function<void()> = $action;'), 'only direct invocation'),
             ('leaf', leaf, expected),
             ('main', view_loop.replace('applyTwice($step)', 'applyTwice($clear)'), 'during writable foreach'),
             ('main', view_loop.replace('applyTwice($step)', 'applyTwice(function() use (&$n):void {})'), 'named local closure'),
             ('main', view_loop, expected),
             ('consumer', consumer.replace('callback_view<void>', 'callback<void>'), 'direct local invocation'),
             ('consumer', None, None),
             ('consumer', consumer, expected)]
    revisions(Path(work), 'php', dict(main=view_loop, consumer=consumer, leaf=leaf), edits)
