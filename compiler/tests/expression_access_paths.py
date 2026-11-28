"""Trace selection, suspension, failure and destination identity across expression contexts."""
import argparse
import subprocess
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


CALLABLES = """
class Holder { public hash<function<int(int)>,string> $ops; }
function first(int $x):int { echo "A"; return $x+10; }
function second(int $x):int { echo "B"; return $x+20; }
function third(int $x):int { echo "C"; return $x+30; }
function arg():int { echo "I"; return 6; }
"""
SETUP = '$holder=new Holder(); $holder->ops["run"]=first(...);'


def awaited_key(work, case):
    mutation = '$h->ops["run"]=second(...);'
    if case == 'await_remove':
        mutation = '$h->ops->remove("run");'
    key = ('async function key(Holder $h):string { echo "K"; await async_sleep_ms(1); echo "R";'
           + mutation + 'return "run"; }')
    argument = 'arg()'
    source = CALLABLES + key
    if case == 'await_both':
        source += """
        async function change(Holder $h):int { echo "I"; await async_sleep_ms(1); echo "J";
            $h->ops["run"]=third(...); return 6; }
        """
        argument = 'await change($holder)'
    source += SETUP + '$n=$holder->ops[await key($holder)](' + argument + ');'
    source += '$next=$holder->ops["run"](6); echo "|",$n,"/",$next;return 8;'
    if case == 'await_remove':
        guard(work, 'php', case, source, 'hash_missing_key', exception_type='std::out_of_range', expected_stdout=b'KR')
    else:
        expected = b'KRIJBC|26/36' if case == 'await_both' else b'KRIBB|26/26'
        revisions(work, 'php', {'main': source}, [(None, None, expected)])


def empty_callable(work, case):
    source = CALLABLES + SETUP + """
    $empty function<int(int)>; $holder->ops["run"]=$empty;
    return $holder->ops["run"](arg());
    """
    guard(work, 'php', case, source, expected_stdout=b'I',
          exception_type='std::bad_function_call', exception_header='functional')


def exceptional(work, case):
    source = CALLABLES + """
    class Marker extends Exception {}
    class Token { public function __destruct() {echo "D";} public int $value=3; }
    function make():function<int(int)> { $t=new Token(); return fn($n)=>$t->value+$n; }
    async function fail(Holder $h, Marker $error):int {
        echo "F"; await async_sleep_ms(1); $h->ops->remove("run"); echo "X";
        throw $error;
    }
    async function badKey(Holder $h, Marker $error):string { $v=await fail($h,$error); return "run"; }
    """ + SETUP + '$holder->ops["run"]=make(); $error=new Marker();'
    call = '$holder->ops["run"](await fail($holder,$error))' if case == 'throw_arg' else '$holder->ops[await badKey($holder,$error)](arg())'
    source += 'try {$n=' + call + '; echo "BAD";} catch (Marker $e) {if ($e !== $error) {return 1;} echo "C";}return 8;'
    expected = b'FXDC' if case == 'throw_arg' else b'FDXC'
    revisions(work, 'php', {'main': source}, [(None, None, expected)])


