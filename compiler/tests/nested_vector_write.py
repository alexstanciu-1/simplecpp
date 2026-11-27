"""Checked paths of nested dimensions on writable local vectors, including reentrant replacement."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import guard, revisions
from populated_vectors_native import declaration


def check(work, case):
    if case in ('php', 'cpp', 'js'):
        f = fixture(case)
        v = f['v']
        api = f['function']('element', 'int', [], 'echo "V"; return 8;')
        bad_api = f['function']('element', 'string', [], 'return "bad";')
        index = f['function']('indexOf', 'int', [('key', 'int')], f'echo {v}key; return {v}key;')
        api += index
        bad_api += index
        main = declaration(case, 'rows', 'vector<vector<int>>', '[[1], [2]]')
        main += declaration(case, 'deep', 'vector<vector<vector<vector<int>>>>', '[[], [[[1], [3]]]]')
        deep_slot = f'{v}deep[1][0][1][0]'
        path = f'{v}deep[indexOf(1)][indexOf(0)][indexOf(1)][indexOf(0)]'
        main += f'{path} = element(); {path} += element(); ++{deep_slot}; echo {deep_slot}; {deep_slot}--; echo {deep_slot};'
        main += f['infer']('original', v+'rows')
        slot = f'{v}rows[1][0]'
        main += f'{v}rows[indexOf(1)][indexOf(0)] += element(); echo {slot};'
        main += f'{slot}++; echo {slot}; --{slot}; echo {slot}; ++{slot}; echo {slot}; {slot}--; echo {slot};'
        main += f'{v}rows[indexOf(1)][indexOf(0)] = element(); echo {v}original[1][0]; return {v}rows[1][0];'
        revisions(work, case, {'main': main, 'api': api}, [
            (None, None, b'1010V1010V171610V101110111010V2'), ('api', None, 'Unknown function'),
            ('api', bad_api, None), ('api', api, b'1010V1010V171610V101110111010V2')])
        return

    helpers = """
        function replaceRows(vector<vector<int>> &$rows, int $key): int {
            echo "R";
            $rows = [[41,42],[51,52]];
            return $key;
        }
        function clearRows(vector<vector<int>> &$rows): int {
            clean($rows);
            echo "C";
            return 8;
        }
        function replaceMiddle(vector<vector<vector<int>>> &$cube, int $drop): int {
            echo "M";
            if ($drop) { $cube[0] = []; } else { $cube[0] = [[41,42]]; }
            return 6;
        }
        function value(): int { echo "V"; return 8; }
    """
    rows = '$rows vector<vector<int>> = [[1]];'
    if case == 'replacement':
        main = rows + """
            $rows[0][replaceRows($rows,1)] = 7;
            echo $rows[0][0],$rows[0][1];
            $rows[1][0] = replaceRows($rows,8);
            echo $rows[1][1];
            $rows[1][0] += replaceRows($rows,6);
            echo $rows[1][0],$rows[1][1];
            $rows[0][replaceRows($rows,0)] += 3;
            echo $rows[0][0];
            $cube vector<vector<vector<int>>> = [[[1,2]]];
            $cube[0][0][1] += replaceMiddle($cube,0);
            echo $cube[0][0][0],$cube[0][0][1];
            return 8;
        """
        revisions(work, 'php', {'main': main, 'api': helpers}, [
            (None, None, b'R417R52R1452R44M418'),
            ('main', 'function bad(const vector<vector<int>> &$rows):void { $rows[0][0]=8; }' + main,
             'Cannot write through read-only reference'),
            *[('main', 'function bad(const vector<vector<int>> &$rows):void {' + op + ';}' + main,
               'Cannot write through read-only reference') for op in ('$rows[0][0] += 1', '++$rows[0][0]')],
            ('main', '''function bad(const vector<vector<vector<int>>> &$cube):void { $cube[0][0][0]++; } return 8;''',
             'Cannot write through read-only reference')])
        return
    failure, trace = {
        'middle_removed': ('$cube vector<vector<vector<int>>> = [[[1]]]; $cube[0][0][0] += replaceMiddle($cube,1);', b'M'),
        'compound_outer': ('$rows[1][0] += value();', b''),
        'compound_inner': ('$rows[0][-1] += value();', b''),
        'compound_cleared': ('$rows[0][0] += clearRows($rows);', b'C'),
        'outer': ('$rows[1][0] = value();', b'V'),
        'inner': ('$rows[0][-1] = value();', b'V'),
        'cleared': ('$rows[0][0] = clearRows($rows);', b'C'),
    }[case]
    guard(work, 'php', case, helpers + rows + failure + 'return 0;', 'sequence_index_out_of_range',
          expected_stdout=trace, exception_type='std::out_of_range')


with TemporaryDirectory(prefix='nested-vector-write-') as tmp:
    check(Path(tmp), sys.argv[1])
