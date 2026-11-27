"""Two-key compounds read before RHS effects and write to the current path."""
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
    main, trace = '', b''
    for family in ('mixed', 'dynamic'):
        root, row, before, current = [v + name + family for name in ('data', 'row', 'before', 'current')]
        def local(name, value):
            return declaration(flavor, name[len(v):], family, value)
        change = 'replace' + family
        body = declaration(flavor, 'fresh', family, '[]')
        body += f'{v}fresh[0]=40; {v}fresh[1]=7; {v}data["team"]={v}fresh; echo "R"; return 6;'
        api += f['function'](change, 'int', [('&data', family)], body).replace('$&', '&$')
        main += local(root, '[]') + local(row, '[]')
        main += f'{row}[0]=2; {root}["team"]={row};' + local(before, root)
        target = root + '[groupKey()][leafKey()]'
        main += f'{target}+={change}({root});' + local(current, root + '["team"]')
        main += f'echo (int){current}[0],(int){current}[1],(int){row}[0];'
        for update in ('++' + target, target + '++', '--' + target, target + '--'):
            main += f'{update}; {current}={root}["team"]; echo (int){current}[0];'
        main += f'{current}={before}["team"]; echo (int){current}[0];'
        trace += b'GKR872GK9GK10GK9GK8' + (b'2' if family == 'mixed' else b'8')
    revisions(work, flavor, {'main': main + 'return 8;', 'api': group + api}, [
        (None, None, trace), ('api', bad + api, 'Boxed container key requires int or string'),
        ('api', group + api, trace)])


def failure(work, family, case):
    setup = f'$data {family}=[]; $child {family}=[];'
    api = f'function rhs({family} &$data):int {{ echo "R"; $fresh {family}=[]; $data["team"]=$fresh; return 6; }}'
    if case != 'missing_parent':
        if case == 'removed':
            setup += '$child[0]=2;'
        setup += '$data["team"]=$child;'
    operation = '$data["team"][0]+=rhs($data);'
    if case == 'removed':
        operation = 'try {' + operation + f'}} finally {{ $seen {family}=$data["team"]; echo count($seen); }}'
    guard(work, 'php', case, api + setup + operation + 'return 0;', 'boxed_missing_key',
          expected_stdout=b'R0' if case == 'removed' else b'')


with TemporaryDirectory(prefix='boxed-mutation-') as directory:
    if len(sys.argv) == 2:
        exercise(Path(directory), sys.argv[1])
    else:
        failure(Path(directory), *sys.argv[1:])
