"""PHP chain writes observe the stored value, including across a foreign RHS signature edit."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions

helper = '''
function compute(bool $fail):int {
    echo "R";
    if ($fail) { throw new Exception("failed"); }
    return 7;
}
'''
caller = '''
$a int=1; $b int=2; $c int=3;
try { $a=$b=$c=compute(true); } catch (Exception $e) { echo $a,$b,$c; }
$a=$b=$c=compute(false);
echo $a,$b,$c;
$source mixed=9; $outer dynamic=0;
$outer=$b=$source;
echo (int)$outer,$b;
$byte byte=4; $bridge mixed=0;
$a=$bridge=$byte;
echo $a;
return 8;
'''
expected = b'R123R777994'
with TemporaryDirectory(prefix='php-chain-') as folder:
    revisions(Path(folder), 'php', {'main': caller, 'helper': helper}, [
        (None, None, expected),
        ('helper', helper.replace(':int', ':string').replace('return 7;', 'return "wrong";'), 'conversion from string to int'),
        ('helper', helper, expected),
        ('main', caller.replace('$a=$bridge=$byte;', '$a=$byte;'), 'conversion from uint8 to int64'),
        ('main', caller.replace('$outer=$b=$source;', '$outer=$missing=$source;'), 'requires an existing local'),
        ('main', caller.replace('$outer=$b=$source;', '$missing=$b=$source;'), 'requires an existing local'),
    ])
