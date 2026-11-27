"""Two saved keys write into the current field of the originally selected holder."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import revisions
from populated_vectors_native import declaration


def exercise(work, flavor):
    f = fixture(flavor)
    v, access = f['v'], f['access']
    model, main, expected = '', '', b''
    api = f['function']('groupKey', 'string', [], 'echo "G"; return "team";')
    api += f['function']('leafKey', 'int', [], 'echo "K"; return 0;')
    for family in ('mixed', 'dynamic'):
        holder = 'Holder' + family
        model += f['klass'](holder, f['field']('data', family))
        build, choose, rebind, replace = [name + family for name in ('build', 'choose', 'rebind', 'replace')]
        create = declaration(flavor, 'row', family, '[]') + declaration(flavor, 'root', family, '[]')
        create += f'{v}row[0]={v}seed; {v}row[1]={v}seed; {v}root["team"]={v}row; return {v}root;'
        api += f['function'](build, family, [('seed', 'int')], create)
        api += f['function'](choose, holder, [('owner', holder)], f'echo "H"; return {v}owner;')
        field = v + 'owner' + access + 'data'
        api += f['function'](rebind, 'int', [('&owner', holder)],
                             f'echo "B"; {v}owner=new {holder}(); {field}={build}(9); return 6;').replace('$&', '&$')
        api += f['function'](replace, 'int', [('owner', holder)], f'echo "F"; {field}={build}(4); return 7;')
        name = 'owner' + family
        owner = v + name
        saved = v + 'saved' + family
        row = v + 'row' + family
        main += f['infer'](name, 'new ' + holder + '()') + f'{owner}{access}data={build}(2);'
        main += f['infer']('saved' + family, owner)
        main += declaration(flavor, 'before' + family, family, owner + access + 'data')
        target = f'{choose}({owner}){access}data[groupKey()][leafKey()]'
        main += f'{target}={rebind}({owner});'
        main += declaration(flavor, 'row' + family, family, f'{saved}{access}data["team"]')
        main += f'echo (int){row}[0]; {row}={owner}{access}data["team"]; echo (int){row}[0];'
        main += f'{row}={v}before{family}["team"]; echo (int){row}[0];'
        main += f'{target}={replace}({owner}); {row}={owner}{access}data["team"]; echo (int){row}[0],(int){row}[1];'
        expected += b'HGKB69' + (b'2' if family == 'mixed' else b'6') + b'HGKF74'
    revisions(work, flavor, {'main': main + 'return 8;', 'model': model, 'api': api}, [
        (None, None, expected), ('model', model.replace('data', 'renamed'), 'Unknown field'),
        ('model', model, expected)])


def lifetime(work):
    model = 'class Holder { public mixed $data; public function __destruct() { echo "D"; } }'
    api = 'function rebind(Holder &$holder):int { $holder=new Holder(); echo "R"; return 6; }'
    main = '$holder=new Holder(); $root mixed=[]; $row mixed=[]; $root["team"]=$row; $holder->data=$root;'
    main += '$holder->data["team"][0]=rebind($holder); echo "E"; return 8;'
    revisions(work, 'php', {'main': main, 'api': api, 'model': model}, [(None, None, b'RDED')])


def field_access(work):
    model = ('class Holder { public readonly FAMILY $data; '
             'public function __construct(FAMILY $value) { $this->data=$value; } }')
    main = ('$root FAMILY=[]; $row FAMILY=[]; $root["team"]=$row; $h=new Holder($root); '
            '$h->data["team"][0]=8; echo (int)$row[0]; return 8;')
    source = main.replace('FAMILY', 'dynamic')
    api = model.replace('FAMILY', 'dynamic')
    # A readonly dynamic field permits mutation through the shared child.
    # A mixed field would have to publish a changed value into readonly storage.
    program = api + source
    revisions(work, 'php', {'main': program}, [
        (None, None, b'8'), ('main', program.replace('dynamic', 'mixed'), 'Cannot modify readonly property data'),
        ('main', program.replace('[0]=8', '[]=8'), 'Append requires writable local storage'),
        ('main', program.replace('[0]=8', '[0]+=8'), 'Nested boxed indexing is deferred'),
        ('main', program.replace('[0]=8', '[0][1]=8'), 'Nested boxed indexing is deferred'),
        ('main', program, b'8')])


with TemporaryDirectory(prefix='nested-boxed-field-') as tmp:
    if sys.argv[1] == 'last_owner':
        lifetime(Path(tmp))
    elif sys.argv[1] == 'field_access':
        field_access(Path(tmp))
    else:
        exercise(Path(tmp), sys.argv[1])
