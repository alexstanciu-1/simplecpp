"""Private collection readers preserve snapshots, callbacks and observable cleanup across edits."""
import argparse
import re
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions

reader = """
function width(vector<int> $items):int {return count($items);}
function first(vector<int> $items):int {return $items[0];}
function lookup(hash<int,int> $items):int {return $items[42];}
function keys(hash<int,int> $items):int {return count($items);}
"""
other = """
struct Mark {public int $x=0;public function __destruct(){echo "D";}}
function marked(vector<Mark> $items):int {return count($items);}
function textKeys(hash<int,string> $items):int {return count($items);}
function invokeWidth(callback<int,vector<int>> $cb,vector<int> &$v):int {return $cb($v);}
function snapshot(vector<int> $view,vector<int> &$writer):int {clean($writer);return count($view);}
"""
main = """
$v vector<int> = [1,2];$h hash<int,int> = [];$h[1]=1;
$h[42]=7;echo first($v),lookup($h),$v[0],$h[42];
try {echo width($v),invokeWidth(width(...),$v);} catch (Exception $e) {echo "E";}
echo count($v),keys($h),snapshot($v,$v),count($v);
$text hash<int,string> = [];$text["a"]=1;echo textKeys($text);
$m Mark;$marks vector<Mark> = [$m];echo "[";echo marked($marks);echo "]";
return 8;
"""
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--quick', action='store_true')
quick = parser.parse_args().quick
with TemporaryDirectory(prefix='scpp-private-count-') as directory:
    work = Path(directory)
    cleanup = b'1D[1D]DD'
    edits = [
        (None, None, b'1717222220' + cleanup),
        ('reader', reader.replace('return count($items);', 'clean($items);return count($items);', 1), b'1717002220' + cleanup),
        ('reader', reader.replace('return count($items);', 'clean($items);throw new Exception("stop");', 1), b'1717E2220' + cleanup),
        ('reader', reader.replace('return $items[0];', '$items[0]=8;return $items[0];')
                         .replace('return $items[42];', '$items[42]=9;return $items[42];'), b'8917222220' + cleanup),
        ('reader', None, None),
        ('reader', reader, b'1717222220' + cleanup),
    ]
    revisions(work, 'php', dict(main=main, reader=reader, other=other), edits[:1] if quick else edits)
    units = '\n'.join(p.read_text() for p in (work/'php-out/units').glob('*.cpp'))
    for symbol, operation in (('width', '.size()'), ('keys', '.size()'), ('first', '.at('), ('lookup', '.at(')):
        assert symbol + '_collection_reader(var_' in units
        body = re.search(symbol + r'_collection_reader\(const [^;{]+\)\s*\{([^}]+)', units)
        assert body and operation in body[1], (symbol, body)
    assert 'marked_collection_reader' not in units and 'textKeys_collection_reader' not in units
    assert '&width_' in units and '&width_collection_reader' not in units

if not quick:
    with TemporaryDirectory(prefix='scpp-index-failure-') as directory:
        for function, collection, error in [('first', 'vector<int>', 'sequence_index_out_of_range'),
                                            ('lookup', 'hash<int,int>', 'hash_missing_key')]:
            guard(Path(directory), 'php', function, reader + f'$v {collection} = [];return {function}($v);',
                  error, exception_type='std::out_of_range')
    from classes_native import fixture
    for flavor in ('cpp', 'js'):
        forms = fixture(flavor)
        read = forms['function']('width', 'int', [('items', 'vector<int>')], 'return count(items);')
        read += forms['function']('first', 'int', [('items', 'vector<int>')], 'return items[0];')
        local = 'vector<int> v=[3,5];' if flavor == 'cpp' else 'let v:vector<int> = [3,5];'
        app = local + 'echo width(v),first(v),v[0];return 8;'
        with TemporaryDirectory(prefix='scpp-private-count-') as directory:
            revisions(Path(directory), flavor, dict(main=app, reader=read), [
                (None, None, b'233'),
                ('reader', read.replace('return items[0]', 'items[0]=9;return items[0]'), b'293'),
                ('reader', None, None),
                ('reader', read, b'233'),
            ])
