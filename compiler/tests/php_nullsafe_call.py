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


def terminal_index(work, case):
    vector = case != 'index_hash'
    strings = case == 'index_string_vector'
    element = 'string' if strings else 'int'
    collection = f'vector<{element}>' if vector else 'hash<int,string>'
    key = 'function key():int {echo "K";return 0;}' if vector else 'function key():string {echo "K";return "home";}'
    slot = '[]' if vector else '["home"]'
    stored = '"hit"' if strings else '0'
    fallback = '"absent"' if strings else '9'
    team = f'class Team {{public {collection} $scores; public function fill():void {{$this->scores{slot}={stored};}}}}'
    probe = 'function probe(?Team $t):?Team {echo "P";return $t;}'
    read = f'function read(?Team $t):nullable<{element}> {{return probe($t)?->scores[key()];}}'
    main = key + probe + read + f'echo read(null) ?? {fallback};$t=new Team();$t->fill();echo read($t) ?? {fallback};return 8;'
    trace = b'PabsentPKhit' if strings else b'P9PK0'
    grouped = main.replace('probe($t)?->scores[', '(probe($t)?->scores)[')
    diagnostic = ('Indexed read requires a vector, hash, boxed container or readable string' if strings else
                  'No optional representation for field type ' + ('vector<int64>' if vector else 'hash<int64,string>'))
    edits = [('main', grouped, diagnostic), ('main', main, trace)]
    if not strings:
        changed = team.replace('<int', '<string').replace('=0;', '="zero";')
        edits.extend([('team', changed, 'Unsupported explicit conversion from string to int64'), ('team', team, trace)])
    revisions(work, 'php', dict(main=main, team=team), edits)
    error = 'sequence_index_out_of_range' if vector else 'hash_missing_key'
    guard(work, 'php', 'missing', team + key + probe + read + f'echo read(new Team()) ?? {fallback};return 8;',
          error, exception_type='std::out_of_range', expected_stdout=b'PK')


def index_member(work, case):
    vector = case == 'index_member_vector'
    slot = '0' if vector else '"home"'
    collection = 'vector<?Player>' if vector else 'hash<?Player,string>'
    insert = '$this->players[]' if vector else '$this->players["home"]'
    model = f"""
        class Team {{
            public {collection} $players;
            public function addEmpty():void {{{insert}=null;}}
            public function put(int $n):void {{$p=new Player();$p->points=$n;$this->players[{slot}]=$p;}}
        }}
    """
    keys = ('function key():int' if vector else 'function key():string') + ' {echo "K";return ' + slot + ';}'
    readers = keys + """
        function probe(?Team $t):?Team {echo "P";return $t;}
        function swap(?Team $t):int {echo "A";$t->put(8);return 1;}
        function field(?Team $t):int {return probe($t)?->players[key()]?->points ?? 9;}
        function callPlayer(?Team $t):int {return probe($t)?->players[key()]?->score(swap($t)) ?? 9;}
    """
    types = PLAYERS.replace('public int $points;', 'public int $points; public function __destruct() {echo "D",$this->points;}')
    types = types.replace('class Other {}', 'class Other {public int $points;}')
    main = readers + """
        echo field(null),callPlayer(null);
        $t=new Team();$t->addEmpty();echo field($t),callPlayer($t);
        $t->put(0);echo field($t),callPlayer($t),field($t);return 8;
    """
    trace = b'P9P9PK9PK9PK0PKAMD00PK8D8'
    revisions(work, 'php', dict(main=main, team=model, players=types), [
        (None, None, trace), ('team', model.replace('Player', 'Other'), 'Unknown method Other::score'),
        ('team', model, trace)])
    error = 'sequence_index_out_of_range' if vector else 'hash_missing_key'
    guard(work, 'php', 'missing', types + model + readers + 'echo callPlayer(new Team());return 8;',
          error, exception_type='std::out_of_range', expected_stdout=b'PK')


