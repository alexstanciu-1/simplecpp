"""PHP grouped locals and immediately invoked factories reuse callable contracts."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def selection(work):
    factory = 'function makeOp():function<int(int)> {echo "F";return fn($n)=>$n+2;}'
    main = """
        function invoke(callback<int,int> $f):int {return ($f)(6);}
        function plus(int $n):int {return $n+2;}
        function arg():int {echo "A";return 6;}
        $f function<int(int)> = fn($n)=>$n+1;
        $replace=function() use (&$f):int {$f=fn($n)=>$n+10;echo "R";return 6;};
        echo ($f)($replace()),":",($f)(6),":",makeOp()(arg());
        ($f)(6);makeOp()(6);
        $local=fn(int $n=4):int=>$n+1;echo ($local)(),($local)(n:6),invoke(plus(...));
        return 8;
    """
    trace = b'R7:16:FA8F578'
    revisions(work, 'php', dict(main=main, api=factory), [
        ('api', 'function makeOp():int {return 3;}', 'Invocation requires an owned callable'),
        ('api', factory, trace)])


def conditional(work, kind):
    api = """
        function left(int $x):int {echo "A";return $x+1;}
        function right(int $x):int {echo "B";return $x+2;}
        function newer(int $x):int {echo "N";return $x+10;}
        function condition(bool $choice):bool {echo "C";return $choice;}
        function arm(string $tag,function<int(int)> $op):function<int(int)> {echo $tag;return $op;}
    """
    def pick(a, b):
        return (f'(condition($choice) ? {a} : {b})' if kind == 'ternary' else
                f'(match(true) {{condition($choice)=>{a},default=>{b}}})')

    traced = pick('arm("L",$a)', 'arm("R",$b)')
    direct = pick('$a', '$b')
    main = """
        function run(bool $choice):void {
            $a function<int(int)>=left(...);$b function<int(int)>=right(...);
            $replace=function() use (&$a,&$b):int {echo "X";$a=newer(...);$b=newer(...);return 7;};
    """ + f'{traced}(7);echo {direct}($replace()),"/",{direct}(7);' + """
        }
        run(true);run(false);return 8;
    """
    trace = b'CLACXA8/CN17CRBCXB9/CN17'
    revisions(work, 'php', dict(main=main, api=api), [
        ('api', api.replace('):function<int(int)> {echo $tag;return $op;', '):int {echo $tag;return 3;'),
         'Invocation requires an owned callable'),
        ('api', api, trace)])


def references(work):
    factory = 'function picker():function<int&(int&)> {echo "F";return fn&(&$x)=>$x;}'
    main = """
        $n=2;$f function<int&(int&)> = fn&(&$x)=>$x;
        $a =& ($f)($n);$copy=($f)($n);unset($f);$a=7;
        $b =& picker()($n);$snapshot=picker()($n);$b=9;
        echo $copy,":",$snapshot,":",$n;return 8;
    """
    revisions(work, 'php', dict(main=main, api=factory), [
        ('api', factory.replace('int&(', 'int(').replace('fn&(', 'fn('), 'Local alias call requires a reference return'),
        ('api', factory, b'FF2:7:9')])


def arguments(work, callee):
    factory = 'function makeOp():function<int(int&)> {return fn(&$n)=>$n+2;}'
    setup = '$n=6;$f function<int(int&)> = makeOp();'
    edits = []
    for args, diagnostic in [('', 'Argument count mismatch'), ('2', 'stable variable storage'),
                                 ('n:$n', 'Typed callback calls require positional arguments')]:
        edits.append(('main', setup + callee + '(' + args + ');return 8;', diagnostic))
    revisions(work, 'php', dict(main=setup, api=factory), edits)


def suspended(work):
    main = """
        async function leaf():int {await async_sleep_ms(1);echo "L";return 2;}
        function makeOp(int $a,int $b):function<int(int,int)> {
            echo "F";return fn($x,$y)=>$x+$y+$a+$b;
        }
        async function run():int {return makeOp(await leaf(),await leaf())(await leaf(),await leaf());}
        echo await run();return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'LLFLL8')])


def absent(work):
    factory = 'function absent():function<int(int)> {echo "F";$f function<int(int)>;return $f;}'
    arg = 'function arg():int {echo "A";return 6;}'
    guard(work, 'php', 'absent', factory + arg + 'absent()(arg());return 8;', None,
          exception_type='std::bad_function_call', exception_header='functional', expected_stdout=b'FA')


with TemporaryDirectory(prefix='php-callable-expression-') as tmp:
    cases = {'selection': selection, 'references': references, 'local_arguments': lambda w: arguments(w, '($f)'),
             'factory_arguments': lambda w: arguments(w, 'makeOp()'), 'absent': absent, 'suspended': suspended}
    case = sys.argv[1]
    if case in ('ternary', 'match'):
        conditional(Path(tmp), case)
    else:
        cases[case](Path(tmp))
