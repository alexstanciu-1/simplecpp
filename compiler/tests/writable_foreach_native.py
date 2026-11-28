"""Writable traversal updates selected vector and hash entries immediately."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


provider = '''
function observe(const vector<int> &$v,int $k):int {return $v[$k];}
function &selected(int &$n):int {return $n;}
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
        ('$aliasRef =& selected($n);', 'cannot bind a reference'),
        ('$a =& $n;', 'Cannot alias'),
        ('$f function<void()> = function() use (&$n):void {};', 'requires an inferred local closure'),
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

hash_provider = '''
function observe(const hash<int,string> &$map,string $key):int {return $map[$key];}
'''
hash_main = '''
function early():hash<int,string> {
    $map hash<int,string> = [];$map["one"]=1;
    foreach ($map as &$value) {$value=9;return $map;}
    return $map;
}
$map hash<int,string> = [];$alias =& $map;
$map["a"]=1;$map["b"]=2;$map["c"]=3;$map->remove("b");$map["b"]=4;
$other hash<int,int> = [];$other[17]=0;
foreach ($map as $key => &$value) {
    $saved=$key;$key="unused";
    foreach ($other as &$inner) {$inner++;$value+=10;}
    echo $saved,observe($alias,$saved),";";
}
foreach ($map as &$value) {$value=7;break;}
try {foreach ($map as &$value) {$value++;throw new Exception("stop");}}
catch (Exception $e) {echo $map["a"];}
$result=early();echo $result["one"],$other[17];return 8;
'''
hash_trace = b'a11;c13;b14;893'
with TemporaryDirectory(prefix='scpp-writable-hash-') as directory:
    edits = [(None, None, hash_trace)]
    for write in ['$map["new"]=1;', '$alias[$key]=1;', '$alias[$key]+=1;',
                  '$alias[$key]++;', '$alias->remove($key);', 'clean($alias);']:
        edits.append(('main', hash_main.replace('$saved=$key;', write), 'during writable foreach'))
    edits += [('main', hash_main, hash_trace),
              ('provider', hash_provider.replace('const hash', 'hash'), 'during writable foreach'),
              ('provider', hash_provider, hash_trace)]
    revisions(Path(directory), 'php', dict(main=hash_main, provider=hash_provider), edits)

parameter_provider = '''
function doubled(vector<int> $items):vector<int> {
    foreach ($items as &$n) {$n*=2;}
    return $items;
}
function bumped(hash<int,string> $map):hash<int,string> {
    foreach ($map as $key => &$n) {$n+=10;echo $map[$key],";";}
    return $map;
}
'''
parameter_main = '''
$v vector<int> = [2,3];$h hash<int,string> = [];$h["x"]=4;$h["y"]=5;
$out=doubled($v);$updated=bumped($h);
echo $out[0],$out[1],":",$v[0],$v[1],":",$updated["x"],$updated["y"],":",$h["x"],$h["y"];
return 8;
'''
parameter_trace = b'14;15;46:23:1415:45'
with TemporaryDirectory(prefix='scpp-writable-foreach-parameters-') as directory:
    edits = [(None, None, parameter_trace)]
    for written, changed, error in [
        ('hash<int,string> $map', 'const hash<int,string> &$map', 'read-only reference'),
        ('$n*=2;', '$items[]=9;', 'during writable foreach'),
        ('$n+=10;', '$map[$key]=1;', 'during writable foreach'),
    ]:
        edits.extend([('provider', parameter_provider.replace(written, changed), error),
                      ('provider', parameter_provider, parameter_trace)])
    revisions(Path(directory), 'php', dict(main=parameter_main, provider=parameter_provider), edits)

pack_provider = """
function doubled(int ...$items):vector<int> {
    foreach ($items as &$item) {$item*=2;}
    return $items;
}
"""
pack_main = """
$values vector<int> = [2,3];
$result=doubled(...$values);$direct=doubled(5);$empty=doubled();
echo $result[0],",",$result[1],";",$values[0],",",$values[1],";",$direct[0],";",count($empty);
return 8;
"""
pack_trace = b'4,6;2,3;10;0'
with TemporaryDirectory(prefix='scpp-writable-foreach-pack-') as directory:
    revisions(Path(directory), 'php', dict(main=pack_main, provider=pack_provider), [
        (None, None, pack_trace),
        ('provider', pack_provider.replace('int ...', 'int &...'), 'read-only variadic pack'),
        ('provider', pack_provider, pack_trace),
    ])

reference_provider = """
function clearValues(vector<int> &$v):void {clean($v);}
function readSize(const vector<int> &$v):int {return count($v);}
function run(callback<void> $cb):void {$cb();}
function visit(vector<int> &$items,vector<int> &$peer,vector<int> $copy,callback<void> $cb):void {
    $alias =& $peer;
    $clear=function() use (&$alias):void {clean($alias);};
    foreach ($items as $k => &$item) {
        $item+=3;echo $alias[$k],readSize($peer);
        clean($copy);run($cb);
    }
    $clear();
}
function bump(hash<int,string> &$h,hash<int,string> &$peer):void {
    foreach ($h as $key => &$value) {$value++;echo $peer[$key];}
}
"""
reference_main = """
function noop():void {}
$v vector<int> = [1,2];visit($v,$v,$v,noop(...));echo count($v);
$lambda=function(vector<int> &$rows) use (&$v):void {
    foreach ($rows as &$row) {$row+=1;echo count($v);}
};
$v[]=8;$lambda($v);echo $v[0];
$h hash<int,string> = [];$h["a"]=5;bump($h,$h);echo $h["a"];return 8;
"""
reference_trace = b'425201966'
with TemporaryDirectory(prefix='scpp-writable-foreach-reference-') as directory:
    edits = [(None, None, reference_trace)]
    for change in ['clean($alias);', '$peer[]=7;', '$peer=$copy;', 'clearValues($peer);',
                   '$clear();', 'run(function() use (&$peer):void {clean($peer);});',
                   'foreach ($peer as &$second) {$second=0;}']:
        edits.append(('provider', reference_provider.replace('clean($copy);', change), 'during writable foreach'))
    edits += [
        ('provider', reference_provider.replace('echo $peer[$key];', '$peer[$key]=9;'), 'during writable foreach'),
        ('provider', reference_provider.replace('vector<int> $copy', 'vector<int> &$copy'), 'during writable foreach'),
        ('provider', reference_provider, reference_trace),
        ('main', reference_main.replace('noop(...)', 'function() use (&$v):void {clean($v);}'), 'capture-free'),
        ('main', reference_main.replace('echo count($v);}', 'clean($v);}'), 'during writable foreach'),
        ('main', reference_main, reference_trace),
    ]
    revisions(Path(directory), 'php', dict(main=reference_main, provider=reference_provider), edits)

# The selected key (7), advanced physical cursor (2), and visible key (41)
# name different entries or positions after deletion of the first entry.
hole_main = """
$map hash<int,int> = [];$map[13]=9;$map[7]=3;$map[41]=80;
$map->remove(13);
foreach ($map as $key => &$value) {
    $key=41;$value=$value+4;
    $borrow=function() use (&$value):void {$value*=2;};
    $borrow();echo $map[7],",",$map[$key];break;
}
return 8;
"""
with TemporaryDirectory(prefix='scpp-writable-hash-hole-') as directory:
    revisions(Path(directory), 'php', dict(main=hole_main), [(None, None, b'14,80')])