def failed_store(work, case):
    source = """
    function key():string {echo "K";return "n";}
    function fail(dynamic $child):int {$child["effect"]=1; echo "R"; throw Exception::make("marker");}
    $d dynamic=[]; $child dynamic=[]; $child["n"]=3; $d->child=$child;
    try {$d->child[key()]+=fail($child);} catch (Exception $e) {echo "C",$e->get_message();}
    echo "|",(int)$d->child["n"],"/",(int)$child["effect"];return 8;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'KRCmarker|3/1')])


def selection(work, case):
    model = """
    class Node {public int $value=3;}
    function selectNode(Node $n):Node {echo "N"; return $n;}
    function bad():Node {echo "X"; throw Exception::make("branch");}
    function choose(bool $a,Node $n):Node {return $a ? selectNode($n) : bad();}
    function key(int $i):string {return match($i){1=>good(),default=>badKey()};}
    function good():string {echo "G";return "run";}
    function badKey():string {echo "T";throw Exception::make("key");}
    """
    main = SETUP + """
    $n=new Node(); $r=choose(true,$n); $r->value=7;echo $n->value;
    try {$v=choose(false,$n);} catch(Exception $e){echo $e->get_message();}
    echo $holder->ops[key(1)](arg());
    try {$value=$holder->ops[key(0)](arg());} catch(Exception $e){echo $e->get_message();}
    return 8;
    """
    expected = b'N7XbranchGIA16Tkey'
    # A second successful native build took the full edit sequence above four seconds in isolation.
    # Default still verifies the invalid provider and a native recovery against fresh compilation.
    bad = model.replace('function key(int $i):string', 'function key(int $i):int')
    initial = [(None, None, expected)] if case == 'selection_full' else []
    revisions(work, 'php', {'main': main, 'holder': CALLABLES, 'model': model},
              initial + [('model', bad, None), ('model', model, expected)])


def call_modes(work, case):
    source = """
    class Receiver {
        public function edit(int &$n):void {$n+=4;echo "E";}
        public function diff(int $a,int $b):int {return $a-$b;}
        public function sum(int ...$items):int {return $items[0]+$items[1];}
    }
    function key():int {echo "K";return 0;}
    function left():int {echo "L";return 9;}
    function right():int {echo "R";return 2;}
    function items():vector<int> {echo "S";$v vector<int>=[1,2];return $v;}
    $owners vector<Receiver>=[new Receiver()];$n=3;
    $owners[key()]->edit($n);echo $n;
    echo $owners[key()]->diff(b:right(),a:left());
    echo $owners[key()]->sum(...items());return 8;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'KE7KRL7KS3')])


def anchor(work, case):
    root = Path(__file__).resolve().parents[2]
    parser = root / 'specs/planning/probes/expression_context/parse.php'
    expression = '$something[$selector[12]->func()[10]][a_constant]($arg)'
    tree = subprocess.check_output(['php', parser, root, 'php'], input=expression + ';', text=True)
    assert tree.strip() == 'indexed callable(index(index($something, index(func(index($selector, 12)), 10)), a_constant), $arg)'
    provider = """
    const int a_constant=0;
    class Selector {public function func():vector<int> {echo "M"; $v vector<int>=[0,0,0,0,0,0,0,0,0,0,0]; return $v;}}
    function useArg(int $arg):int {echo "C",$arg;return $arg+2;}
    """
    source = '$selector vector<Selector>=[' + ','.join(['new Selector()'] * 13) + '];'
    source += """
    $row vector<function<int(int)>>=[useArg(...)];
    $something vector<vector<function<int(int)>>> = [$row]; $arg=6;
    """ + expression + '; echo ' + expression + ';return 8;'
    bad = provider.replace('vector<int>', 'vector<string>').replace('[0,0,0,0,0,0,0,0,0,0,0]', '["x"]')
    # Full valid/invalid/repaired sequence took five seconds in isolation. Default keeps the
    # parser assertion, invalid-key diagnostic and a single successful native recovery.
    expected = b'MC6MC68'
    initial = [(None, None, expected)] if case == 'anchor_full' else []
    revisions(work, 'php', {'main': source, 'provider': provider},
              initial + [('provider', bad, 'Vector index requires int64'), ('provider', provider, expected)])


def bounds(work, case):
    declarations = {
        'bounds_string': '$v string="";',
        'bounds_vector': '$v vector<int>=[];',
        'bounds_fixed': '$v fixed_array<byte,1>=[0];',
        'bounds_hash': '$v hash<int,int>=[];',
    }
    main = CALLABLES + SETUP + declarations[case] + """
    function indexOnce():int {echo "I";return 9;}
    function asKey(int $n):string {echo "S";return "run";}
    return $holder->ops[asKey((int)$v[indexOnce()])](arg());
    """
    error = 'hash_missing_key' if case == 'bounds_hash' else 'sequence_index_out_of_range'
    guard(work, 'php', case, main, error, expected_stdout=b'I', exception_type='std::out_of_range')


