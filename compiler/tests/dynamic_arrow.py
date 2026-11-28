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


def computed_admission(work, flavor):
    setup = '$d dynamic=[];$name="key";'
    cases = [
        ('$d mixed=[];$x=$d->{"key"};', 'Computed member read requires a dynamic receiver'),
        ('class H {public int $key;}$h=new H();$x=$h->{"key"};', 'Computed member read requires a dynamic receiver'),
        (setup + '$x=$d->{$name}();', 'Computed member calls are not supported'),
        ('$d mixed=[];$d->{"key"}=7;', 'Computed member assignment requires a local dynamic receiver'),
        ('class H {public dynamic $data;}$h=new H();$h->data->{"key"}=7;', 'Expected writable struct field'),
        (setup + '$d->{$name}+=7;', 'Expected writable struct field'),
        (setup + '++$d->{$name};', 'Computed member reads cannot be used as write targets'),
        (setup + '$d->{$name}["leaf"]=7;', 'Expected writable struct field'),
    ]
    revisions(work, flavor, dict(main='return 8;'), [('main', source + 'return 8;', error) for source, error in cases])


with TemporaryDirectory(prefix='dynamic-arrow-') as tmp:
    {'missing': missing, 'computed': computed, 'computed_write': computed_write, 'computed_admission': computed_admission,
     'exercise': exercise}[sys.argv[1]](Path(tmp), sys.argv[2])
