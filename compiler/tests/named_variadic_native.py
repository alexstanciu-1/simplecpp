"""Named fixed parameters preserve written order and leave an owned variadic tail empty."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


provider = '''
function score(int $base,int $bonus=4,int ...$items):int {
    return $base*10+$bonus+count($items);
}
'''
records = '''
class BaseScore {
    public int $n=0;
    public function __construct(int $base,int $bonus=4,int ...$xs) {
        $this->n=$base*10+$bonus+count($xs);
    }
    public static function apply(int $base,int $bonus=4,int ...$xs):int {return score($base,$bonus,...$xs);}
    public function run(int $base,int $bonus=4,int ...$xs):int {return score($base,$bonus,...$xs);}
}
struct Pair {
    public int $n=0;
    public function __construct(int $base,int $bonus=4,int ...$xs) {$this->n=$base*10+$bonus+count($xs);}
}
class ChildScore extends BaseScore {
    public function __construct() {parent::__construct(bonus:mark(2),base:mark(1));}
}
class DefaultScore extends BaseScore {
    public function __construct() {parent::__construct(base:3);}
}
'''
entry = '''
function mark(int $x):int {echo $x;return $x;}
echo score(base:1),":",score(bonus:mark(2),base:mark(1)),":",score(1,bonus:3),";";
$c=new ChildScore();$d=new DefaultScore();echo "=", $c->n,":",$d->n,";";
$b=new BaseScore(bonus:mark(2),base:mark(1));echo "=", $b->n,":",$b->run(base:3),":",BaseScore::apply(base:2),";";
$f=fn(int $base,int $bonus=4,int ...$xs):int=>$base*10+$bonus+count($xs);
echo $f(bonus:mark(3),base:mark(2)),";";
$p Pair(bonus:mark(5),base:mark(3));echo $p->n;return 8;
'''
trace = b'14:2112:13;21=12:34;21=12:34:24;3223;5335'
with TemporaryDirectory(prefix='scpp-named-variadic-') as directory:
    revisions(Path(directory), 'php', dict(main=entry, provider=provider, records=records), [
        (None, None, trace),
        ('provider', provider.replace('$bonus', '$extra'), 'Unknown named argument: bonus'),
        ('provider', provider, trace),
        ('main', entry.replace('score(base:1)', 'score(base:1,items:4)'), 'only bind fixed parameters'),
        ('main', entry.replace('score(base:1)', 'score(bonus:4)'), 'Missing required argument: base'),
        ('main', entry, trace),
    ])