def terminal_void(work, case):
    setup = 'class T {public function notify():void {echo "N";}} function useInt(int $n):void {} $t ?T=null;'
    main = '$v vector<int>=[8];'
    target = '$t?->notify()'
    edits = [('main', setup + main + tail + ' return 8;', 'Void call cannot be used as a value')
             for tail in ('echo $v[' + target + '];', 'useInt(' + target + ');', target + '->notify();')]
    good = setup + target + ';return 8;'
    revisions(work, 'php', {'main': good}, [(None, None, b''), *edits, ('main', good, b'')])


def mixed_read_context(work, case):
    setup = '$v mixed=[];$row mixed=[];$row[0]=3;$v[0]=$row;$d dynamic=[];$child dynamic=[];$d->child=$child;'
    good = setup + '$v[0][0]=8;$r=$v[0];echo (int)$r[0];return 8;'
    read = '(int)$v[0][0]'
    edits = [('main', setup + tail + 'return 8;', 'Nested boxed indexing is deferred')
             for tail in ('echo '+read+';', '$d['+read+']=8;', '$d->child['+read+']=8;', '$d->name='+read+';')]
    revisions(work, 'php', {'main': good}, [(None, None, b'8'), *edits, ('main', good, b'8')])


def shallow_failure(work, case):
    source = """
    function key(int &$k):int {$k+=1;return 0;}
    function rhs(int &$r):int {$r+=1;throw Exception::make("bad");}
    function badKey(int &$k):int {$k+=1;throw Exception::make("key");}
    function value(int &$r):int {$r+=1;return 9;}
    $h hash<int,int>=[0=>3]; $k=0;$r=0;
    try {$h[key($k)]=rhs($r);} catch(Exception $e) {echo $e->get_message();}
    if ($k>1 || $r!=1 || $h[0]!=3) {return 1;}
    $k=0;$r=0;
    try {$h[badKey($k)]=value($r);} catch(Exception $e) {echo $e->get_message();}
    if ($k!=1 || $r>1 || $h[0]!=3) {return 2;}
    return 8;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'badkey')])


def conversion_failure(work, case):
    source = """
    function fail():int {echo "R";return 300;}
    $h hash<int,int>=[0=>7];
    try {$h[0]=(int)(byte)fail();} finally {echo (int)$h[0];}
    return 8;
    """
    guard(work, 'php', case, source, 'integer_cast_out_of_range', expected_stdout=b'R7',
          exception_type='std::out_of_range')


def suspended_owner(work, case):
    source = CALLABLES + """
    class Registry {public Holder $current; public function __construct(Holder $h) {$this->current=$h;}}
    function choose(Registry $r):Holder {echo "O";return $r->current;}
    async function switchOwner(Registry $r,Holder $fresh):string {
        echo "K"; await async_sleep_ms(1); $r->current=$fresh; echo "S";return "run";
    }
    """ + SETUP + """
    $r=new Registry($holder);
    $fresh=new Holder();$fresh->ops["run"]=second(...);
    $value=choose($r)->ops[await switchOwner($r,$fresh)](arg());
    $next=$r->current->ops["run"](6); echo "|",$value,"/",$next;return 8;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'OKSIAB|16/26')])


