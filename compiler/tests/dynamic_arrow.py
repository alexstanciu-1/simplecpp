"""Named dynamic keys share bracket lookup, owner selection and post-RHS write-back."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def exercise(work, flavor):
    f = fixture(flavor)
    v, a = f['v'], f['access']
    model = f['klass']('Holder', f['field']('data', 'dynamic'))
    key = f['function']('key', 'string', [], 'echo "K";return "name";')
    owner = f['function']('choose', 'Holder', [('h', 'Holder')], f'echo "H";return {v}h;')
    rhs = f['function']('replaceChild', 'int', [('h', 'Holder'), ('fresh', 'dynamic')],
                        f'echo "R";{v}h{a}data["user"]={v}fresh;return 8;')
    setup = f['infer']('h', 'new Holder()')
    for name in ('root', 'old', 'fresh'):
        setup += declaration(flavor, name, 'dynamic', '[]')
    setup += f'{v}h{a}data={v}root;{v}root{a}user={v}old;{v}old{a}name=3;'
    target = f'choose({v}h){a}data{a}user[key()]'
    check = f'echo (int){v}root["user"]{a}name,(int){v}old["name"],(int){v}fresh{a}name;'
    method = f'{v}root{a}remove=2;echo (int){v}root{a}remove;{v}root{a}remove("remove");'
    method += f'echo {v}root{a}has("remove") ? 1 : 0;'
    more = f'{v}root["user"]{a}name+=2; ++{v}root{a}user{a}name; {v}root{a}user{a}name--;'
    more += f'{v}root{a}user[]=4;echo (int){v}root{a}user{a}name,(int){v}fresh[0];'
    main = setup + target + f'=replaceChild({v}h,{v}fresh);' + check + method + more + 'return 8;'
    revisions(work, flavor, {'main': main, 'model': model, 'api': key + owner + rhs}, [
        (None, None, b'HKR83820104'),
        ('main', main.replace(target, f'choose({v}h){a}data["user"]{a}name'), b'HR83820104'),
        ('model', model.replace('dynamic', 'mixed'), 'conversion'), ('model', model, b'HR83820104'),
    ])


def missing(work, flavor):
    f = fixture(flavor)
    v, a = f['v'], f['access']
    start = declaration(flavor, 'd', 'dynamic', '[]')
    rhs = f['function']('rhs', 'int', [], 'echo "R";return 3;')
    for label, path in [('named', f'{a}missing["x"]'), ('index', f'["missing"]{a}x')]:
        guard(work, flavor, label, rhs + start + v + 'd' + path + '=rhs();return 1;',
              'boxed_missing_key', expected_stdout=b'R')


def computed(work, flavor):
    api = 'function makeName():string {echo "K";return "remove";}'
    main = """
        function receiver(dynamic $d):dynamic {echo "R";return $d;}
        $d dynamic=[];$d[0]=3;$d["01"]=4;$d["remove"]=7;
        echo (int)receiver($d)->{makeName()};
        $name="0";echo (int)$d->{$name},(int)$d->{"0" . "1"};
        echo is_null($d->{"absent"}),$d->has("absent") ? 1 : 0;
        $d->remove("remove");echo is_null($d->{"remove"});return 8;
    """
    trace = b'RK734101'
    revisions(work, flavor, dict(main=main, api=api), [
        (None, None, trace), ('api', 'function makeName():int {return 0;}', 'Computed member name requires a string'),
        ('api', api, trace)])


def computed_write(work, flavor):
    api = 'function name():string {echo "K";return "remove";}'
    main = """
        function rhs(bool $fail):int {
            echo "R";if ($fail) {throw new Exception("stop");}return 7;
        }
        $d dynamic=[];$d->{"remove"}=3;echo (int)$d["remove"];
        $d->{name()}=rhs(false);echo (int)$d["remove"];
        try {$d->{name()}=rhs(true);} catch (Exception $e) {echo $e->get_message();}
        echo (int)$d->remove;$d->remove("remove");echo is_null($d["remove"]);return 8;
    """
    trace = b'3KR7KRstop71'
    revisions(work, flavor, dict(main=main, api=api), [
        (None, None, trace), ('api', 'function name():int {return 1;}', 'Computed member name requires a string'),
        ('api', api, trace)])


def computed_mutation(work, flavor):
    api = 'function name():string {echo "K";return "remove";}'
    main = """
        function change(dynamic $d):int {echo "R";$d["remove"]=99;return 3;}
        $d dynamic=[];$d["remove"]=4;
        $d->{name()}+=change($d);echo (int)$d["remove"];
        ++$d->{name()};echo (int)$d["remove"];
        $d->{name()}++;echo (int)$d["remove"];
        --$d->{name()};echo (int)$d["remove"];
        $d->{name()}--;echo (int)$d["remove"];
        $d["remove"]="a";$d->{name()}.="b";echo (string)$d["remove"];return 8;
    """
    trace = b'KR7K8K9K8K7Kab'
    revisions(work, flavor, dict(main=main, api=api), [
        (None, None, trace), ('api', 'function name():int {return 0;}', 'Computed member name requires a string'),
        ('api', api, trace)])


def computed_mutation_failure(work, flavor):
    source = """
        function name():string {echo "K";return "key";}
        function removeKey(dynamic $d):int {echo "R";$d->remove("key");return 2;}
        $d dynamic=[];
    """
    operation = 'try {$d->{name()}+=removeKey($d);} finally {echo count($d);}return 0;'
    for label, setup, trace in [('missing', '', b'K0'), ('removed', '$d["key"]=4;', b'KR0')]:
        guard(work, flavor, label, source + setup + operation, 'boxed_missing_key', expected_stdout=trace)


NESTED_TARGETS = ('$d->{groupKey()}[leafKey()]', '$d[groupKey()]->{leafKey()}', '$d->{groupKey()}->{leafKey()}')
NESTED_KEYS = 'function groupKey():string {echo "G";return "group";} function leafKey():string {echo "K";return "leaf";}'


def computed_nested(work, flavor):
    api = NESTED_KEYS + 'function replaceChild(dynamic $d,dynamic $fresh):int {echo "R";$d["group"]=$fresh;return 9;}'
    main = ''
    for i, target in enumerate(NESTED_TARGETS):
        main += f'function run{i}():void {{'
        main += '$d dynamic=[];$old dynamic=[];$fresh dynamic=[];$old["leaf"]=2;$d["group"]=$old;'
        main += target + '=replaceChild($d,$fresh);'
        main += 'echo (int)$old["leaf"],(int)$d["group"]["leaf"],(int)$fresh["leaf"];}'
        main += f'run{i}();'
    trace = b'GKR299' * len(NESTED_TARGETS)
    bad_api = api.replace('groupKey():string', 'groupKey():int').replace('return "group"', 'return 1')
    revisions(work, flavor, dict(main=main + 'return 8;', api=api), [
        (None, None, trace), ('api', bad_api, 'Computed member name requires a string'), ('api', api, trace)])


def computed_nested_missing(work, flavor):
    for label, target in zip(('first', 'second'), NESTED_TARGETS[:2]):
        source = NESTED_KEYS + 'function rhs():int {echo "R";return 9;} $d dynamic=[];'
        guard(work, flavor, label, source + 'try {' + target + '=rhs();} finally {echo count($d);}return 0;',
              'boxed_missing_key', expected_stdout=b'GKR0')


def computed_admission(work, flavor):
    setup = '$d dynamic=[];$name="key";'
    cases = [
        ('$d mixed=[];$x=$d->{"key"};', 'Computed member read requires a dynamic receiver'),
        ('class H {public int $key;}$h=new H();$x=$h->{"key"};', 'Computed member read requires a dynamic receiver'),
        (setup + '$x=$d->{$name}();', 'Computed member calls are not supported'),
        ('$d mixed=[];$d->{"key"}=7;', 'Computed member assignment requires a local dynamic receiver'),
        ('class H {public dynamic $data;}$h=new H();$h->data->{"key"}=7;', 'Expected writable struct field'),
        ('$d mixed=[];$d->{"key"}+=7;', 'Computed member read requires a dynamic receiver'),
        ('class H {public dynamic $data;}$h=new H();++$h->data->{"key"};', 'Computed member mutation requires a local dynamic receiver'),
        (setup + '++$d->{$name}["leaf"];', 'Computed member reads cannot be used as write targets'),
        (setup + '$d->{$name}["leaf"]+=7;', 'Computed member reads cannot be used as write targets'),
        (setup + '$d[0]->{1}=7;', 'Computed member name requires a string'),
    ]
    revisions(work, flavor, dict(main='return 8;'), [('main', source + 'return 8;', error) for source, error in cases])


with TemporaryDirectory(prefix='dynamic-arrow-') as tmp:
    {'missing': missing, 'computed': computed, 'computed_write': computed_write, 'computed_admission': computed_admission,
     'computed_mutation': computed_mutation, 'computed_mutation_failure': computed_mutation_failure,
     'computed_nested': computed_nested, 'computed_nested_missing': computed_nested_missing,
     'exercise': exercise}[sys.argv[1]](Path(tmp), sys.argv[2])
