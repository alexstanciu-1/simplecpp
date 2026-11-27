"""Checked field paths retain the selected owner and observe its current collection."""
from pathlib import Path
import sys
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions

kind = 'hash<vector<int>,string>'


def exercise(work, flavor):
    f = fixture(flavor)
    v, access = f['v'], f['access']
    model = f['record']('Board', f['field']('groups', kind))
    path = f'{v}board{access}groups'
    main = f['local']('board', 'Board') + f'{path}["A"] = [2];'
    main += f['infer']('copy', v + 'board')
    main += f'{path}["A"][0] = 6; {path}["A"][0]++; --{path}["A"][0];'
    main += f'{path}["A"][] = 3; {path}["A"][0] += 2;'
    main += f'echo {v}copy{access}groups["A"][0],count({v}copy{access}groups["A"]),{path}["A"][1];'
    main += f'return {path}["A"][0];'
    revisions(work, flavor, {'main': main, 'model': model}, [
        (None, None, b'213'), ('model', model.replace(kind, 'hash<vector<int>,int>'), 'Hash key requires'),
        ('model', f['record']('Board', ''), 'Unknown field'), ('model', model, b'213')])


model = f'class Board {{ public {kind} $groups; }}'
api = """
function selected(Board $board):Board { echo "R"; return $board; }
function keyOnce():string { echo "K"; return "A"; }
function rebind(Board &$board):int {
    echo "B"; $board = new Board(); $board->groups["A"] = [90]; return 6;
}
function replace(Board $board, int $keep):int {
    echo "F"; $board->groups = [];
    if ($keep) { $board->groups["A"] = [40,41]; }
    return 3;
}
"""
setup = '$board Board = new Board(); $board->groups["A"] = [2];'


def ownership(work, case):
    if case == 'last_owner':
        tracked = model.replace(' }', ' public function __destruct(){ echo "D"; } }')
        main = setup + 'selected($board)->groups[keyOnce()][0] = rebind($board); echo "E",$board->groups["A"][0]; return 8;'
        after_rebind = api.replace('return 6;', 'echo "X"; return 6;')
        revisions(work, 'php', {'main': main, 'model': tracked, 'api': after_rebind}, [(None, None, b'RKBXDE90D')])
        return
    if case == 'missing':
        source = model + api + setup + 'selected($board)->groups[keyOnce()][0] += replace($board,0); return 0;'
        guard(work, 'php', 'missing', source, 'hash_missing_key', expected_stdout=b'RKF', exception_type='std::out_of_range')
        return
    main = setup + """
        $saved = $board;
        selected($board)->groups[keyOnce()][0] += rebind($board);
        echo $saved->groups["A"][0],"/",$board->groups["A"][0],"/";
        selected($board)->groups[keyOnce()][] = replace($board,1);
        echo $board->groups["A"][0],"/",$board->groups["A"][2],"/";
        selected($board)->groups[keyOnce()][0] += replace($board,1);
        echo $board->groups["A"][0];
        $board->tick(); echo "/",$board->groups["A"][0];
        return 8;
    """
    method = 'public function tick():void { ++$this->groups["A"][0]; }'
    model_with_method = model[:-1] + method + '}'
    revisions(work, 'php', {'main': main, 'api': api, 'model': model_with_method}, [
        (None, None, b'RKB8/90/RKF40/3/RKF43/44')])


def readonly(work):
    immutable = f'class Board {{ public readonly {kind} $groups; public function __construct({kind} $g) {{ $this->groups = $g; }} }}'
    edits = [('main', 'function bad(Board $b):void { $b->groups["A"]' + op + '; } return 8;', 'Cannot modify readonly property groups')
             for op in ('[0] = 1', '[0] += 1', '[0]++', '[] = 1')]
    revisions(work, 'php', {'main': 'return 8;', 'model': immutable}, edits)


with TemporaryDirectory(prefix='nested-field-write-') as tmp:
    case = sys.argv[1]
    if case in ('cpp', 'js', 'php'):
        exercise(Path(tmp), case)
    elif case == 'readonly':
        readonly(Path(tmp))
    else:
        ownership(Path(tmp), case)
