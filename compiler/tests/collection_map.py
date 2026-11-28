"""PHP single-input map: result types, entry order, provider selection and failed builds."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


source = """
struct Item {public int $n; public function __construct(int $n){$this->n=$n;}}
function makeItem(int $n):Item {$item Item($n);return $item;}
function label(int $n):string {echo $n; return $n===2 ? "two" : "one";}
$v vector<int>=[1,2];
$words vector<string>=array_map(label(...),$v);
if(count($words)!==2 || $words[0]!=="one" || $words[1]!=="two"){return 1;}
$records=array_map(makeItem(...),$v);
$again=array_map(fn($item):string=>label($item->n),$records);
if($again[0]!=="one" || $again[1]!=="two"){return 9;}
$h hash<int,int>=[19=>2,2=>1];
$ints hash<string,int>=array_map(label(...),$h);
foreach($ints as $k=>$word){echo "/",$k,$word;}
$text hash<int,string>=["05"=>2,"5"=>1];
$items hash<Item,string>=array_map(makeItem(...),$text);
$numbers hash<int,string>=array_map(fn($item):int=>$item->n,$items);
if(count($numbers)!==2 || $numbers["05"]!==2 || $numbers["5"]!==1){return 10;}
$strings hash<string,string>=array_map(label(...),$text);
foreach($strings as $k=>$word){echo "/",$k,$word;}
$bytes fixed_array<byte,3>=[2,1,2];
$third vector<string>=array_map(fn(byte $x):string=>label((int)$x),$bytes);
if(count($third)!==3 || $third[0]!=="two" || $third[1]!=="one" || $third[2]!=="two"){return 2;}
$empty fixed_array<byte,0>=[];
$none function<string(byte)>;
if(count(array_map($none,$empty))!==0){return 3;}
$m mixed=[];$m[19]=1;$m["name"]=2;
$copy=array_map(fn(mixed $x):mixed=>$x+1,$m);
if(count($copy)!==2 || (int)$copy[19]!==2 || (int)$copy["name"]!==3){return 4;}
$d dynamic={};$child dynamic={};$child["n"]=1;$d["child"]=$child;
$dc=array_map(fn(dynamic $x):dynamic=>$x,$d);
$alias=$dc["child"];$alias["n"]=7;$dc->clear();
if(count($d)!==1 || (int)$child["n"]!==7){return 5;}
try {
    $words=array_map(function(int $n):string {if($n===2){throw new Exception("stop");}return "changed";},$v);
    return 6;
} catch(Exception $e) { if($words[0]!=="one" || $words[1]!=="two"){return 7;} }
return 8;
"""

if __name__ == '__main__':
    with TemporaryDirectory(prefix='collection-map-') as directory:
        unsupported = '$h hash<int,string>=["k"=>1]; $r=array_map(fn(int $n):byte=>(byte)$n,$h); return 8;'
        revisions(Path(directory), 'php', {'main': source}, [
            ('main', '$a fixed_array<fixed_array<byte,2>,2>; $r=array_map(fn($row):string=>"x",$a); return 8;',
             'Unavailable mapped result builder'),
            ('main', unsupported, 'Unavailable mapped result builder for array_map'),
            ('main', source, b'121221/19two/2one21/05two/5one212')])
        generated = ''.join(p.read_text() for p in (Path(directory) / 'php-out').rglob('*.cpp'))
        assert generated.count('::scpp::detail::map_entries<') == source.count('array_map(')
