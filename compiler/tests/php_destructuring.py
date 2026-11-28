"""Flat destructuring through prepared index reads and ordered local writes."""
from pathlib import Path
import re
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

helper = 'function pair(int &$calls):vector<int> {$calls+=1;return [11,22,33];}'
source = """
class Row {public vector<int> $values;}
function digits(vector<int> $values):Iterator<int> {
    [$first,$last]=$values; yield $first; yield $last;
}
function frame():int { $v fixed_array<byte,65536>; [$x,$y]=$v; return (int)$x+(int)$y; }
function stable(vector<int> &$input):int {
    $x=0; [$x,$y]=$input; return $x+$y;
}
function aliased(mixed &$src,mixed &$out):int {
    [$out,$tail]=$src; return (int)$tail;
}
if(frame()!==0){return 12;}
$row=new Row();$row->values=[2,3];[$left,$right]=$row->values;if($left+$right!==5){return 13;}
$n=0; [$x,$y]=pair($n);
if ($n!==1 || $x!==11 || $y!==22) {return 1;}
$v vector<int>=[4,6]; if(stable($v)!==10){return 2;}
$h hash<int,string>=["b"=>2,"a"=>0];
["a"=>$x,'b'=>$z,"b"=>$z,]=$h;
if($x!==0 || $z!==2){return 3;}
$numbers hash<string,int>=[1=>"one",0=>"zero",-2=>"neg"];
$zero="";[$zero,$one]=$numbers; [-2=>$neg]=$numbers;
if($zero!=="zero" || $one!=="one" || $neg!=="neg"){return 4;}
$m mixed=[7,8]; if(aliased($m,$m)!==8 || (int)$m!==7){return 5;}
$m=[3,4];[$m,$tail]=$m;if((int)$m!==3 || (int)$tail!==4){return 10;}
[$a,$b]=[1,2];if((int)$a!==1 || (int)$b!==2){return 11;}
$d dynamic=["a"=>false]; ["missing"=>$miss,"a"=>$hit]=$d;
if($miss!==null || (bool)$hit){return 6;}
if(true){[$x,$inner]=$v;} [$x,$x]=$v;
if($x!==6){return 7;}
$sum=0;foreach(digits($v) as $digit){$sum+=$digit;}if($sum!==10){return 9;}
return 8;
"""
with TemporaryDirectory(prefix='php-destructure-') as tmp:
    root = Path(tmp)
    edits = [(None, None, b'')]
    if '--quick' not in sys.argv:
        edits += [
            ('helper', None, 'Unknown function'),
            ('helper', helper, b''),
            ('main', source.replace('[$x,$y]=$input', '["s"=>$x]=$input'), 'index'),
            ('main', source.replace('[$x,$y]=$input', '[$x,"k"=>$y]=$input'), 'Cannot mix'),
            ('main', source.replace('[$x,$y]=$input', '[$x,$y]="abc"'), 'requires an indexed collection'),
            ('main', source.replace('return 8;', 'echo $inner; return 8;'), 'Unknown or not-yet-initialized'),
            ('main', source, b''),
        ]
    revisions(root, 'php', {'main': source, 'helper': helper}, edits)
    cpp = '\n'.join(p.read_text() for p in (root/'php-out').rglob('*.cpp'))
    assert re.search(r'const auto& (\w+) = var_input_\d+;\s+var_x_\d+ = \(\1\).at\(0L\);\s+\[\[maybe_unused\]\] scpp::int64 var_y_\d+ = \(\1\).at\(1L\);', cpp), cpp
    assert re.search(r'const auto& \w+ = var_numbers_\d+;', cpp)
    assert re.search(r'const auto& \w+ = \(var_row_\d+\)->values;', cpp)
    assert len(re.findall(r'pair_\(var_n_\d+\)', cpp)) == 1
    if '--quick' not in sys.argv:
        for label, setup, error in [
            ('short', '$v vector<int>=[11];', 'sequence_index_out_of_range'),
            ('conversion', '$v mixed=[11,"bad"];', 'boxed_scalar_kind'),
        ]:
            guard(root, 'php', label, setup + '''
$x=90;$y=80;
try {[$x,$y]=$v;} finally {echo $x,":",$y;}
return 8;''', error, expected_stdout=b'11:80')
