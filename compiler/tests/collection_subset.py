"""Preserve sparse positions, typed and boxed keys, and result ownership across providers."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


source = """
class Grid { public fixed_array<fixed_array<byte,3>,1> $rows; }
function row():int {echo "K";return 0;}
function keep(int $x):bool {echo $x,"/";return $x===30;}
$v vector<int>=[10,20,30];
$r=array_filter($v,keep(...));
if(count($r)!==1 || $r[2]!==30){return 1;}
$bytes fixed_array<byte,3>=[0,0,30];
$b=array_filter($bytes,fn(byte $x):bool=>(int)$x===30);
if(count($b)!==1 || (int)$b[2]!==30){return 2;}
$grid=new Grid(); $grid->rows[0][2]=30; $calls=0;
$selected=array_filter($grid->rows[row()],function(byte $x) use (&$grid,&$calls):bool {
    $calls++; $grid=new Grid(); return (int)$x===30;
});
if($calls!==3 || count($selected)!==1 || (int)$selected[2]!==30){return 6;}
try {
    $r=array_filter($v,function(int $x):bool {if($x===20){throw new Exception("stop");} return true;});
    return 7;
} catch(Exception $e) { if(count($r)!==1 || $r[2]!==30){return 9;} }
$blank fixed_array<byte,0> = [];
if(count(array_filter($blank,fn(byte $x):bool=>true))!==0){return 3;}
$h hash<int,string>=["03"=>0,"05"=>1,"02"=>2];
foreach(array_filter($h) as $k=>$n){echo $k,"/";}
$m mixed=[];$m["child"]=7;$m[2]=1;
$mc=array_filter($m,fn(mixed $x):bool=>(int)$x===7);
$mc["child"]=8;
if(count($mc)!==1 || (int)$m["child"]!==7){return 4;}
$d dynamic={};$inner dynamic={};$inner["n"]=1;$d["child"]=$inner;
$dc=array_filter($d,fn(dynamic $x):bool=>true);
$alias=$dc["child"];$alias["n"]=9;$dc->clear();
if(count($d)!==1 || (int)$inner["n"]!==9){return 5;}
echo $r[2],"/",(int)$b[2];return 8;
"""

if __name__ == '__main__':
    with TemporaryDirectory(prefix='collection-subset-') as directory:
        bad = 'function f(int $x):int{return $x;} $v vector<int>=[1];$r=array_filter($v,f(...));return 8;'
        revisions(Path(directory), 'php', {'main': source}, [
            ('main', '$rows fixed_array<fixed_array<byte,2>,2>; $r=array_filter($rows); return 8;', 'Unavailable keyed filter result'),
            ('main', bad, 'Invalid callback signature'), ('main', source, b'10/20/30/K05/02/30/30')])
        generated = ''.join(p.read_text() for p in (Path(directory) / 'php-out').rglob('*.cpp'))
        assert generated.count('::scpp::detail::filter_entries<') == source.count('array_filter(')
