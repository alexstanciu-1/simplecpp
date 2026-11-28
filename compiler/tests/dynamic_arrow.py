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


def computed_append(work, flavor):
    api = 'function groupKey():string {echo "G";return "group";}'
    main = """
        function replace(dynamic $d,dynamic $fresh):int {$d["group"]=$fresh;echo "R";return 7;}
        $d dynamic=[];$old dynamic=[];$fresh dynamic=[];$old[2]=2;$fresh[8]=8;$d["group"]=$old;
        $d->{groupKey()}[]=replace($d,$fresh);
        echo (int)$d["group"][9],(int)$fresh[9],count($old),(int)$old[2];return 8;
    """
    trace = b'GR7712'
    revisions(work, flavor, dict(main=main, api=api), [
        (None, None, trace), ('api', 'function groupKey():int {return 1;}', 'Computed member name requires a string'),
        ('api', api, trace)])


def computed_append_missing(work, flavor):
    source = NESTED_KEYS + 'function rhs():int {echo "R";return 3;} $d dynamic=[];'
    guard(work, flavor, 'missing', source + 'try {$d->{groupKey()}[]=rhs();} finally {echo count($d);}return 0;',
          'boxed_missing_key', expected_stdout=b'GR0')


def computed_nested_mutation(work, flavor):
    api = NESTED_KEYS + 'function replace(dynamic $d,dynamic $fresh):int {$d["group"]=$fresh;echo "R";return 3;}'
    main = ''
    for i, target in enumerate(NESTED_TARGETS):
        main += f'function run{i}():void {{'
        main += '$d dynamic=[];$old dynamic=[];$fresh dynamic=[];$d["group"]=$old;$old["leaf"]=4;$fresh["leaf"]=99;'
        main += target + '+=replace($d,$fresh);echo (int)$old["leaf"],(int)$fresh["leaf"];'
        for update in ('++{}', '{}++', '--{}', '{}--'):
            main += update.format(target) + ';echo (int)$fresh["leaf"];'
        main += f'}}run{i}();'
    trace = b'GKR47GK8GK9GK8GK7' * len(NESTED_TARGETS)
    bad_api = api.replace('leafKey():string', 'leafKey():int').replace('return "leaf"', 'return 1')
    revisions(work, flavor, dict(main=main + 'return 8;', api=api), [
        (None, None, trace), ('api', bad_api, 'Computed member name requires a string'), ('api', api, trace)])


def computed_nested_mutation_failure(work, flavor):
    api = NESTED_KEYS + 'function removeLeaf(dynamic $row):int {echo "R";$row->remove("leaf");return 3;}'
    for case, setup, trace in [('missing', '', b'GK0'), ('removed', '$row["leaf"]=4;', b'GKR0')]:
        main = '$d dynamic=[];$row dynamic=[];$d["group"]=$row;' + setup
        main += 'try {$d->{groupKey()}->{leafKey()}+=removeLeaf($row);} finally {echo count($row);}return 0;'
        guard(work, flavor, case, api + main, 'boxed_missing_key', expected_stdout=trace)


DEEP_KEYS = ('function key(string $k):string {echo $k;return $k;} '
             'function middle():string {echo "M";return "m";} function rhs():int {echo "R";return 4;}')


def computed_deep(work, flavor):
    main = """
        $d dynamic=[];$region dynamic=[];$child dynamic=[];$d["a"]=$region;$region["m"]=$child;
        $d[key("a")]->{middle()}->{key("x")}=rhs();echo (int)$child["x"];
        $d->{key("a")}->{middle()}[key("x")]+=rhs();echo (int)$child["x"];
        ++$d[key("a")]->{middle()}[key("x")];echo (int)$child["x"];
        $d[key("a")]->{middle()}->{key("x")}--;echo (int)$child["x"];
        $list dynamic=[];$child["l"]=$list;$list[6]=2;
        $d[key("a")]->{middle()}[key("l")][]=rhs();echo (int)$list[7];return 8;
    """
    trace = b'aMxR4aMxR8aMx9aMx8aMlR4'
    bad_api = DEEP_KEYS.replace('middle():string', 'middle():int').replace('return "m"', 'return 1')
    revisions(work, flavor, dict(main=main, api=DEEP_KEYS), [
        (None, None, trace), ('api', bad_api, 'Computed member name requires a string'), ('api', DEEP_KEYS, trace)])


def computed_deep_missing(work, flavor):
    target = '$d[key("a")]->{middle()}[key("x")]'
    for label, operator, trace in [('assign', '=', b'aMxR0'), ('compound', '+=', b'aMx0')]:
        main = '$d dynamic=[];try {' + target + operator + 'rhs();} finally {echo count($d);}return 0;'
        guard(work, flavor, label, DEEP_KEYS + main, 'boxed_missing_key', expected_stdout=trace)


