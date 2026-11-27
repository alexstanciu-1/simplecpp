"""Append follows saved keys into current typed storage after RHS conversion."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def mixed_path(work, flavor):
    f = fixture(flavor)
    v = f['v']
    score = f['function']('score', 'int', [], 'echo "V"; return 8;')
    bad = f['function']('score', 'string', [], 'return "bad";')
    keys = f['function']('team', 'string', [], 'echo "T"; return "A";')
    keys += f['function']('indexOf', 'int', [], 'echo "I"; return 0;')
    main = declaration(flavor, 'teams', 'hash<vector<vector<int>>,string>', '[]')
    main += f'{v}teams["A"] = [[]]; {v}teams[team()][indexOf()][] = score();'
    main += f'return {v}teams["A"][0][0];'
    revisions(work, flavor, {'main': main, 'api': keys + score}, [
        (None, None, b'TIV'), ('api', keys + bad, 'conversion from string to int'),
        ('api', None, 'Unknown function'), ('api', keys + score, b'TIV')])


groups = 'hash<hash<int,int>,string>'
setup = f'$groups {groups} = []; $groups["A"] = [3 => 11]; $team = "A";'
helper = f"""
function replace({groups} &$groups, string &$team, int $restore):int {{
    echo "R";
    $groups->remove("A");
    $team = "B";
    if ($restore) {{ $groups["A"] = [9 => 12]; }}
    return 8;
}}
"""


def replace_or_fail(work, case):
    if case == 'reinsert':
        main = setup + """
            $groups[$team][] = replace($groups,$team,1);
            echo $team,$groups["A"][9],count($groups["A"]);
            return $groups["A"][10];
        """
        revisions(work, 'php', {'main': main, 'api': helper}, [
            (None, None, b'RB122'),
            ('main', f'function bad(const {groups} &$groups):void {{ $groups["A"][]=1; }}' + main,
             'Cannot write through read-only reference'),
            ('main', '$rows vector<hash<int,string>> = [[]]; $rows[0][] = 8; return 8;',
             'Append requires an appendable collection'),
            ('main', setup + 'foreach ($groups as $k => &$v) { $groups["A"][]=1; } return 8;',
             'Cannot structurally mutate or borrow')])
    elif case == 'await':
        async_api = 'async function delayed(int $n):int { await async_sleep_ms(1); echo "D"; return $n; }'
        main = setup + """
            $groups[$team][] = (await delayed(replace($groups,$team,1))) + 1;
            echo $groups["A"][10];
            return 8;
        """
        revisions(work, 'php', {'main': main, 'api': helper + async_api}, [(None, None, b'RD9')])
    elif case == 'removed':
        guard(work, 'php', case, helper + setup + '$groups[$team][] = replace($groups,$team,0); return 0;',
              'hash_missing_key', expected_stdout=b'R', exception_type='std::out_of_range')
    else:
        guard(work, 'php', case, setup + '$wrong mixed = "bad"; $groups["missing"][] = $wrong; return 0;',
              'boxed_scalar_kind')


with TemporaryDirectory(prefix='nested-append-') as tmp:
    case = sys.argv[1]
    if case in ('php', 'cpp', 'js'):
        mixed_path(Path(tmp), case)
    else:
        replace_or_fail(Path(tmp), case)
