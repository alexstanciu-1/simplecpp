"""Read the existing leaf before RHS effects, then write to the current path."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def exercise(work, flavor, storage):
    f = fixture(flavor)
    v, access = f['v'], f['access']
    key = f['function']('key', 'string', [('name', 'string')], f'echo {v}name; return {v}name;')
    bad_key = f['function']('key', 'bool', [('name', 'string')], 'return true;')
    main, model, api, expected = '', '', '', b''
    for family in ('mixed', 'dynamic'):
        holder = 'Holder' + family
        model += f['klass'](holder, f['field']('data', family))
        root, region, old, fresh, saved, seen = [v + name + family for name in ('root', 'region', 'old', 'fresh', 'saved', 'seen')]
        for name in ('root', 'region', 'old', 'fresh'):
            main += declaration(flavor, name + family, family, '[]')
        main += f'{old}[0]=2; {fresh}[0]=40; {fresh}[1]=7; {region}["g"]={old}; {root}["r"]={region};'
        main += declaration(flavor, 'saved' + family, family, root)
        change = 'change' + family
        if storage == 'field':
            owner = v + 'owner' + family
            main += f['infer']('owner' + family, 'new ' + holder + '()') + f'{owner}{access}data={root};'
            main += f['infer']('kept' + family, owner)
            target_root = owner + access + 'data'
            current = v + 'kept' + family + access + 'data'
            params = [('&h', holder), ('child', family)]
            body = f'{v}h{access}data["r"]["g"]={v}child; {v}h=new {holder}();'
            argument = owner
        else:
            target_root = current = argument = root
            params = [('&data', family), ('child', family)]
            body = f'{v}data["r"]["g"]={v}child;'
        api += f['function'](change, 'int', params, body + 'echo "V"; return 6;').replace('$&', '&$')
        path = '[key("r")][key("g")][key("0")]'
        main += f'{target_root}{path}+={change}({argument},{fresh});'
        main += declaration(flavor, 'seen' + family, family, f'{current}["r"]')
        main += f'{seen}={seen}["g"]; echo (int){seen}[0],(int){seen}[1],(int){old}[0],(int){fresh}[0];'
        expected += b'rg0V872' + (b'40' if family == 'mixed' else b'8')
        for update, value in [('++{}', 9), ('{}++', 10), ('--{}', 9), ('{}--', 8)]:
            main += update.format(current + path) + f'; {seen}={current}["r"]; {seen}={seen}["g"]; echo (int){seen}[0];'
            expected += b'rg0' + str(value).encode()
        main += f'{seen}={saved}["r"]; {seen}={seen}["g"]; echo (int){seen}[0];'
        expected += b'2' if family == 'mixed' else b'8'
    revisions(work, flavor, {'main': main + 'return 8;', 'api': key + api, 'model': model}, [
        (None, None, expected), ('api', bad_key + api, 'Boxed container key requires int or string'),
        ('api', key + api, expected)])


def failure(work, family, case):
    api = (f'function rhs({family} &$data):int {{ $region=$data["r"]; $child=$region["g"]; '
           '$child->remove(0); $data["r"]["g"]=$child; echo "V"; return 6; }')
    main = f'$data {family}=[]; $region {family}=[]; $child {family}=[];'
    if case == 'removed':
        main += '$child[0]=2;'
    main += '$region["g"]=$child; $data["r"]=$region; try { $data["r"]["g"][0]+=rhs($data); }'
    main += 'finally { $now=$data["r"]; $leaf=$now["g"]; echo count($leaf); } return 0;'
    guard(work, 'php', case, api + main, 'boxed_missing_key', expected_stdout=b'V0' if case == 'removed' else b'0')


with TemporaryDirectory(prefix='deep-boxed-mutation-') as tmp:
    if sys.argv[1] in ('mixed', 'dynamic'):
        failure(Path(tmp), *sys.argv[1:])
    else:
        exercise(Path(tmp), *sys.argv[1:])
