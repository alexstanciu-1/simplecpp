"""Owned callable elements retain value semantics across indexed calls and incremental builds."""
import argparse
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def prove(work, quick=False):
    producer = """
        function makeOps(int $n):vector<function<int(int)>> {
            $ops vector<function<int(int)>> = [fn($x)=>$x+$n];return $ops;
        }
    """
    consumer = """
        function readHash(hash<function<int(int)>,string> $ops):int {return $ops["run"](2);}
        function runOps(vector<function<int(int)>> $ops):int {return $ops[0](2);}
        function grow(vector<function<int(int)>> &$ops):int {
            $ops[0]=fn($x)=>$x+10;
            for ($i=0;$i<32;$i++) {$ops[]=fn($x)=>$x;}
            return 2;
        }
        function key():string {echo "K";return "run";}
        function replaceOp(hash<function<int(int)>,string> &$h):int {
            $h["run"]=fn($x)=>$x+20;return 2;
        }
    """
    main = """
        $v=makeOps(3);$copy=$v;echo runOps($v),":",$v[0](grow($v)),":",$v[0](2),":",$copy[0](2);
        $h hash<function<int(int)>,string> = ["run"=>fn($x)=>$x+4];
        $saved=$h;echo ":",$h[key()](replaceOp($h)),":",$h["run"](2),":",readHash($saved);
        $absent function<int(int)>;$v[]=$absent;$h["absent"]=$absent;
        if (!isset($v[0]) || isset($v[count($v)-1]) || isset($v[50])) {return 1;}
        if (!isset($h[key()]) || isset($h["absent"]) || isset($h["missing"])) {return 2;}
        $nested vector<hash<function<int(int)>,string>> = [$h];
        if (isset($nested[9][key()]) || !isset($nested[0]["run"])) {return 3;}
        $saved["run"](0);echo ":",count($h);return 8;
    """
    trace = b'5:5:12:5:K6:22:6K:2'
    borrow = 'function(int $x) use (&$n):int {return $x+$n;}'
    if quick:
        source = producer + consumer + main
        revisions(work, 'php', dict(main=source), [
            (None, None, trace),
            ('main', source.replace('fn($x)=>$x+$n', borrow), 'cannot erase a borrowed environment'),
            ('main', producer + '$ops=makeOps(4);echo $ops[0](2);return 8;', b'6'),
        ])
        return
    revisions(work, 'php', dict(main=main, producer=producer, consumer=consumer), [
        (None, None, trace), (None, None, trace),
        ('main', """
            class Handler {public vector<function<int(Handler)>> $ops;}
            $h=new Handler();$h->ops[]=fn($item)=>7;echo $h->ops[0]($h);return 8;
        """, b'7'),
        ('main', main, trace),
        ('producer', producer.replace('fn($x)=>$x+$n', borrow), 'cannot erase a borrowed environment'),
        ('producer', producer, trace),
        ('main', main.replace('$saved=$h;', '$n=3;$h["run"]='+borrow+';$saved=$h;'), 'cannot erase a borrowed environment'),
        ('main', main, trace),
        ('producer', producer.replace('int(int)', 'int()').replace('fn($x)=>$x+$n', 'fn()=>$n'),
         'vector<function<int64()>> to vector<function<int64(int64)>>'),
        ('producer', producer, trace),
        ('consumer', consumer.replace('int(int)>> $ops', 'int()>> $ops').replace('$ops[0](2)', '$ops[0]()'),
         'vector<function<int64(int64)>> to vector<function<int64()>>'),
        ('consumer', consumer, trace),
        ('consumer', consumer.replace('int(int)>,string> $ops', 'int()>,string> $ops')
                             .replace('$ops["run"](2)', '$ops["run"]()'),
         'hash<function<int64(int64)>,string> to hash<function<int64()>,string>'),
        ('consumer', consumer, trace),
        ('main', """
            async function probeKey():int {await async_sleep_ms(1);echo "K";return 0;}
            async function check():int {
                $rows vector<vector<function<int(int)>>> = [[fn($x)=>$x+4]];
                if (isset($rows[2][await probeKey()])) {return 1;}
                if (!isset($rows[0][await probeKey()])) {return 2;}
                $row=$rows[0];foreach ($row as $op) {echo $op(2);}
                return 8;
            }
            return await check();
        """, b'K6'),
    ])
    guard(work, 'php', 'absent',
          '$empty function<int(int)>;$v vector<function<int(int)>> = [$empty];'
          'function arg():int {echo "A";return 2;}$v[0](arg());return 8;',
          expected_stdout=b'A', exception_type='std::bad_function_call', exception_header='functional')


def nullsafe_index(work, selection):
    api = """
    class Selector { public nullable<string> $key; }
    function probe(?Selector $s):?Selector { echo "P"; return $s; }
    function fallbackKey():string { echo "F"; return "run"; }
    function first(int $n):int {echo "A"; return $n+10;}
    function second(int $n):int {echo "B"; return $n+20;}
    function replaceSelected(hash<function<int(int)>,string> &$ops):int {
        echo "R"; $ops["run"]=fn($n)=>second($n); return 6;
    }
    """
    selector, trace = {
        'direct': ('$selector=new Selector(); $selector->key="run";', b'P'),
        'fallback': ('$selector ?Selector=null;', b'PF'),
    }[selection]
    setup = api + '$ops hash<function<int(int)>,string> = ["decoy"=>fn($n)=>0];' + selector
    call = '$ops[probe($selector)?->key ?? fallbackKey()](replaceSelected($ops))'
    present = setup + '$ops["run"]=fn($n)=>first($n); $pending=' + call + ';'
    present += '$next=$ops["run"](6); echo "|",$pending,"/",$next; return 8;'
    revisions(work, 'php', {'main': present}, [(None, None, trace + b'RAB|16/26')])
    guard(work, 'php', 'missing', setup + '$pending=' + call + '; return 0;', 'hash_missing_key',
          expected_stdout=trace, exception_type='std::out_of_range')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--quick', action='store_true')
    parser.add_argument('--nullsafe-index', choices=('direct', 'fallback'))
    args = parser.parse_args()
    with TemporaryDirectory(prefix='scpp-callable-collections-') as directory:
        if args.nullsafe_index:
            nullsafe_index(Path(directory), args.nullsafe_index)
        else:
            prove(Path(directory), args.quick)
