"""Saved path keys resolve against the post-RHS tree at every depth."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def exercise(work, flavor, append=False):
    f = fixture(flavor)
    v, access = f['v'], f['access']
    api = f['function']('key', 'string', [('k', 'string')], f'echo {v}k; return {v}k;')
    main, model, trace = '', '', b''
    for family in ('mixed', 'dynamic'):
        holder = 'Holder' + family
        model += f['klass'](holder, f['field']('data', family))
        def build(name, value):
            return declaration(flavor, name + family, family, value)

        root, region, old, fresh, snap, seen = [v + name + family for name in ('root', 'region', 'old', 'fresh', 'snap', 'seen')]
        h = v + 'h' + family
        for name in ('root', 'region', 'old', 'fresh'):
            main += build(name, '[]')
        main += f'{old}[2]=2; {fresh}[8]=4; {region}["g"]={old}; {region}["n"]=3; {root}["r"]={region}; {root}["z"]=5;'
        main += build('snap', root)
        # Four keys force publication through three intermediate containers.
        main += build('wrapper', '[]')
        wrapper = v + 'wrapper' + family
        local_suffix = '[]' if append else '[key("2")]'
        main += f'{wrapper}["w"]={root}; {wrapper}[key("w")][key("r")][key("g")]{local_suffix}=6;'
        main += build('seen', f'{wrapper}["w"]')
        main += f'{seen}={seen}["r"]; {seen}={seen}["g"]; echo (int){seen}[{3 if append else 2}],(int){old}[2];'
        main += f['infer']('h' + family, 'new ' + holder + '()') + f'{h}{access}data={root};'
        change = 'replace' + family
        field = v + 'owner' + access + 'data'
        body = f'{field}["r"]["g"]={v}child; echo "V"; return 8;'
        api += f['function'](change, 'int', [('owner', holder), ('child', family)], body)
        field_suffix = '[]' if append else '[key("9")]'
        main += f'{h}{access}data[key("r")][key("g")]{field_suffix}={change}({h},{fresh});'
        main += f'{seen}={h}{access}data["r"]; echo (int){h}{access}data["z"],(int){seen}["n"];'
        main += f'{seen}={seen}["g"]; echo (int){seen}[8],(int){seen}[9],count({fresh}),count({old});'
        main += f'{seen}={snap}["r"]; {seen}={seen}["g"]; echo count({seen});'
        if append:
            trace += b'wrg62rgV5348' + (b'111' if family == 'mixed' else b'222')
        else:
            trace += b'wrg26' + (b'2' if family == 'mixed' else b'6') + b'rg9V5348' + (b'111' if family == 'mixed' else b'212')
    revisions(work, flavor, {'main': main + 'return 8;', 'api': api, 'model': model}, [
        (None, None, trace), ('model', model.replace('data', 'renamed'), 'Unknown field'), ('model', model, trace)])


def failure(work, family, case, append=False):
    api = (f'function rhs({family} &$root):int {{ $group=$root["r"]; '
           + ('$group->remove("g");' if case == 'missing' else '$group["g"]=3;')
           + '$root["r"]=$group; echo "V"; return 8; }')
    main = f'$root {family}=[]; $region {family}=[]; $old {family}=[]; $old["s"]=7; $region["g"]=$old; $root["r"]=$region;'
    suffix = '[]' if append else '["x"]'
    main += 'try { $root["r"]["g"]' + suffix + '=rhs($root); } finally { $now=$root["r"]; echo count($old),count($now); }'
    guard(work, 'php', case, api + main + 'return 0;', 'boxed_missing_key' if case == 'missing' else 'boxed_expected_container',
          expected_stdout=b'V10' if case == 'missing' else b'V11')


def field_type_edit(work):
    main = ('$h=build(); $h->data["r"]["g"][0]=8; $h->data["r"]["g"][]=9; $h->data["r"]["g"][0]+=2; --$h->data["r"]["g"][1]; $region=$h->data["r"]; '
            '$leaf=$region["g"]; echo (int)$leaf[0],(int)$leaf[1]; return 8;')
    boxed = ('class Holder { public mixed $data; } function build():Holder { $h=new Holder(); '
             '$root mixed=[]; $region mixed=[]; $leaf mixed=[]; $leaf[0]=2; $region["g"]=$leaf; '
             '$root["r"]=$region; $h->data=$root; return $h; }')
    typed = ('class Holder { public hash<hash<vector<int>,string>,string> $data; } '
             'function build():Holder { $h=new Holder(); $region hash<vector<int>,string> = []; '
             '$region["g"]=[2]; $h->data["r"]=$region; return $h; }')
    revisions(work, 'php', {'main': main, 'model': boxed}, [
        (None, None, b'108'), ('model', typed, b'108'), ('model', boxed, b'108')])


with TemporaryDirectory(prefix='deep-boxed-store-') as tmp:
    if sys.argv[1] == 'append':
        if len(sys.argv) == 3:
            exercise(Path(tmp), sys.argv[2], append=True)
        else:
            failure(Path(tmp), *sys.argv[2:], append=True)
    elif sys.argv[1] == 'field_type_edit':
        field_type_edit(Path(tmp))
    elif len(sys.argv) == 2:
        exercise(Path(tmp), sys.argv[1])
    else:
        failure(Path(tmp), *sys.argv[1:])