def grouped_receiver(work, case):
    source = """
    class N {public int $v=3;}
    class H {public ?N $node;}
    function probe(?H $h):?H {echo "P";return $h;}
    $h ?H=null;
    """
    if case == 'grouped_absent':
        guard(work, 'php', case, source + 'echo (probe($h)?->node)->v;return 8;',
              'ownership_empty_access', expected_stdout=b'P')
        return
    source += """
    echo probe($h)?->node->v ?? 9;
    $h=new H();$h->node=new N();echo (probe($h)?->node)->v;
    echo (probe($h)?->node?->v) ?? 9;return 8;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'P9P3P3')])


def callable_join(work, case):
    # Keep expected semantics executable for the two outstanding preparation failures.
    model = 'function make(int $n):function<int(int)> {return fn($x)=>$x+$n;}'
    separate = '$a function<int(int)>=make(1);$b function<int(int)>=make(2);'
    shared = '$a=make(1);$b=make(2);'
    expr = 'true ? $a : $b' if case.endswith('ternary') else 'match(1){1=>$a,default=>$b}'
    setup = separate if case.startswith('pending') else shared
    source = setup + '$f=' + expr + ';echo $f(7);return 8;'
    revisions(work, 'php', {'api': model, 'main': source}, [(None, None, b'8')])


def arithmetic_failure(work, case):
    source = """
    function zero():int {echo "R";return 0;}
    $h hash<int,int>=[0=>7];
    try {$h[0]%=zero();} finally {echo $h[0];}
    return 8;
    """
    guard(work, 'php', case, source, 'modulo_by_zero', expected_stdout=b'R7',
          exception_type='std::domain_error')


def predicate_after_write(work, case):
    source = """
    function replace(mixed &$v):void {$v=7;}
    $v mixed=true;
    if (is_bool($v)) {
        replace($v); if (is_bool($v) || !is_int($v)) {return 1;}
        echo (int)$v; return 8;
    }
    return 2;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'7')])


def qualified_receiver(work, case):
    source = """
    class H {public int $x=3;public function get():int {return $this->x;}}
    class Maker {public static function make():H {echo "M";return new H();}}
    echo (new H())->x,Maker::make()->x,Maker::make()->get();return 8;
    """
    revisions(work, 'php', {'main': source}, [(None, None, b'3M3M3')])


def pending_coalesce(work, case):
    # Broader normative matrix; the active compiler currently implements nullable<T> ?? T.
    if case == 'pending_wrapper_coalesce':
        source = '$a nullable<int>=null;$b nullable<int>=7;echo $a ?? $b;return 8;'
        expected = b'7'
    else:
        source = """
        class N {public int $v=3;}
        function choose(N $a,N $b):N {return $a ?? $b;}
        $a=new N(); $b=new N(); $chosen=choose($a,$b); $chosen->v=8;
        echo $a->v,$b->v;return 8;
        """
        expected = b'83'
    revisions(work, 'php', {'main': source}, [(None, None, expected)])


CASES = {name: awaited_key for name in ('await_replace', 'await_remove', 'await_both')}

CASES.update(empty_callable=empty_callable, throw_arg=exceptional, throw_key=exceptional,
             selection_full=selection, anchor_full=anchor,
             failed_store=failed_store, selection=selection, call_modes=call_modes, anchor=anchor)

CASES.update({name: bounds for name in ('bounds_string', 'bounds_vector', 'bounds_fixed', 'bounds_hash')})
CASES.update(arithmetic_failure=arithmetic_failure, predicate_after_write=predicate_after_write,
             qualified_receiver=qualified_receiver, pending_wrapper_coalesce=pending_coalesce,
             pending_handle_coalesce=pending_coalesce)
CASES.update(grouped_absent=grouped_receiver, grouped_present=grouped_receiver)
CASES.update({name: callable_join for name in ('factory_match', 'factory_ternary', 'pending_match', 'pending_ternary')})
CASES.update(suspended_owner=suspended_owner, terminal_void=terminal_void, mixed_read=mixed_read_context,
             shallow_failure=shallow_failure, conversion_failure=conversion_failure)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('case', choices=CASES)
    case = parser.parse_args().case
    with TemporaryDirectory(prefix='expression-access-') as directory:
        CASES[case](Path(directory), case)