def callable_field(work, case):
    model = """
        class Router {
            public function<int(int)> $op;
            public function seed():void {$this->op=original(...);}
            public function replace():void {$this->op=replacement(...);}
        }
        function original(int $n):int {echo "A";return $n;}
        function replacement(int $n):int {echo "B";return $n+1;}
    """
    probe = 'function probe(?Router $r):?Router {echo "P";return $r;}'
    if case != 'callable_field_recovery':
        root = 'null' if case == 'callable_field_absent' else 'new Router()'
        main = probe + 'function arg():int {echo "X";return 7;}'
        main += f'$r ?Router={root};return (probe($r)?->op)(arg());'
        guard(work, 'php', case, model + main, exception_type='std::bad_function_call',
              exception_header='functional', expected_stdout=b'PX')
        return
    main = probe + """
        function arg(?Router $r):int {echo "X";$r->replace();return 7;}
        function invoke(?Router $r):int {return (probe($r)?->op)(arg($r));}
        $r ?Router=new Router();$r->seed();$saved=$r?->op;
        echo invoke($r),invoke($r),$saved(7);return 8;
    """
    changed = model.replace('public function<int(int)>', 'public int').replace('=original(...);', '=1;').replace('=replacement(...);', '=2;')
    trace = b'PXA7PXB8A7'
    revisions(work, 'php', dict(main=main, router=model), [
        (None, None, trace), ('router', changed, 'Invocation requires an owned callable'),
        ('router', model, trace)])


def terminal_index_call(work, case):
    vector = case.endswith('vector')
    key = '0' if vector else '"score"'
    key_type = 'int' if vector else 'string'
    collection = 'vector<function<int(int)>>' if vector else 'hash<function<int(int)>,string>'
    first = '$this->ops[]' if vector else '$this->ops["score"]'
    model = f"""
        class Router {{
            public {collection} $ops;
            public function fill():void {{{first}=fn($n)=>original($n);}}
            public function replace():void {{$this->ops[{key}]=fn($n)=>replacement($n);}}
        }}
        function original(int $n):int {{echo "A";return $n;}}
        function replacement(int $n):int {{echo "B";return 8;}}
    """
    readers = f"""
        function probe(?Router $r):?Router {{echo "P";return $r;}}
        function key():{key_type} {{echo "K";return {key};}}
        function arg(?Router $r):int {{echo "X";$r->replace();return 0;}}
        function invoke(?Router $r):nullable<int> {{return probe($r)?->ops[key()](arg($r));}}
    """
    if '_missing_' in case:
        guard(work, 'php', 'missing', model + readers + 'echo invoke(new Router()) ?? 9;return 8;',
              'sequence_index_out_of_range' if vector else 'hash_missing_key',
              exception_type='std::out_of_range', expected_stdout=b'PK')
        return
    main = readers + """
        echo invoke(null) ?? 9;$r=new Router();$r->fill();
        echo invoke($r) ?? 9,invoke($r) ?? 9;return 8;
    """
    changed = model.replace('function<int(int)>', 'function<int(int,int)>').replace('fn($n)', 'fn($n,$other)')
    trace = b'P9PKXA0PKXB8'
    edits = [(None, None, trace)]
    # Two native builds plus fresh/incremental comparisons took 4–7 s in isolation
    # even with misses split out. Keep one native execution in the default suite.
    if '_recovery_' in case:
        edits.extend([('router', changed, 'Argument count mismatch for function: indexed callable'), ('router', model, trace)])
    revisions(work, 'php', dict(main=main, router=model), edits)


def terminal_index_call_admission(work):
    model = 'class Router {public vector<function<int(int&)>> $ops;}'
    main = 'function read(?Router $r):nullable<int> {$n=0;return $r?->ops[0]($n);}return 8;'
    diagnostic = 'Nullsafe indexed invocation requires a callable returning by value'
    revisions(work, 'php', dict(router=model, main=main), [
        ('router', model.replace('int(int&)', 'int&(int&)'), diagnostic),
        ('router', model.replace('int(int&)', 'void(int&)'), 'Void call cannot be used as a value'),
        ('router', model, b'')])


