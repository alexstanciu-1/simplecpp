"""Native writes through checked hash/vector paths and repeated lookup after effects."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def mixed_path(work, flavor):
    f = fixture(flavor)
    v = f['v']
    def key(result_type):
        result = '"team"' if result_type == 'string' else '0'
        return f['function']('team', result_type, [], f'echo "T"; return {result};')
    operations = f['function']('indexOf', 'int', [], 'echo "I"; return 0;')
    operations += f['function']('leaf', 'string', [], 'echo "K"; return "goal";')
    operations += f['function']('score', 'int', [], 'echo "V"; return 4;')
    main = declaration(flavor, 'groups', 'hash<vector<hash<int,string>>,string>', '[]')
    main += f'{v}groups["team"] = [[]];'
    path = f'{v}groups[team()][indexOf()][leaf()]'
    main += f'{path} = score(); {path} += score(); ++{path}; {path}--;'
    main += declaration(flavor, 'buckets', 'vector<hash<vector<int>,int>>', '[[]]')
    main += f'{v}buckets[0][7] = [2]; {v}buckets[0][7][0] += 4; echo {v}buckets[0][7][0];'
    main += f'return {v}groups["team"][0]["goal"];'
    trace = b'TIKVTIKVTIKTIK6'
    api = key('string') + operations
    revisions(work, flavor, {'main': main, 'api': api}, [
        (None, None, trace), ('api', key('int') + operations, 'Hash key requires string'),
        ('api', None, 'Unknown function'), ('api', api, trace)])


groups_type = 'hash<hash<int,string>,string>'
prefix = f'$groups {groups_type} = []; $groups["team"] = ["score" => 2];'
helpers = f"""
function replaceTeam({groups_type} &$groups, string &$key, int $mode):int {{
    echo "R";
    $key = "other";
    $groups->remove("team");
    if ($mode == 1) {{ $groups["team"] = ["score" => 40, "sibling" => 11]; }}
    if ($mode == 2) {{ $groups["team"] = []; }}
    return 6;
}}
function score():int {{ echo "V"; return 6; }}
"""


def checked_keys(work, case):
    setup = prefix + '$team = "team";'
    if case == 'reinsert':
        main = setup + """
            $groups[$team]["score"] += replaceTeam($groups,$team,1);
            echo $team,$groups["team"]["sibling"];
            return $groups["team"]["score"];
        """
        revisions(work, 'php', {'main': main, 'api': helpers}, [
            (None, None, b'Rother11'),
            ('main', f'function bad(const {groups_type} &$groups):void {{ $groups["team"]["score"]++; }}' + main,
             'Cannot write through read-only reference'),
            ('main', setup + 'foreach ($groups as $k => &$g) { $groups["team"]["x"]=1; } return 8;',
             'Cannot structurally mutate or borrow')])
        return
    statement, trace = {
        'missing_parent': ('$groups["missing"]["score"] = score();', b'V'),
        'missing_leaf': ('$groups["team"]["missing"] += score();', b''),
        'removed_parent': ('$groups[$team]["score"] += replaceTeam($groups,$team,0);', b'R'),
        'removed_leaf': ('$groups[$team]["score"] += replaceTeam($groups,$team,2);', b'R'),
    }[case]
    guard(work, 'php', case, helpers + setup + statement + 'return 0;', 'hash_missing_key',
          expected_stdout=trace, exception_type='std::out_of_range')


with TemporaryDirectory(prefix='nested-hash-write-') as tmp:
    case = sys.argv[1]
    if case in ('php', 'cpp', 'js'):
        mixed_path(Path(tmp), case)
    else:
        checked_keys(Path(tmp), case)
