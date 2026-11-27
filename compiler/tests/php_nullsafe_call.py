"""Nullsafe PHP method calls bind normally and stage arguments only for a present owner."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def named(work):
    player = """
        class Player {
            public function score(int $left,int $right):int {echo "M";return $left*10+$right;}
            public function bonus(int $n):nullable<int> {if ($n) {return 0;} return null;}
        }
    """
    main = """
        function key(int $n):int {echo $n;return $n;}
        function fail():int {echo "X";throw Exception::make("arg");}
        function pick(?Player $p):?Player {echo "P";return $p;}
        function check(?Player $p):void {
            echo pick($p)?->score(right:key(2),left:key(1)) ?? 9;
            echo $p?->bonus(0) ?? 9,$p?->bonus(1) ?? 9;
        }
        $empty ?Player=null;$empty?->score(fail(),fail());
        check(null);check(new Player());
        $present ?Player=new Player();
        try {$present?->score(fail(),key(4));} catch (Exception $e) {echo "C";}
        return 8;
    """
    trace = b'P999P21M1290XC'
    revisions(work, 'php', dict(main=main, player=player), [
        (None, None, trace), ('player', player.replace('$right', '$offset'), 'Unknown named argument: right'),
        ('player', player, trace)])


def chain(work):
    team = """
        class Team {
            public ?Player $captain;
            public function select(int $n):?Player {echo "S";return $this->captain;}
        }
    """
    main = """
        class Player {
            public int $points;
            public function newPlayer():Player {echo "I";return new Player();}
            public function score(int $n):int {echo "M";return $this->points;}
        }
        function probe(?Team $t):?Team {echo "P";return $t;}
        function arg(int $n):int {echo $n;return $n;}
        function check(?Team $t):void {
            echo probe($t)?->select(arg(1))?->score(arg(2)) ?? 9;
            echo $t?->captain?->points ?? 9;
            $t?->captain?->newPlayer()?->score(arg(3));
        }
        check(null);$t=new Team();check($t);
        $t->captain=new Player();check($t);return 8;
    """
    trace = b'P99P1S99P1S2M00I3M'
    scalar = team.replace(':?Player {echo "S";return $this->captain;', ':int {return 0;')
    revisions(work, 'php', dict(main=main, team=team), [
        (None, None, trace), ('team', scalar, 'unknown method score on nullable<int64>'), ('team', team, trace)])


PLAYERS = """
    class Player {
        public int $points;
        public function score(int $n):int {echo "M";return $this->points;}
    }
    class Other {}
"""
TEAM = """
    class Team {
        public ?Player $captain;
        public function pick(int $n):?Player {echo "S";return $this->captain;}
    }
"""
PROBES = """
    function probe(?Team $t):?Team {echo "P";return $t;}
    function key():int {echo "K";return 1;}
"""


def ordinary_suffix(work):
    main = PROBES + """
        function check(?Team $t):void {
            echo probe($t)?->pick(key())->score(key()) ?? 9;
            echo probe($t)?->captain->points ?? 9;
        }
        check(null);$t=new Team();$t->captain=new Player();check($t);return 8;
    """
    trace = b'P9P9PKSKM0P0'
    changed = TEAM.replace(':?Player {echo "S";return $this->captain;', ':?Other {return null;')
    revisions(work, 'php', dict(main=main, team=TEAM, players=PLAYERS), [
        (None, None, trace), ('team', changed, 'Unknown method Other::score'), ('team', TEAM, trace)])


def suspended_suffix(work):
    main = PROBES + """
        async function keyLater():int {await async_sleep_ms(1);return key();}
        async function check(?Team $t):int {
            return probe($t)?->pick(await keyLater())->score(await keyLater()) ?? 9;
        }
        echo await check(null);$t=new Team();$t->captain=new Player();echo await check($t);return 8;
    """
    revisions(work, 'php', dict(main=main, team=TEAM, players=PLAYERS), [(None, None, b'P9PKSKM0')])


def empty_intermediate(work):
    setup = PLAYERS + TEAM + PROBES + '$t=new Team();'
    for name, access, trace in [('field', 'captain->points', b'P'), ('method', 'pick(key())->score(key())', b'PKS')]:
        guard(work, 'php', name, setup + 'echo probe($t)?->' + access + ' ?? 9;return 8;',
              'ownership_empty_access', expected_stdout=trace)


def dispatch(work):
    main = """
        class Base {public virtual function score(int $left):int {return $left;}}
        class Player extends Base {public override function score(int $n):int {return $n+3;}}
        function read(?Base $p):int {return $p?->score(left:4) ?? 0;}
        echo read(null),read(new Player());return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'07')])


