"""Run identical count and value-search calls over three provider contracts."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


source = """
function row():int {echo "K";return 1;}
function strictFlag():bool {echo "S";return true;}
$needle byte = 7;
$v vector<int>=[7,8];$h hash<int,int>=[7=>8,3=>9];
$rows fixed_array<fixed_array<byte,2>,2>;
$rows[1][1]=7;
$empty fixed_array<byte,0> = [];
echo count($v),in_array(7,$v),count($h),in_array(7,$h),in_array(9,$h);
echo count($rows[row()]),in_array($needle,$rows[row()]),in_array($needle,$rows[1],strictFlag());
$zero byte=0;
echo count($empty),in_array($zero,$empty);
return 8;
"""

if __name__ == '__main__':
    with TemporaryDirectory(prefix='collection-value-scan-') as tmp:
        # The receiver and strict flag each run once; test them independently of native argument order.
        mismatch = source.replace('in_array($needle,$rows[row()])', 'in_array(7,$rows[row()])')
        with_box = source.replace('echo count($empty)', '$box mixed=[];in_array(7,$box);echo count($empty)')
        error = 'matching comparable scalar needle'
        revisions(Path(tmp), 'php', {'main': source}, [
            ('main', 'echo count(1);return 8;', 'supports counting'),
            ('main', mismatch, error), ('main', with_box, error), ('main', source, b'2121K2K1S10')])
