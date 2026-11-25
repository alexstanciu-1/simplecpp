"""Receiver retention, pack ownership and exact variadic slots across method dispatch."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


api = 'interface Scorer {public function score(int $base,int ...$xs):int;}'
base = '''
class Scores implements Scorer {
    public int $offset=10;
    public override function score(int $base,int ...$xs):int {
        foreach ($xs as $x) {$base=$base+$x;}
        if (count($xs)>0) {$xs[0]=99;}
        $xs[]=100;return $this->offset+$base;
    }
}
'''
child = '''
class Bonus extends Scores {
    public override function score(int $base,int ...$xs):int {return 20+parent::score($base,...$xs);}
}
'''
entry = '''
struct Counter {
    public int $n=0;
    public function add(int ...$xs):int {
        foreach ($xs as $x) {$this->n=$this->n+$x;}
        return $this->n;
    }
}
class Holder {public Scores $current;public function __construct() {$this->current=new Bonus();}}
function pick(Holder $h):Scores {echo "R";return $h->current;}
function prefix():int {echo "P";return 1;}
function replace(Holder $h):vector<int> {
    echo "V";$h->current=new Scores();$h->current->offset=80;
    $xs vector<int> = [2,3];return $xs;
}
function viaBase(Scores $s,vector<int> $xs):int {return $s->score(1,...$xs);}
function viaInterface(Scorer $s,vector<int> $xs):int {return $s->score(2,...$xs);}
$c Counter;$xs vector<int> = [2,3];echo $c->add(),":",$c->add(4,5),":",$c->add(...$xs),";";
$plain=new Scores();echo $plain->score(0,...$xs),":",$xs[0],count($xs),";";
$b=new Bonus();echo viaBase($b,$xs),":",viaInterface($b,$xs),":",$xs[0],count($xs),";";
$h=new Holder();
echo pick($h)->score(prefix(),...replace($h)),":",$h->current->score(1);return 8;
'''
trace = b'0:9:14;15:22;36:37:22;RPV36:81'
mismatch = 'Override signature must match exactly'

with TemporaryDirectory(prefix='scpp-variadic-instance-') as directory:
    revisions(Path(directory), 'php', dict(main=entry, api=api, base=base, child=child), [
        (None, None, trace),
        ('child', child.replace('int ...$xs', 'vector<int> $xs'), mismatch),
        ('child', child, trace),
        ('api', api.replace('int ...$xs', 'vector<int> $xs'), mismatch),
        ('api', api, trace),
        ('base', base.replace('int ...$xs', 'vector<int> $xs'), mismatch),
        ('base', base, trace),
    ])