def index_ordinary(work, case):
    team = """
        class Team {
            public hash<vector<?Player>,string> $groups;
            public function fill(?Player $p):void {$row vector<?Player> = [];$row[]=$p;$this->groups["g"]=$row;}
        }
    """
    readers = """
        function probe(?Team $t):?Team {echo "P";return $t;}
        function groupKey():string {echo "G";return "g";}
        function slot():int {echo "K";return 0;}
        function swap(?Team $t):int {echo "A";$p=new Player();$p->points=8;$t->fill($p);return 1;}
        function field(?Team $t):int {return probe($t)?->groups[groupKey()][slot()]->points ?? 9;}
        function callPlayer(?Team $t):int {return probe($t)?->groups[groupKey()][slot()]->score(swap($t)) ?? 9;}
    """
    players = PLAYERS.replace('public int $points;', 'public int $points;public function __destruct() {echo "D",$this->points;}')
    if case == 'index_ordinary_failures':
        source = players + team + readers + '$t=new Team();'
        for label, setup, error, trace in [
                ('missing_group', '', 'hash_missing_key', b'PG'),
                ('missing_player', '$t->groups["g"]=[];', 'sequence_index_out_of_range', b'PGK')]:
            guard(work, 'php', label, source + setup + 'echo callPlayer($t);return 8;', error,
                  exception_type='std::out_of_range', expected_stdout=trace)
        guard(work, 'php', 'empty', source + '$t->fill(null);echo callPlayer($t);return 8;',
              'ownership_empty_access', expected_stdout=b'PGK')
        return
    main = readers + """
        echo field(null),callPlayer(null);$t=new Team();$t->fill(new Player());
        echo field($t),callPlayer($t),field($t);return 8;
    """
    trace = b'P9P9PGK0PGKAMD00PGK8D8'
    revisions(work, 'php', dict(main=main, team=team, players=players), [
        (None, None, trace), ('players', players.replace('function score(', 'function renamed('), 'Unknown method Player::score'),
        ('players', players, trace)])


def index_ordinary_owner(work):
    main = PLAYERS + """
        class Roster {
            public vector<?Player> $players;
            public function __construct(int $n) {$p=new Player();$p->points=$n;$this->players[]=$p;}
            public function __destruct() {echo "D",$this->players[0]->points;}
        }
        class Team {public ?Roster $roster;}
        function key(?Team $t):int {echo "K";$t->roster=new Roster(8);return 0;}
        function read(?Team $t):int {return $t?->roster->players[key($t)]->points ?? 9;}
        $t=new Team();$t->roster=new Roster(7);echo read($t);return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'KD77D8')])


def repeated_index(work, case):
    hash_first = case.startswith('hash_vector')
    outer = 'hash<vector<int>,string>' if hash_first else 'vector<hash<int,string>>'
    inner = 'vector<int>' if hash_first else 'hash<int,string>'
    group, leaf = ('"home"', '0') if hash_first else ('0', '"home"')
    group_type, leaf_type = ('string', 'int') if hash_first else ('int', 'string')
    insert = f'$this->groups[{group}]' if hash_first else '$this->groups[]'
    populate = '$row[]=$n;' if hash_first else f'$row[{leaf}]=$n;'
    model = f"""
        class Team {{
            public {outer} $groups;
            public function add():void {{{insert}=[];}}
            public function put(int $n):void {{
                $row {inner}=[];{populate}$this->groups[{group}]=$row;
            }}
        }}
    """
    probes = f"""
        function probe(?Team $t):?Team {{echo "P";return $t;}}
        function groupKey():{group_type} {{echo "G";return {group};}}
        function leafKey():{leaf_type} {{echo "L";return {leaf};}}
        function read(?Team $t):nullable<int> {{return probe($t)?->groups[groupKey()][leafKey()];}}
    """
    if case.endswith('_missing'):
        outer_error, inner_error = ('hash_missing_key', 'sequence_index_out_of_range') if hash_first else ('sequence_index_out_of_range', 'hash_missing_key')
        for label, setup, error, trace in [('outer', '', outer_error, b'PG'), ('inner', '$t->add();', inner_error, b'PGL')]:
            guard(work, 'php', label, model + probes + '$t=new Team();' + setup + 'echo read($t) ?? 9;return 8;',
                  error, exception_type='std::out_of_range', expected_stdout=trace)
        return
    main = probes + f"""
        async function replaceKey(?Team $t):{leaf_type} {{
            await async_sleep_ms(1);echo "R";$t->put(90);return {leaf};
        }}
        async function saved(?Team $t):int {{return probe($t)?->groups[groupKey()][await replaceKey($t)] ?? 9;}}
        echo read(null) ?? 9,await saved(null);
        $t=new Team();$t->add();$t->put(0);echo read($t) ?? 9;
        $t->put(7);echo await saved($t),read($t) ?? 9;return 8;
    """
    changed = model.replace(inner, inner.replace('<int', '<string')).replace('=$n;', '="edited";')
    trace = b'P9P9PGL0PGR7PGL90'
    revisions(work, 'php', dict(main=main, team=model), [
        (None, None, trace), ('team', changed, 'Unsupported explicit conversion from string to int64'), ('team', model, trace)])


def index_admission(work):
    main = 'function read(?Team $t):nullable<int> {return $t?->scores[0];}return 8;'
    model = 'class Team {public vector<int> $scores;}'
    revisions(work, 'php', dict(main=main, model=model), [
        ('model', model.replace('vector<int>', 'string'), 'Nullsafe indexed read requires a typed vector or hash'),
        ('model', model, b'')])


def index_owner(work):
    main = """
        class Scores {
            public vector<int> $values;
            public function __construct(int $n) {$this->values[]=$n;}
            public function __destruct() {echo "D",$this->values[0];}
        }
        class Team {public ?Scores $scores;}
        function replace(Team $t):int {$t->scores=new Scores(9);echo "K";return 0;}
        function pick(Team $t):?Team {return $t;}
        $t=new Team();$t->scores=new Scores(7);
        $n=pick($t)?->scores->values[replace($t)];echo "R",$n ?? 0;return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'KD7R7D9')])


