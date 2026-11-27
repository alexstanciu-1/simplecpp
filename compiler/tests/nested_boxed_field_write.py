"""Indexed writes and appends select the current field of the original holder."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
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
        # Read 2 before the RHS, then write into the replacement on the original owner.
        change = 'change' + family
        body = f'{field}={v}replacement; {v}owner=new {holder}(); {field}={build}(9); echo "R"; return 6;'
        api += f['function'](change, 'int', [('&owner', holder), ('replacement', family)], body).replace('$&', '&$')
        main += f'{owner}{access}data={build}(2); {saved}={owner};'
        fresh = v + 'fresh' + family
        main += declaration(flavor, 'fresh' + family, family, f'{build}(40)')
        main += f'{target}+={change}({owner},{fresh});'
        main += f'{row}={saved}{access}data["team"]; echo (int){row}[0],(int){row}[1];'
        main += f'{row}={owner}{access}data["team"]; echo (int){row}[0];'
        main += f'{row}={fresh}["team"]; echo (int){row}[0];'
        expected += b'HGKR8409' + (b'40' if family == 'mixed' else b'8')
        for update, result in [('++' + target, b'10'), (target + '++', b'11'),
                               ('--' + target, b'10'), (target + '--', b'9')]:
            main += f'{update}; {row}={owner}{access}data["team"]; echo (int){row}[0];'
            expected += b'HGK' + result
    revisions(work, flavor, {'main': main + 'return 8;', 'model': model, 'api': api}, [
        (None, None, expected), ('model', model.replace('data', 'renamed'), 'Unknown field'),
        ('model', model, expected)])


def append(work, flavor):
    f = fixture(flavor)
    v, access = f['v'], f['access']
    model, main, expected = '', '', b''
    api = f['function']('groupKey', 'string', [], 'echo "G"; return "team";')
    for family in ('mixed', 'dynamic'):
        holder = 'Holder' + family
        model += f['klass'](holder, f['field']('data', family))
        owner, saved, old, fresh, root, seen = [v + name + family for name in ('owner', 'saved', 'old', 'fresh', 'root', 'seen')]
        for name in ('old', 'fresh', 'root'):
            main += declaration(flavor, name + family, family, '[]')
        main += f'{old}[2]=2; {fresh}[8]=8; {root}["team"]={old};'
        main += f['infer']('owner' + family, 'new ' + holder + '()') + f'{owner}{access}data={root};'
        main += f['infer']('saved' + family, owner)
        main += declaration(flavor, 'replacement' + family, family, '[]')
        replacement = v + 'replacement' + family
        main += f'{replacement}["team"]={fresh};'
        select, change = 'select' + family, 'change' + family
        api += f['function'](select, holder, [('h', holder)], f'echo "H"; return {v}h;')
        body = declaration(flavor, 'emptyRoot', family, '[]')
        body += f'{v}h{access}data={v}replacement; {v}h=new {holder}(); {v}h{access}data={v}emptyRoot; echo "R"; return 7;'
        api += f['function'](change, 'int', [('&h', holder), ('replacement', family)], body).replace('$&', '&$')
        main += f'{select}({owner}){access}data[groupKey()][]={change}({owner},{replacement});'
        main += declaration(flavor, 'seen' + family, family, f'{saved}{access}data["team"]')
        main += f'echo (int){seen}[9],count({seen}),count({owner}{access}data),count({old}),count({fresh});'
        expected += b'HGR7201' + (b'1' if family == 'mixed' else b'2')
    revisions(work, flavor, {'main': main + 'return 8;', 'api': api, 'model': model}, [
        (None, None, expected), ('model', model.replace('data', 'renamed'), 'Unknown field'), ('model', model, expected)])


def removed(work, family):
    model = f'class Holder {{ public {family} $data; }}'
    api = 'function removeGroup(Holder $h):int { $items=$h->data; $items->remove("team"); $h->data=$items; echo "R"; return 7; }'
    main = f'$h=new Holder(); $root {family}=[]; $row {family}=[]; $root["team"]=$row; $h->data=$root;'
    main += 'try { $h->data["team"][]=removeGroup($h); } finally { echo count($h->data), count($row); }'
    guard(work, 'php', family, model + api + main + 'return 0;', 'boxed_missing_key', expected_stdout=b'R00')


def missing_leaf(work, family, case):
    model = f'class Holder {{ public {family} $data; }}'
    api = ('function rhs(Holder $h):int { $row=$h->data["team"]; $row->remove(0); '
           '$h->data["team"]=$row; echo "R"; return 6; }')
    main = f'$h=new Holder(); $root {family}=[]; $row {family}=[];'
    if case == 'removed_leaf':
        main += '$row[0]=2;'
    main += '$root["team"]=$row; $h->data=$root; try { $h->data["team"][0]+=rhs($h); }'
    main += 'finally { $current=$h->data["team"]; echo count($current); } return 0;'
    guard(work, 'php', case, model + api + main, 'boxed_missing_key',
          expected_stdout=b'R0' if case == 'removed_leaf' else b'0')


def lifetime(work):
    model = 'class Holder { public mixed $data; public function __destruct() { echo "D"; } }'
    api = 'function rebind(Holder &$holder):int { $holder=new Holder(); echo "R"; return 6; }'
    main = '$holder=new Holder(); $root mixed=[]; $row mixed=[]; $root["team"]=$row; $holder->data=$root;'
    main += '$holder->data["team"][0]=rebind($holder); echo "E"; return 8;'
    revisions(work, 'php', {'main': main, 'api': api, 'model': model}, [
        (None, None, b'RDED'), ('main', main.replace('[0]=rebind', '[]=rebind'), b'RDED'),
        ('main', main.replace('$row mixed=[];', '$row mixed=[]; $row[0]=2;').replace('[0]=rebind', '[0]+=rebind'), b'RDED')])


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
        ('main', program.replace('[0]=8', '[]=8'), b'8'),
        ('main', program.replace('[0]=8', '[]=8').replace('dynamic', 'mixed'), 'Cannot modify readonly property data'),
        ('main', program.replace('$row dynamic=[];', '$row dynamic=[]; $row[0]=2;').replace('[0]=8', '[0]+=8'), b'10'),
        ('main', program.replace('dynamic', 'mixed').replace('[0]=8', '[0]+=8'), 'Cannot modify readonly property data'),
        ('main', program.replace('[0]=8', '[0]=($h->data["team"][0])'), 'Nested boxed indexing is deferred'),
        ('main', program, b'8')])


with TemporaryDirectory(prefix='nested-boxed-field-') as tmp:
    if sys.argv[1] == 'append':
        append(Path(tmp), sys.argv[2])
    elif sys.argv[1] in ('missing_leaf', 'removed_leaf'):
        missing_leaf(Path(tmp), sys.argv[2], sys.argv[1])
    elif sys.argv[1] == 'removed':
        removed(Path(tmp), sys.argv[2])
    elif sys.argv[1] == 'last_owner':
        lifetime(Path(tmp))
    elif sys.argv[1] == 'field_access':
        field_access(Path(tmp))
    else:
        exercise(Path(tmp), sys.argv[1])
