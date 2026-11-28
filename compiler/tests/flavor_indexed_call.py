"""Call PHP-provided owned callable collections from each consuming frontend."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


PROVIDER = """
function first(int $n):int {echo "A";return $n+1;}
function second(int $n):int {echo "B";return $n+2;}
function makeOps():vector<function<int(int)>> {return [first(...)];}
function makeTable():hash<function<int(int)>,string> {return ["hit"=>first(...)];}
function key():int {echo "K";return 0;}
function swap(vector<function<int(int)>> &$ops):int {echo "R";$ops[0]=second(...);return 7;}
function argument():int {echo "X";return 7;}
function makeEmpty():vector<function<int(int)>> {$empty function<int(int)>;return [$empty];}
"""


def declaration(flavor, name, type_name, value):
    if flavor == 'py':
        return f'{name}: {type_name} = {value}'
    prefix = 'let' if flavor == 'js' else 'auto'
    return f'{prefix} {name} = {value}'


with TemporaryDirectory(prefix='flavor-indexed-call-') as directory:
    work = Path(directory)
    flavor, case = sys.argv[1:]
    support = {'provider.php': PROVIDER}
    end = '\n' if flavor == 'py' else ';\n'
    vector = 'vector<function<int(int)>>'
    table = 'hash<function<int(int)>,string>'
    if case == 'selection':
        main = end.join([
            declaration(flavor, 'ops', vector, 'makeOps()'), 'ops[key()](swap(ops))',
            declaration(flavor, 'n', 'int', 'ops[key()](argument())'),
            declaration(flavor, 'table', table, 'makeTable()'), 'return table["hit"](n)-2',
        ]) + end
        revisions(work, flavor, {'main': main}, [
            ('main', main.replace('table["hit"](n)', 'table["hit"]()'), 'Argument count mismatch'),
            ('main', main, b'KRAKXBA')], support_sources=support)
    else:
        missing = case == 'missing'
        main = (declaration(flavor, 'ops', table, 'makeTable()') + end + 'return ops["absent"](argument())'
                if missing else declaration(flavor, 'ops', vector, 'makeEmpty()') + end + 'return ops[key()](argument())')
        guard(work, flavor, case, main + end, 'hash_missing_key' if missing else None,
              exception_type='std::out_of_range' if missing else 'std::bad_function_call',
              exception_header='stdexcept' if missing else 'functional', expected_stdout=b'' if missing else b'KX',
              support_sources=support)
