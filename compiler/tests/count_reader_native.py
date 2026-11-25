"""Private count entries preserve snapshots, callbacks and observable cleanup across edits."""
import argparse
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions

reader = """
function width(vector<int> $items):int {return count($items);}
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
        (None, None, b'222120' + cleanup),
        ('reader', reader.replace('return count($items);', 'clean($items);return count($items);', 1), b'002120' + cleanup),
        ('reader', reader.replace('return count($items);', 'clean($items);throw new Exception("stop");', 1), b'E2120' + cleanup),
        ('reader', None, None),
        ('reader', reader, b'222120' + cleanup),
    ]
    revisions(work, 'php', dict(main=main, reader=reader, other=other), edits[:1] if quick else edits)
    units = '\n'.join(p.read_text() for p in (work/'php-out/units').glob('*.cpp'))
    for symbol in ('width', 'keys'):
        assert symbol + '_count_reader(var_' in units
        definition = next(line for line in units.splitlines() if symbol + '_count_reader(const ' in line and '{' in line)
        assert '.size()' in definition
    assert 'marked_count_reader' not in units and 'textKeys_count_reader' not in units
    assert '&width_' in units and '&width_count_reader' not in units

if not quick:
    from classes_native import fixture
    for flavor in ('cpp', 'js'):
        forms = fixture(flavor)
        read = forms['function']('width', 'int', [('items', 'vector<int>')], 'return count(items);')
        local = 'vector<int> v=[3,5];' if flavor == 'cpp' else 'let v:vector<int> = [3,5];'
        app = local + 'echo width(v),count(v);return 8;'
        with TemporaryDirectory(prefix='scpp-private-count-') as directory:
            revisions(Path(directory), flavor, dict(main=app, reader=read), [
                (None, None, b'22'),
                ('reader', read.replace('return count', 'items = [];return count'), b'02'),
                ('reader', None, None),
                ('reader', read, b'22'),
            ])
