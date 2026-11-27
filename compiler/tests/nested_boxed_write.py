"""Two boxed keys are normalized once before the RHS; lookup follows the current root."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def exercise(work, flavor):
    f = fixture(flavor)
    v = f['v']
    group = f['function']('groupKey', 'string', [], 'echo "G"; return "team";')
    bad = f['function']('groupKey', 'bool', [], 'return true;')
    api = f['function']('leafKey', 'string', [], 'echo "K"; return "0";')
    api += f['function']('score', 'int', [], 'echo "V"; return 8;')
    main = ''
    trace = b''
    for family in ('mixed', 'dynamic'):
        root, child, saved, previous, current = [name + family for name in ('data', 'row', 'before', 'previous', 'current')]
        for name in (root, child):
            main += declaration(flavor, name, family, '[]')
        main += f'{v}{child}[0]=2; {v}{root}["team"]={v}{child};'
        main += declaration(flavor, saved, family, v + root)
        main += f'{v}{root}[groupKey()][leafKey()]=score();'
        main += declaration(flavor, previous, family, f'{v}{saved}["team"]')
        main += declaration(flavor, current, family, f'{v}{root}["team"]')
        main += f'echo (int){v}{previous}[0],(int){v}{current}[0];'
        # Replace the intermediate entry during RHS evaluation, then insert the final key.
        change = 'replace' + family
        body = declaration(flavor, 'fresh', family, '[]')
        body += f'{v}leaf="changed"; {v}fresh["sibling"]=4; {v}data["team"]={v}fresh; echo "R"; return 9;'
        api += f['function'](change, 'int', [('&data', family), ('&leaf', 'string')], body).replace('$&', '&$')
        key = 'key' + family
        main += declaration(flavor, key, 'string', '"new"')
        main += f'{v}{root}[groupKey()][{v}{key}]={change}({v}{root},{v}{key});'
        main += f'{v}{current}={v}{root}["team"]; echo (int){v}{current}["sibling"],(int){v}{current}["new"];'
        trace += b'GKV' + (b'28' if family == 'mixed' else b'88') + b'GR49'
    main += 'return 8;'
    revisions(work, flavor, {'main': main, 'api': group + api}, [
        (None, None, trace), ('api', bad + api, 'Boxed container key requires int or string'),
        ('api', None, 'Unknown function'), ('api', group + api, trace)])


def failure(work, family, case):
    api = f'function removeGroup({family} &$data):int {{ echo "V"; $data->remove("team"); return 6; }}'
    source = f'$data {family}=[]; $child {family}=[]; $data["team"]=$child;'
    if case == 'removed':
        source += '$data["team"][0]=removeGroup($data);'
        error, trace = 'boxed_missing_key', b'V'
    else:
        source += '$data["team"]=' + ('null' if case == 'null' else '3') + '; $data["team"][0]=3;'
        error, trace = 'boxed_expected_container', b''
    guard(work, 'php', case, api + source + 'return 0;', error, expected_stdout=trace)


with TemporaryDirectory(prefix='nested-boxed-write-') as tmp:
    if len(sys.argv) == 2:
        exercise(Path(tmp), sys.argv[1])
    else:
        failure(Path(tmp), sys.argv[1], sys.argv[2])
