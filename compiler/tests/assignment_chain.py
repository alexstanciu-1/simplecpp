"""PHP and C++ chain writes observe the stored value, including across a foreign RHS signature edit."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions

flavor = sys.argv[1]
if flavor == 'cpp':
    signature = 'int compute(bool fail)'
    flag = 'fail'
    bad_signature = 'string compute(bool fail)'
    caller = """
int a=1; int b=2; int c=3;
try { a=b=c=compute(true); } catch (Exception e) { echo a,b,c; }
a=b=c=compute(false);
echo a,b,c;
mixed source=9; dynamic outer=0;
outer=b=source;
echo (int)outer,b;
byte small=4; mixed bridge=0;
a=bridge=small;
echo a;
return 8;
"""
    direct_byte = ('a=bridge=small;', 'a=small;')
    chain, inner_missing, outer_missing = 'outer=b=source;', 'outer=missing=source;', 'missing=b=source;'
else:
    assert flavor == 'php'
    signature, bad_signature = 'function compute(bool $fail):int', 'function compute(bool $fail):string'
    flag = '$fail'
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
    direct_byte = ('$a=$bridge=$byte;', '$a=$byte;')
    chain, inner_missing, outer_missing = '$outer=$b=$source;', '$outer=$missing=$source;', '$missing=$b=$source;'

helper = f'''{signature} {{
    echo "R";
    if ({flag}) {{ throw new Exception("failed"); }}
    return 7;
}}
'''
expected = b'R123R777994'
with TemporaryDirectory(prefix=f'{flavor}-chain-') as folder:
    revisions(Path(folder), flavor, {'main': caller, 'helper': helper}, [
        (None, None, expected),
        ('helper', helper.replace(signature, bad_signature).replace('return 7;', 'return "wrong";'), 'conversion from string to int'),
        ('helper', helper, expected),
        ('main', caller.replace(*direct_byte), 'conversion from uint8 to int64'),
        ('main', caller.replace(chain, inner_missing), 'requires an existing local'),
        ('main', caller.replace(chain, outer_missing), 'requires an existing local'),
    ])
