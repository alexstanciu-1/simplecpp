"""Named data construction through generated and authored constructors."""
import argparse
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions

records = """
data struct Packet {public vector<int> $data; public int $version=1;}
data class Box {public Packet $contents;}
data class User {
    public int $number;
    public function __construct(int $a,int $b=3) {
        echo "C";
        if ($a===0) {throw new Exception("invalid");}
        $this->number=$a*10+$b;
    }
}
data struct Pair {public int $a;private int $hidden=7;public readonly int $b;}
data struct Marker {public int $value; public function __construct(int $value) {echo "M";$this->value=$value;}}
data class Envelope {public Marker $marker;}
data struct Extended extends Pair {public int $extra=6;}
"""
caller = """
function bump(int &$n):int {$n++;return $n;}
function packet(vector<int> $items):Packet {return ["data"=>$items];}
$v vector<int> = [8];$p=packet($v);$box Box = {contents:{data:$v}};
$v[0]=2;$x=$p->data;$y=$box->contents->data;echo $x[0],$p->version,$y[0];
$n=0;$u User = {b:bump($n),a:bump($n)};echo $u->number;
try {$u={a:0};} catch(Exception $e) {echo $u->number;}
$n=0;$pair Extended = {b:bump($n),a:bump($n)};echo $pair->a,$pair->b,$pair->extra;
$envelope Envelope = {marker:{value:7}};echo $envelope->marker->value;
return 8;
"""


def php(work, quick):
    expected = b'818C21C21216M7'
    edits = [(None, None, expected),
             ('records', records.replace('$version=1', '$version=4'), expected.replace(b'818', b'848')),
             ('records', records.replace('data struct Packet', 'struct Packet'), 'keyed destination'),
             ('records', records, expected)]
    if not quick:
        for changed, error in [
            (caller.replace('a:bump($n)};echo $pair', 'b:bump($n)};echo $pair'), 'supplied twice'),
            (caller.replace('b:bump($n),a:bump($n)};echo $pair', 'a:bump($n)};echo $pair'), 'Missing required argument: b'),
            (caller.replace('$n=0;$pair', '$n=0;$bad Pair = {hidden:1,b:2};$pair'), 'Unknown named argument: hidden'),
            (caller.replace('["data"=>$items]', '[$items=>$items]'), 'literal string keys'),
            (caller.replace('["data"=>$items]', '[8]'), 'named inputs'),
            (caller.replace('["data"=>$items]', '["data"=>3]'), 'Unsupported explicit conversion'),
            (caller.replace('$n=0;$pair', '$n=0;$bad Pair(1,2);$pair'), 'named data inputs'),
        ]:
            edits.append(('main', changed, error))
        edits.extend([('main', caller, expected),
                      ('records', records.replace('int $a,int $b', 'int $first,int $b').replace('if ($a', 'if ($first').replace('=$a*', '=$first*'), 'Unknown named argument: a'),
                      ('records', None, None), ('records', records, expected)])
    revisions(work, 'php', dict(main=caller, records=records), edits)


def other(work, flavor):
    record, caller = {
        'cpp': ('data struct Packet { int x=3; }; data class Box { public: int x=3; };',
                'Packet p=["x"=>7]; Box b=["x"=>8]; echo p->x,b->x; return 8;'),
        'js': ('data struct Packet { x:int=3; } data class Box { x:int=3; }',
               'let p:Packet={x:7}; let b:Box={x:8}; echo p.x,b.x; return 8;'),
        'py': ('data class Box:\n    x: int = 3\n',
               'b: Box = {"x": 8}\nreturn b.x\n'),
    }[flavor]
    expected = b'' if flavor == 'py' else b'78'
    edits = [(None, None, expected), ('records', record.replace('data class Box', 'class Box'), 'keyed destination'),
             ('records', record, expected)]
    revisions(work, flavor, dict(main=caller, records=record), edits)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--flavor', choices=('php', 'cpp', 'js', 'py'), default='php')
    parser.add_argument('--quick', action='store_true')
    args = parser.parse_args()
    with TemporaryDirectory(prefix='scpp-data-records-') as tmp:
        if args.flavor == 'php':
            php(Path(tmp), args.quick)
        else:
            other(Path(tmp), args.flavor)
