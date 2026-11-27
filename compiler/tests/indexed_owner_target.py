"""PHP keyed stores retain an indexed shared owner through write-back."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


model = 'class Holder { public vector<int> $values; }'
setup = '$h=new Holder(); $h->values=[3]; $owners vector<Holder> =[$h];'
# Exercise a call continuation through an indexed field alongside the read control.
model += ' class Box { public vector<function<int(int)>> $ops; }'
call = ('function witness(int $n):int { echo $n; return $n; } '
        '$box=new Box(); $ops vector<function<int(int)>> = [witness(...)]; $box->ops=$ops; '
        '$boxes vector<Box> = [$box]; $boxes[0]->ops[0](6);')
controls = setup + 'echo $owners[0]->values[0];' + call
diagnostic = 'Field assignment requires writable local storage'
local = '$o=$owners[0]; $o->values[0]=7; echo $h->values[0]; return 8;'


def targets(work):
    store = ('function writeBoth(vector<Holder> $owners):void { $owners[0]->values[0]=4; '
             'echo $owners[0]->values[0]; ($owners[0])->values[0]=5; }')
    main = controls + 'writeBoth($owners); echo $h->values[0];' + local
    revisions(work, 'php', {'main': main, 'api': store, 'model': model}, [(None, None, b'36457')])


def replacement(work):
    api = ('function ownerIndex():int { echo "O"; return 0; } '
           'function valueIndex(int &$calls):int { $calls+=1; return 0; } '
           'function swap(vector<Holder> &$owners, Holder $next):int { echo "R"; $owners[0]=$next; return 7; }')
    main = setup + '$calls=0; $next=new Holder(); $next->values=[90];'
    main += '$owners[ownerIndex()]->values[valueIndex($calls)]=swap($owners,$next);'
    main += 'echo $calls,":",$h->values[0],"/",$owners[0]->values[0]; return 8;'
    revisions(work, 'php', {'main': main, 'api': api, 'model': model}, [(None, None, b'OR1:7/90')])


def lifetime(work):
    tracked = ('class Holder { public vector<int> $values; '
               'public function __destruct() { echo "D",$this->values[0]; } }')
    api = ('function make(int $n):Holder { $h=new Holder(); $h->values=[$n]; return $h; } '
           'function swap(vector<Holder> &$owners):int { $owners[0]=make(90); echo "R"; return 7; }')
    main = '$owners vector<Holder> = [make(3)]; $owners[0]->values[0]=swap($owners); echo "E"; return 8;'
    revisions(work, 'php', {'main': main, 'api': api, 'model': tracked}, [(None, None, b'RD7ED90')])


def append(work):
    holder = """
        class Holder {
            public vector<int> $values;
            public function __construct(int $n) {$this->values=[$n];}
            public function __destruct() {echo "D",count($this->values),"/",$this->values[count($this->values)-1];}
        }
    """
    api = """
        function slot(int &$calls):int {$calls+=1;echo "I";return 0;}
        function swap(vector<Holder> &$owners):int {$owners[0]=new Holder(90);echo "R";return 7;}
    """
    body = """
        $owners vector<Holder> = [new Holder(3)];$calls=0;
        $owners[slot($calls)]->values[]=swap($owners);
        echo "E",$calls,"/",count($owners[0]->values),"/",$owners[0]->values[0];
    """
    grouped = body.replace('$owners[slot($calls)]->', '($owners[slot($calls)])->')
    main = 'function plain():void {' + body + '} function grouped():void {' + grouped + '} plain();grouped();return 8;'
    trace = b'IRD2/7E1/1/90D1/90' * 2
    readonly = holder.replace('public vector', 'public readonly vector')
    revisions(work, 'php', dict(main=main, api=api, holder=readonly), [
        (None, None, 'Cannot modify readonly property values'), ('holder', holder, trace)])


def mutations(work):
    tracked = """
        class Holder {
            public vector<int> $values;
            public function __construct(int $n) {$this->values=[$n];}
            public function __destruct() {echo "D",$this->values[0];}
        }
    """
    api = """
        function slot():int {echo "I";return 0;}
        function key():int {echo "K";return 0;}
        function replaceField(Holder $h):int {$h->values=[40,41];echo "F";return 7;}
        function swap(vector<Holder> &$owners, int $result):int {
            $owners[0]=new Holder(90);echo "R";return $result;
        }
    """
    sources, calls, trace = [], [], b''
    for owner in ('$owners[slot()]', '($owners[slot()])'):
        for pattern, value in [('{}[key()]+=swap($owners,7)', 10), ('++{}[swap($owners,0)]', 4),
                               ('{}[swap($owners,0)]++', 4), ('--{}[swap($owners,0)]', 2),
                               ('{}[swap($owners,0)]--', 2)]:
            name = 'check' + str(len(calls))
            operation = pattern.format(owner + '->values')
            sources.append(f'function {name}():void {{$owners vector<Holder> = [new Holder(3)];'
                           + operation + ';echo "E",$owners[0]->values[0];}')
            calls.append(name + '();')
            trace += (b'IKR' if '+=' in pattern else b'IR') + f'D{value}E90D90'.encode()
    main = ''.join(sources + calls) + """
        $owners vector<Holder> = [new Holder(3)];
        $owners[slot()]->values[key()]+=replaceField($owners[0]);
        echo $owners[0]->values[0],"/",$owners[0]->values[1];return 8;
    """
    trace += b'IKF10/41D10'
    revisions(work, 'php', dict(main=main, api=api, model=tracked), [(None, None, trace)])


def missing(work, case):
    api = """
        function slot():int {echo "I";return 0;}
        function key(int $n):int {echo "K";return $n;}
        function clear(Holder $h):int {$h->values=[];echo "R";return 7;}
    """
    op, trace = {
        'missing_before': ('$owners[slot()]->values[key(1)] += clear($h);', b'IK'),
        'missing_after': ('($owners[slot()])->values[key(0)] += clear($h);', b'IKR'),
        'missing_update': ('--($owners[slot()])->values[key(1)];', b'IK'),
    }[case]
    guard(work, 'php', case, model + api + setup + op + 'return 8;', 'sequence_index_out_of_range',
          expected_stdout=trace, exception_type='std::out_of_range')


def mutation_access(work):
    holder = 'class Holder {public vector<int> $values; public function __construct() {$this->values=[3];}}'
    readonly = holder.replace('public vector', 'public readonly vector')
    start = '$owners vector<Holder> = [new Holder()];'
    end = 'echo $owners[0]->values[0];return 8;'
    compound = start + '$owners[0]->values[0]*=2;' + end
    update = start + '++($owners[0])->values[0];' + end
    denied = 'Cannot modify readonly property values'
    revisions(work, 'php', dict(main=compound, model=holder), [
        (None, None, b'6'), ('model', readonly, denied), ('main', update, denied),
        ('model', holder, b'4')])


def admission(work):
    store = 'function store(vector<Holder> $owners):void { $owners[0]->values[0]=7; }'
    grouped = store.replace('$owners[0]->', '($owners[0])->')
    inline = 'struct Holder { public vector<int> $values; }'
    readonly = ('class Holder { public readonly vector<int> $values; '
                'public function __construct(vector<int> $v) { $this->values=$v; } }')
    revisions(work, 'php', {'main': 'return 8;', 'api': store, 'model': inline}, [
        (None, None, diagnostic), ('api', grouped, diagnostic),
        ('api', store.replace('=7;', '+=7;'), diagnostic),
        ('api', grouped.replace('=7;', '++;'), diagnostic), ('api', store, diagnostic),
        ('model', readonly, 'Cannot modify readonly property values'),
        ('model', model, b'')])


with TemporaryDirectory(prefix='indexed-owner-target-') as tmp:
    cases = {'targets': targets, 'replacement': replacement, 'lifetime': lifetime, 'admission': admission, 'append': append}
    cases.update(mutations=mutations, mutation_access=mutation_access)
    case = sys.argv[1] if len(sys.argv) > 1 else 'targets'
    if case.startswith('missing_'):
        missing(Path(tmp), case)
    else:
        cases[case](Path(tmp))