FIELD_MODEL = """
    class Holder {
        public dynamic $data;
        public function __construct(dynamic $root) {$this->data=$root;}
        public function __destruct() {echo "D",(int)$this->data["group"][9];}
    }
"""
FIELD_API = """
    function owner():int {echo "O";return 0;}
    function groupKey():string {echo "G";return "group";}
    function leafKey():string {echo "K";return "9";}
    function install(Holder $h,dynamic $next):void {$h->data=$next;}
    function swap(vector<Holder> &$owners,dynamic $fresh,dynamic $other):int {
        $next dynamic=[];$next["group"]=$fresh;install($owners[0],$next);
        $owners[0]=new Holder($other);echo "R";return 3;
    }
"""
FIELD_SETUP = """
    $root dynamic=[];$old dynamic=[];$old[9]=4;$root["group"]=$old;
    $other dynamic=[];$untouched dynamic=[];$untouched[9]=90;$other["group"]=$untouched;
    $fresh dynamic=[];$fresh[8]=8;$owners vector<Holder>=[new Holder($root)];
"""


def computed_field_store(work, flavor):
    main = ''
    for i, suffix in enumerate(('->{leafKey()}', '[]')):
        main += f'function run{i}():void {{' + FIELD_SETUP
        main += '$owners[owner()]->data->{groupKey()}' + suffix + '=swap($owners,$fresh,$other);'
        main += 'echo "E",(int)$old[9],(int)$fresh[9],(int)$owners[0]->data["group"][9];}'
        main += f'run{i}();'
    trace = b'OGKRD3E4390D90OGRD3E4390D90'
    readonly = FIELD_MODEL.replace('public dynamic', 'public readonly dynamic')
    revisions(work, flavor, dict(main=main + 'return 8;', api=FIELD_API, model=FIELD_MODEL), [
        (None, None, trace), ('model', readonly, 'Cannot modify readonly property data'), ('model', FIELD_MODEL, trace)])


def computed_field_mutation(work, flavor):
    main = FIELD_SETUP + """
        $fresh[9]=99;
        $owners[owner()]->data->{groupKey()}->{leafKey()}+=swap($owners,$fresh,$other);
        echo "E",(int)$old[9],(int)$fresh[9],(int)$owners[0]->data["group"][9];return 8;
    """
    key_api = """
        function rebindKey(vector<Holder> &$owners,dynamic $other):string {
            $owners[0]=new Holder($other);echo "G";return "group";
        }
    """
    key_case = FIELD_SETUP + """
        $owners[owner()]->data->{rebindKey($owners,$other)}->{leafKey()}+=3;
        echo "E",(int)$old[9],(int)$owners[0]->data["group"][9];
    """
    main = 'function rhsCase():int {' + main + '} function keyCase():void {' + key_case + '} rhsCase();keyCase();return 8;'
    trace = b'OGKRD7E4790D90OGKD7E790D90'
    readonly = FIELD_MODEL.replace('public dynamic', 'public readonly dynamic')
    revisions(work, flavor, dict(main=main, api=FIELD_API + key_api, model=FIELD_MODEL), [
        (None, None, trace), ('model', readonly, 'Cannot modify readonly property data'), ('model', FIELD_MODEL, trace)])


def computed_field_readonly(work, flavor):
    model = 'class Holder {public dynamic $data; public function __construct(dynamic $v) {$this->data=$v;}}'
    api = 'function name():string {echo "K";return "group";}'
    main = """
        $root dynamic=[];$h=new Holder($root);$child dynamic=[];$h->data->{name()}=$child;
        $h->data->{name()}[]=3;
        ++$h->data->{name()}[0];echo (int)$child[0];
        $h->data["group"]->{"0"}++;echo (int)$child[0];
        --$h->data->{name()}[0];echo (int)$child[0];
        $h->data->{name()}[0]--;echo (int)$child[0];
        $h->data->{"n"}=4;$h->data->{"n"}+=3;echo (int)$root["n"];return 8;
    """
    trace = b'KKK45K4K37'
    readonly = model.replace('public dynamic', 'public readonly dynamic')
    revisions(work, flavor, dict(main=main, api=api, model=model), [
        (None, None, trace), ('model', readonly, trace), ('model', model, trace)])


def computed_field_missing(work, flavor):
    model = 'class Holder {public dynamic $data;}'
    api = NESTED_KEYS + 'function removeLeaf(dynamic $row):int {echo "R";$row->remove("leaf");return 3;}'
    setup = '$root dynamic=[];$row dynamic=[];$h=new Holder();$root["group"]=$row;$h->data=$root;'
    for case, seed, trace in [('missing', '', b'GK0'), ('removed', '$row["leaf"]=4;', b'GKR0')]:
        main = setup + seed + 'try {$h->data->{groupKey()}->{leafKey()}+=removeLeaf($row);} finally {echo count($row);}return 0;'
        guard(work, flavor, case, model + api + main, 'boxed_missing_key', expected_stdout=trace)