def repeated_index_owner(work):
    main = """
        class Scores {
            public hash<vector<hash<int,string>>,string> $rows;
            public function __construct(int $n) {$this->rows["g"]=[["p" => $n]];}
            public function __destruct() {echo "D",$this->rows["g"][0]["p"];}
        }
        class Team {public ?Scores $scores;}
        function replace(Team $t):string {$t->scores=new Scores(90);echo "K";return "g";}
        function pick(Team $t):?Team {return $t;}
        $t=new Team();$t->scores=new Scores(7);
        $n=pick($t)?->scores->rows[replace($t)][0]["p"];echo "R",$n ?? 0;return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'KD7R7D90')])


def index_suspended(work):
    main = """
        class Team {
            public function scores():vector<int> {echo "S";$v vector<int> = [0];return $v;}
        }
        class Club {public ?Team $team;}
        async function key():int {await async_sleep_ms(1);echo "K";return 0;}
        async function read(?Club $c):int {return $c?->team?->scores()[await key()] ?? 9;}
        echo await read(null);$c=new Club();echo await read($c);
        $c->team=new Team();echo await read($c);return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'99SK0')])


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


LEAGUE = """
    class Team {public ?Captain $captain;}
    class Club {public ?Player $coach;}
"""
CAPTAIN = """
    class Captain {
        public ?Club $club;
        public function getClub(int $n):?Club {echo "C";return $this->club;}
    }
"""
MIXED_READ = """
    function read(?Team $t):int {
        return probe($t)?->captain->club?->coach->score(key()) ?? 9;
    }
"""


def mixed_chain(work):
    main = PROBES + MIXED_READ + """
        function longRun(?Team $t):int {
            return probe($t)?->captain->getClub(key())->coach->score(key()) ?? 9;
        }
        echo read(null),longRun(null);
        $t=new Team();$captain=new Captain();$t->captain=$captain;
        echo read($t);
        $club=new Club();$club->coach=new Player();$captain->club=$club;
        echo read($t),longRun($t);return 8;
    """
    trace = b'P9P9P9PKM0PKCKM0'
    changed = CAPTAIN.replace('?Club', '?Other')
    revisions(work, 'php', dict(main=main, captain=CAPTAIN, league=LEAGUE, players=PLAYERS), [
        (None, None, trace), ('captain', changed, 'Unknown field Other.coach'), ('captain', CAPTAIN, trace)])


def mixed_suspended(work):
    main = PROBES + """
        async function later():int {await async_sleep_ms(1);return key();}
        async function read(?Team $t):int {
            return probe($t)?->captain->getClub(await later())?->coach->score(await later()) ?? 9;
        }
        echo await read(null);
        $t=new Team();$c=new Captain();$t->captain=$c;echo await read($t);
        $club=new Club();$club->coach=new Player();$c->club=$club;echo await read($t);return 8;
    """
    revisions(work, 'php', dict(main=main, captain=CAPTAIN, league=LEAGUE, players=PLAYERS), [
        (None, None, b'P9PKC9PKCKM0')])


def mixed_failure(work):
    setup = LEAGUE + CAPTAIN + PLAYERS + PROBES + MIXED_READ + '$t=new Team();'
    for name, init in [('captain', ''), ('coach', '$c=new Captain();$t->captain=$c;$c->club=new Club();')]:
        guard(work, 'php', name, setup + init + 'echo read($t);return 8;',
              'ownership_empty_access', expected_stdout=b'P')


