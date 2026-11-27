"""Nested append selects the current boxed child and uses its own next key."""
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
    main, api, trace = '', '', b''
    for family in ('mixed', 'dynamic'):
        root, old, fresh, saved, seen = [name + family for name in ('data', 'old', 'fresh', 'saved', 'seen')]
        for name in (root, old, fresh):
            main += declaration(flavor, name, family, '[]')
        main += f'{v}{old}[2]=2; {v}{fresh}[8]=8; {v}{root}["team"]={v}{old};'
        main += declaration(flavor, saved, family, v + root)
        replace = 'replace' + family
        body = f'{v}data["team"]={v}child; echo "R"; return 7;'
        api += f['function'](replace, 'int', [('&data', family), ('child', family)], body).replace('$&', '&$')
        main += f'{v}{root}[groupKey()][]={replace}({v}{root},{v}{fresh});'
        main += declaration(flavor, seen, family, f'{v}{root}["team"]')
        main += f'echo (int){v}{seen}[9], count({v}{seen}), count({v}{old}), count({v}{fresh});'
        main += f'{v}{seen}={v}{saved}["team"]; echo count({v}{seen});'
        trace += b'GR721' + (b'11' if family == 'mixed' else b'22')
    revisions(work, flavor, {'main': main + 'return 8;', 'api': group + api}, [
        (None, None, trace), ('api', bad + api, 'Boxed container key requires int or string'),
        ('api', None, 'Unknown function'), ('api', group + api, trace)])


def failure(work, family, case):
    change = {'removed': '$data->remove("team");', 'null': '$data["team"]=null;',
              'scalar': '$data["team"]=3;'}[case]
    api = f'function rhs({family} &$data):int {{ {change} echo "R"; return 7; }}'
    main = f'$data {family}=[]; $child {family}=[]; $data["team"]=$child;'
    main += 'try { $data["team"][]=rhs($data); } finally { echo count($data), count($child); }'
    removed = case == 'removed'
    guard(work, 'php', case, api + main + 'return 0;',
          'boxed_missing_key' if removed else 'boxed_expected_container',
          expected_stdout=b'R00' if removed else b'R10')


with TemporaryDirectory(prefix='nested-boxed-append-') as tmp:
    if len(sys.argv) == 2:
        exercise(Path(tmp), sys.argv[1])
    else:
        failure(Path(tmp), *sys.argv[1:])