def computed_static(work, flavor):
    model = 'class Registry {public static dynamic $data;}'
    reader = """
        function valueAt(string $k):int {return (int)Registry::$data[$k];}
        function key(string $k):string {echo "K";return $k;}
        function install(dynamic $next):int {echo "R";Registry::$data=$next;return 3;}
    """
    main = """
        echo is_null(Registry::$data);
        $old dynamic={"n":4};$next dynamic={"n":90};Registry::$data=$old;
        Registry::$data->{key("n")}+=install($next);
        echo valueAt("n"),(int)$old["n"];
        ++Registry::$data->{key("n")};echo valueAt("n");
        Registry::$data->{key("n")}--;echo valueAt("n");
        Registry::$data->{key("n")}++;echo valueAt("n");
        --Registry::$data->{key("n")};echo valueAt("n");
        Registry::$data->{key("n")}=install($old);echo valueAt("n"),(int)$next["n"];
        $a dynamic=[10];$b dynamic=[20,30];
        $old["list"]=$a;$next["list"]=$b;
        Registry::$data->{key("list")}[]=install($next);
        echo count($a),count($b),(int)$b[2];
        Registry::$data->{key("list")}[0]+=2;echo (int)$b[0];
        Registry::$data["list"]->{key("0")}=11;echo (int)$b[0];return 8;
    """
    trace = b'1KR74K8K7K8K7KR37KR133K22K11'
    revisions(work, flavor, dict(main=main, model=model, reader=reader), [
        (None, None, trace), ('model', model.replace('$data;', '$other;'), 'Unknown static property'),
        ('model', model.replace('$data;', '$data=null;'), trace)])


def computed_static_limits(work, flavor):
    model = 'struct Registry {public static dynamic $data;}'
    revisions(work, flavor, dict(main='return 8;'), [
        ('main', model + 'Registry::$data->{"x"} ??= 1;return 8;', 'requires a local or writable instance field'),
        ('main', model.replace('$data;', '$data=7;') + 'return 8;', 'Static dynamic property starts as null'),
        ('main', 'global $data dynamic = 7;return 8;', 'globals are deferred')])
    guard(work, flavor, 'null', model + 'Registry::$data->{"x"}=1;return 8;', 'dynamic_requires_container')


def computed_admission(work, flavor):
    setup = '$d dynamic=[];$name="key";'
    cases = [
        ('$d mixed=[];$x=$d->{"key"};', 'Computed member read requires a dynamic receiver'),
        ('class H {public int $key;}$h=new H();$x=$h->{"key"};', 'Computed member read requires a dynamic receiver'),
        (setup + '$x=$d->{$name}();', 'Computed member calls are not supported'),
        ('$d mixed=[];$d->{"key"}=7;', 'Computed member assignment requires a dynamic local, writable instance field or named static property'),
        ('class H {public mixed $data;}$h=new H();$h->data->{"key"}=7;', 'Computed member assignment requires a dynamic local, writable instance field or named static property'),
        ('$d mixed=[];$d->{"key"}+=7;', 'Computed member read requires a dynamic receiver'),
        ('function make():dynamic {$d dynamic=[];return $d;} ++make()->{"mid"}["key"];', 'Computed member write requires a dynamic local, stable instance field or named static property'),
        (setup + '$d[0]->{1}=7;', 'Computed member name requires a string'),
    ]
    revisions(work, flavor, dict(main='return 8;'), [('main', source + 'return 8;', error) for source, error in cases])


with TemporaryDirectory(prefix='dynamic-arrow-') as tmp:
    {'missing': missing, 'computed': computed, 'computed_write': computed_write, 'computed_admission': computed_admission,
     'computed_mutation': computed_mutation, 'computed_mutation_failure': computed_mutation_failure,
     'computed_nested': computed_nested, 'computed_nested_missing': computed_nested_missing,
     'computed_append': computed_append, 'computed_append_missing': computed_append_missing,
     'computed_nested_mutation': computed_nested_mutation, 'computed_nested_mutation_failure': computed_nested_mutation_failure,
     'computed_deep': computed_deep, 'computed_deep_missing': computed_deep_missing,
     'computed_static': computed_static, 'computed_static_limits': computed_static_limits, 'computed_field_store': computed_field_store, 'computed_field_mutation': computed_field_mutation,
     'computed_field_readonly': computed_field_readonly, 'computed_field_missing': computed_field_missing,
     'exercise': exercise}[sys.argv[1]](Path(tmp), sys.argv[2])
