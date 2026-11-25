"""Direct PHP closure reference returns, caller storage, and incremental origin checks."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    leaf = 'function &selectSlot(int &$slot):int {echo "S";return $slot;}'
    arrow = 'fn&(int &$slot):int => selectSlot($slot)'
    block = 'function &(int &$slot):int {$alias =& $slot;return $alias;}'
    main = f"""
        $forward={arrow};$select={block};$n=3;
        $alias =& $forward($n);$copy=$select($n);$alias=9;
        $again =& $select($n);$again=12;echo $copy,":",$n;return 8;
    """
    result = b'S3:12'
    origin = 'Reference return must originate in its writable parameter'
    recurse = 'function helper(int &$n):int {$f=function &(int &$x):int {helper($x);return $x;};'
    revisions(work, 'php', dict(main=main, leaf=leaf), [
        (None, None, result), (None, None, result),
        ('leaf', leaf.replace('&selectSlot', 'selectSlot'), origin),
        ('leaf', None, 'Unknown function'), ('leaf', leaf, result),
        ('main', main.replace('$alias =& $slot;', '$alias=1;'), origin),
        ('main', '$n=1;$f=fn&(int &$x):int=>$n;return 8;', origin),
        ('main', '$n=1;$f=function &(int &$x) use (&$n):int {$a =& $n;return $a;};return 8;', origin),
        ('main', main.replace('int &$slot', 'int &$slot,int &$other', 1), 'exactly one writable parameter'),
        ('main', '$f function<int(int&)> = fn&(int &$x):int=>$x;return 8;', 'Expected exact callable signature'),
        ('main', 'function takeOp(callback<int,int&> $op):void {}takeOp(fn&(int &$x):int=>$x);return 8;', 'Expected exact callback signature'),
        ('main', recurse + 'return $f($n);}return 8;', 'Recursive reference return is unsupported'),
        ('main', recurse + 'return $n;}return 8;', b''),
        ('main', """
            function mark(int $x):int {echo $x;return $x;}
            function &forward(string &$value):string {
                $op=function &(int $a,string &$slot,int $b):string {return $slot;};
                return $op(b:mark(2),slot:$value,a:mark(1));
            }
            $s="old";$copy=forward($s);$alias =& forward($s);
            $alias="new";echo $copy,":",$s;return 8;
        """, b'2121old:new'),
        ('main', main, result),
    ])


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-closure-ref-results-') as directory:
        prove(Path(directory))