def admission(work):
    main = '$p ?Player=null;$n=0;$p?->score($n);return 8;'
    method = 'class Player {public function score(int &$n):int {return $n;}}'
    denied = 'Nullsafe call requires an instance method returning a value'
    revisions(work, 'php', dict(main=main, player=method), [
        ('player', method.replace('function score', 'final function &score'), denied),
        ('player', method.replace(':int {return $n;', ':void {return;'), 'Void call cannot be used as a value'),
        ('player', method, b'')])


def lifetime(work):
    main = """
        class Player {
            public int $n;
            public function __construct(int $n) {$this->n=$n;}
            public function score(int $i):int {echo "M",$this->n;return $i+$this->n;}
            public function __destruct() {echo "D",$this->n;}
        }
        function swap(?Player &$p):int {$p=new Player(90);echo "R";return 2;}
        $p ?Player=new Player(3);
        $r=$p?->score(swap($p));echo "E",$r ?? 0,"/",$p->n;return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'RM3D3E5/90D90')])


def suspended(work):
    main = """
        class Player {public function score(int $n):int {echo "M";return $n;}}
        async function key():int {await async_sleep_ms(1);echo "K";return 7;}
        async function run(?Player $p):int {$r=$p?->score(await key());return $r ?? 9;}
        echo await run(null),await run(new Player());return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'9KM7')])


def indexed_callable(work, case):
    api = """
    class Selector {
        public function pick(string $key):string { echo "M"; return $key; }
    }
    function probe(?Selector $s):?Selector { echo "P"; return $s; }
    function keyArg():string { echo "K"; return "method"; }
    function fallbackKey():string { echo "F"; return "fallback"; }
    function callArg():int { echo "C"; return 6; }
    function byFallback(int $x):int { echo "B"; return $x+20; }
    function byMethod(int $x):int { echo "A"; return $x+10; }
    """
    setup = '$ops hash<function<int(int)>,string> = ["fallback"=>fn($n)=>byFallback($n)];'
    method = '$ops["method"]=fn($n)=>byMethod($n);'
    call = '$value=$ops[probe($selector)?->pick(keyArg()) ?? fallbackKey()](callArg()); echo "|",$value;return 8;'
    present = '$selector ?Selector=new Selector();'
    absent = '$selector ?Selector=null;'
    if case == 'indexed_callable_missing':
        guard(work, 'php', 'missing', api + setup + present + call, 'hash_missing_key',
              exception_type='std::out_of_range', expected_stdout=b'PKM')
        return
    revisions(work, 'php', dict(api=api, main=setup + method + absent + call), [
        (None, None, b'PFCB|26'),
        ('main', setup + method + present + call, b'PKMCA|16'),
    ])


with TemporaryDirectory(prefix='php-nullsafe-call-') as tmp:
    case = sys.argv[1]
    if case in ('indexed_callable', 'indexed_callable_missing'):
        indexed_callable(Path(tmp), case)
    else:
        {'chain': chain, 'named': named, 'lifetime': lifetime, 'suspended': suspended,
         'dispatch': dispatch, 'admission': admission, 'ordinary_suffix': ordinary_suffix,
         'empty_intermediate': empty_intermediate, 'suspended_suffix': suspended_suffix}[case](Path(tmp))