def enum_properties(work):
    kind = 'enum Kind:string {case Empty="";case Other="other";}'
    main = """
        function input(string $s):string {echo "I";return $s;}
        function read(string $s):void {
            echo enum_try_from_value(Kind::class,input($s))?->name ?? "N",":";
            echo enum_try_from_value(Kind::class,input($s))?->value ?? "V","|";
        }
        read("missing");read("");read("other");return 8;
    """
    trace = b'IN:IV|IEmpty:I|IOther:Iother|'
    revisions(work, 'php', dict(main=main, kind=kind), [
        (None, None, trace),
        ('kind', kind.replace('case Empty', 'case Blank'), trace.replace(b'Empty', b'Blank')),
        ('kind', kind, trace)])


def enum_property_admission(work):
    main = """
        enum Rank {case First=0;case Last=2;}
        function read(?Rank $rank):void {echo $rank?->name ?? "N",":",$rank?->value ?? 9,"|";}
        read(null);read(Rank::First);return 8;
    """
    revisions(work, 'php', dict(main=main), [
        (None, None, b'N:9|First:0|'),
        ('main', main.replace('?->name', '?->missing'), 'Unknown enum property Rank.missing'),
        ('main', main.replace('echo $rank?->name', '$rank?->name="changed";echo $rank?->name'), 'Expected call statement'),
        ('main', main, b'N:9|First:0|')])


def enum_method(work):
    kind = """
        enum Kind:string {
            case Empty="";case Other="other";
            public function score(int $n):int {echo enum_name($this);return $n;}
        }
    """
    main = """
        function input(string $s):string {echo "I";return $s;}
        function key():int {echo "K";return 0;}
        function read(string $s):int {return enum_try_from_value(Kind::class,input($s))?->score(key()) ?? 9;}
        echo read("missing"),read("");return 8;
    """
    trace = b'I9IKEmpty0'
    revisions(work, 'php', dict(main=main, kind=kind), [
        (None, None, trace), ('kind', kind.replace('function score', 'function renamed'), 'Unknown method Kind::score'),
        ('kind', kind, trace)])


def enum_selected_case(work):
    main = """
        enum Rank {case First=0;case Last=2;
            public function show(int $n):void {echo enum_name($this),$n;}
        }
        function replace(?Rank &$r):int {$r=Rank::Last;echo "K";return 1;}
        $r ?Rank=null;$r?->show(replace($r));
        $r=Rank::First;$r?->show(replace($r));echo enum_name($r ?? Rank::First);return 8;
    """
    revisions(work, 'php', dict(main=main), [(None, None, b'KFirst1Last')])


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
    denied = 'Nullsafe call requires a synchronous instance method without a reference return'
    revisions(work, 'php', dict(main=main, player=method), [
        ('player', method.replace('function score', 'final function &score'), denied),
        ('player', method.replace(':int {return $n;', ':void {return;'), b''),
        ('player', method, b'')])


NOTIFY = """
    class Coach {public function notify(int $n):void {echo "N",$n;}}
    class Team {public ?Coach $coach;}
"""


def terminal_void(work):
    main = PROBES + """
        function run(?Team $t):void {probe($t)?->coach->notify(key());}
        run(null);$t=new Team();$t->coach=new Coach();run($t);return 8;
    """
    trace = b'PPKN1'
    expression = 'probe($t)?->coach->notify(key())'
    assignment = main.replace(expression, '$saved=' + expression)
    returns_value = NOTIFY.replace(':void {echo "N",$n;', ':int {echo "N",$n;return $n;')
    revisions(work, 'php', dict(main=main, model=NOTIFY), [
        (None, None, trace),
        ('main', assignment, 'Void call cannot be used as a value'),
        ('model', returns_value, trace),
        ('model', NOTIFY, 'Void call cannot be used as a value'),
        ('main', main, trace)])


def terminal_void_empty(work):
    source = NOTIFY + PROBES + '$t ?Team=new Team();probe($t)?->coach->notify(key());return 8;'
    guard(work, 'php', 'empty', source, 'ownership_empty_access', expected_stdout=b'P')


