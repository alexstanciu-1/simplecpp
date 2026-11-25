"""Owned snapshots, read-only aliases and signature recovery for named PHP packs."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


provider = '''
function bump(int &$n):int {$n=$n+1;echo "B";return $n;}
function first(const vector<int> &$xs):int {return $xs[0];}
function collect(int &$n,int &...$xs):vector<int> {
    $n=9;$alias =& $xs;$copy=$alias;$copy[]=7;
    echo first($alias),count($alias);return $copy;
}
class Probe {
    public static function text(string &...$xs):string {return $xs[0];}
    public function flags(bool &...$xs):int {
        $total=0;foreach ($xs as $x) {if ($x) {$total=$total+1;}}return $total;
    }
}
function decimals(float &...$xs):float {return $xs[0];}
'''
entry = '''
$n=3;$copy=collect($n,$n,bump($n));echo ":",$n,":",$copy[0],$copy[1],$copy[2];
$p=new Probe();echo ":",$p->flags(),$p->flags(false,true),Probe::text("X");
if (decimals(1.5) === 1.5) {return 8;}return 1;
'''
adapter = 'function adapt(callback<int,bool...> $cb):int {return $cb(true);}'
mutable = 'function flags(bool ...$xs):int {return count($xs);}'
readonly = mutable.replace('bool ...', 'bool &...')
use = adapter + 'echo adapt(flags(...));return 8;'

with TemporaryDirectory(prefix='scpp-readonly-variadic-') as directory:
    revisions(Path(directory), 'php', dict(main=entry, provider=provider), [
        (None, None, b'B32:9:347:01X'),
        ('provider', provider + mutable, b'B32:9:347:01X'),
        ('main', use, b'1'),
        ('provider', provider + readonly, 'Callback target signature mismatch'),
        ('provider', provider + mutable, b'1'),
        ('child', 'class More extends Probe {public static function text(string &...$xs):string {return $xs[0];}}', b'1'),
        ('provider', provider.replace('string &...', 'string ...') + mutable, 'Static redeclaration signature must match exactly'),
        ('provider', provider + mutable, b'1'),
    ])
