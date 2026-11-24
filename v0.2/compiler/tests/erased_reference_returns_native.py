"""Reference-result origin survives erasure, replacement and signature edits."""
from pathlib import Path
from tempfile import TemporaryDirectory

from app_namespace_native import invoke, service
from application_vectors_native import measure
from collection_goal_native import revisions


def prove(work):
    helper = 'function &pick(int &$x):int {echo "P";return $x;}'
    consumer = """
        function &relay(callback<int&,int&> $op,int &$n):int {return $op($n);}
        function test(callback<int&,int&> $op):int {
            $n=1;$a =& relay($op,$n);$copy=$op($n);$a=9;echo $copy,":",$n;return 0;
        }
    """
    main = """
        test(pick(...));
        $f function<int&(int&)> = fn&(&$x)=>$x;
        $n=3;$b =& $f($n);$copy=$f($n);unset($f);$b=12;echo ":",$copy,":",$n;
        $ops vector<function<int&(int&)>> = [fn&(&$x)=>$x];
        $c =& $ops[0]($n);$ops[0]=fn&(&$x)=>$x;$c=17;echo ":",$n;return 8;
    """
    trace = b'PP1:9:3:12:17'
    cycle = """
        function through(callback<int,int> $read):int {return $read(2);}
        function &root(int &$n):int {through(fn($x)=>bridge($x));return $n;}
        function bridge(int $v):int {return root($v);}
        return 8;
    """
    owned_cycle = cycle.replace('callback<int,int>', 'function<int(int)>')
    field = """
        class Box {public function<string&(string&,int)> $pick;}
        function mark(Box $b):int {$b->pick=fn&(&$s,$unused)=>$s;return 2;}
        $box=new Box();$label="first";
        $box->pick=function &(&$s,$unused) use ($label) {echo $label;return $s;};
        $s="old";$alias =& ($box->pick)($s,mark($box));$copy=($box->pick)($s,0);
        $alias="new";echo $copy,":",$s;return 8;
    """
    named_cycle = """
        function &recursive(int &$slot):int {return relay(recursive(...),$slot);}
        return 8;
    """
    stable_calls = """
        function via(callback<int,int> $op):int {return $op(2);}
        function &root(int &$slot):int {via(fn($n)=>$n);return $slot;}
        $n=7;echo root($n);return 8;
    """
    spare = 'function spare():void {$op=fn(int $n):int=>root($n);}'
    mismatch = 'Callback target signature mismatch'
    revisions(work, 'php', dict(main=main, helper=helper, consumer=consumer), [
        (None, None, trace), (None, None, trace),
        ('helper', helper.replace('&pick', 'pick'), mismatch),
        ('helper', None, 'Callback requires a source free function'),
        ('helper', helper, trace),
        ('consumer', consumer.replace('callback<int&,int&>', 'callback<int,int&>', 1),
         'Reference return must originate'),
        ('consumer', consumer, trace),
        ('main', main.replace('fn&(&$x)', 'fn(&$x)', 1), 'Expected exact callable signature'),
        ('main', main.replace('function<int&(int&)>', 'function<int&(int)>', 1), 'exactly one writable'),
        ('main', cycle, 'Recursive reference return'),
        ('main', owned_cycle, 'Recursive reference return'),
        ('main', cycle.replace('return root($v);', 'return $v;'), b''),
        ('main', named_cycle, 'Recursive reference return'),
        ('main', 'function readOp(callback<int&,int&,int> $op):void {}'
                 'readOp(fn&(int &$n,int $extra=1):int=>$n);return 8;', 'without parameter defaults'),
        ('main', field, b'firstold:new'),
        ('main', main, trace),
        ('helper', helper + cycle[:cycle.index('return 8;')], 'Recursive reference return'),
        ('helper', helper, trace),
        ('main', stable_calls, b'7'),
        ('helper', helper + spare, 'Recursive reference return'),
        (None, None, 'Recursive reference return'),
        ('helper', helper, b'7'),
    ])


def mixed_consumers(work):
    app = work / 'mixed'
    app.mkdir()
    (app / 'module.json').write_text('{"entry":"main.php"}')
    (app / 'main.php').write_text('function &pick(int &$x):int {$x+=1;return $x;}'
                                  'echo cppUse(pick(...)),":",jsUse(pick(...));return 8;')
    (app / 'cppUse.cpp').write_text('int cppUse(callback<int&,int&> op) {int x=1;return op(x);}')
    (app / 'jsUse.js').write_text('function jsUse(op:callback<int&,int&>):int {let x=3;return op(x);}')
    out = work / 'mixed-out'
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        measure(out, work / 'mixed-native', b'2:4')
    finally:
        proc.stdin.close()
        proc.wait()


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-erased-refs-') as directory:
        prove(Path(directory))
        mixed_consumers(Path(directory))