def terminal_void_suspended(work):
    main = PROBES + """
        async function later():int {await async_sleep_ms(1);return key();}
        async function run(?Team $t):void {probe($t)?->coach->notify(await later());}
        await run(null);$t=new Team();$t->coach=new Coach();await run($t);return 8;
    """
    revisions(work, 'php', dict(main=main, model=NOTIFY), [(None, None, b'PPKN1')])


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
    function keyArg():string { echo "K"; return "method"; }
    function fallbackKey():string { echo "F"; return "fallback"; }
    function callArg():int { echo "C"; return 6; }
    function byFallback(int $x):int { echo "B"; return $x+20; }
    function byMethod(int $x):int { echo "A"; return $x+10; }
    """
    setup = '$ops hash<function<int(int)>,string> = ["fallback"=>fn($n)=>byFallback($n)];'
    method = '$ops["method"]=fn($n)=>byMethod($n);'
    if case in ('chained_indexed_callable', 'ordinary_indexed_callable', 'ordinary_indexed_empty'):
        api += """
        class Router {
            public ?Selector $target;
            public function select(int $n):?Selector { echo "S"; return $this->target; }
        }
        function probe(?Router $r):?Router { echo "P"; return $r; }
        function routeArg():int { echo "R"; return 1; }
        function invokeRoute(?Router $router, hash<function<int(int)>,string> $ops):void {
            $value=$ops[probe($router)?->select(routeArg())?->pick(keyArg()) ?? fallbackKey()](callArg());
            echo "|",$value,";";
        }
        """
        if case != 'chained_indexed_callable':
            api = api.replace('?->pick(keyArg())', '->pick(keyArg())')
            if case == 'ordinary_indexed_empty':
                guard(work, 'php', 'empty_selector', api + setup + method + 'invokeRoute(new Router(),$ops);return 8;',
                      'ownership_empty_access', expected_stdout=b'PRS')
                return
            main = setup + method + """
            invokeRoute(null,$ops);
            $router=new Router(); $router->target=new Selector(); invokeRoute($router,$ops); return 8;
            """
            revisions(work, 'php', dict(api=api, main=main), [(None, None, b'PFCB|26;PRSKMCA|16;')])
            return
        main = setup + method + """
        invokeRoute(null,$ops);
        $router=new Router(); invokeRoute($router,$ops);
        $router->target=new Selector(); invokeRoute($router,$ops); return 8;
        """
        revisions(work, 'php', dict(api=api, main=main), [(None, None, b'PFCB|26;PRSFCB|26;PRSKMCA|16;')])
        return
    api += 'function probe(?Selector $s):?Selector { echo "P"; return $s; }'
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
    if case in ('hash_vector', 'vector_hash', 'hash_vector_missing', 'vector_hash_missing'):
        repeated_index(Path(tmp), case)
    elif case.startswith('callable_field_'):
        callable_field(Path(tmp), case)
    elif case.startswith('terminal_index_call_') and case.endswith(('vector', 'hash')):
        terminal_index_call(Path(tmp), case)
    elif case in ('index_vector', 'index_hash', 'index_string_vector'):
        terminal_index(Path(tmp), case)
    elif case in ('index_ordinary', 'index_ordinary_failures'):
        index_ordinary(Path(tmp), case)
    elif case in ('index_member_vector', 'index_member_hash'):
        index_member(Path(tmp), case)
    elif case in ('indexed_callable', 'indexed_callable_missing', 'chained_indexed_callable', 'ordinary_indexed_callable', 'ordinary_indexed_empty'):
        indexed_callable(Path(tmp), case)
    else:
        {'terminal_index_call_admission': terminal_index_call_admission, 'index_ordinary_owner': index_ordinary_owner, 'repeated_index_owner': repeated_index_owner, 'enum_properties': enum_properties, 'enum_property_admission': enum_property_admission,
         'enum_selected_case': enum_selected_case, 'enum_method': enum_method, 'index_admission': index_admission, 'index_owner': index_owner, 'index_suspended': index_suspended, 'terminal_void': terminal_void, 'terminal_void_empty': terminal_void_empty,
         'terminal_void_suspended': terminal_void_suspended, 'mixed_chain': mixed_chain, 'mixed_failure': mixed_failure, 'mixed_suspended': mixed_suspended,
         'chain': chain, 'named': named, 'lifetime': lifetime, 'suspended': suspended,
         'dispatch': dispatch, 'admission': admission, 'ordinary_suffix': ordinary_suffix,
         'empty_intermediate': empty_intermediate, 'suspended_suffix': suspended_suffix}[case](Path(tmp))
