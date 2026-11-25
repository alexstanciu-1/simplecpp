"""Read-only collection method borrows and cross-file signature recovery."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    helper = 'struct Probe {public static function size(const vector<int> &$v):int {return count($v);}}'
    reader = """
        class Reader {
            public function inspect(const vector<int> &$view,vector<int> &$writer):int {
                $alias =& $view;clean($writer);return Probe::size($alias);
            }
            public static function at(const hash<int,string> &$view):int {return $view["k"];}
        }
        struct Counter {
            public function inspect(const hash<int,string> &$view,hash<int,string> &$writer):int {
                $writer["k"]=8;return Reader::at($view);
            }
        }
    """
    main = """
        function fresh():vector<int> {$v vector<int> = [];return $v;}
        $r=new Reader();$c Counter;$v vector<int> = [1,2];$map hash<int,string> = ["k"=>3];
        echo Probe::size($v),$r->inspect($v,$v),count($v),$c->inspect($map,$map),Reader::at($map);
        return 8;
    """
    trace = b'20088'
    edits = [(None, None, trace),
             ('helper', helper.replace('const ', ''), 'Cannot write through read-only reference'),
             ('helper', helper, trace),
             ('reader', reader.replace('clean($writer)', 'clean($alias)'), 'Cannot write through read-only reference'),
             ('reader', reader.replace('$writer["k"]=8', '$view["k"]=8'), 'Cannot write through read-only reference'),
             ('reader', reader, trace),
             ('main', main.replace('Probe::size($v)', 'Probe::size($map)'), 'exact storage type'),
             ('main', main.replace('Probe::size($v)', 'Probe::size(fresh())'), 'stable variable storage'),
             ('main', main, trace), ('helper', None, None), ('helper', helper, trace)]
    revisions(work, 'php', dict(main=main, helper=helper, reader=reader), edits)


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-readonly-methods-') as directory:
        prove(Path(directory))
