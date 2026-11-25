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

unpack_provider = '''
function inspect(vector<int> &$source,int &...$snapshot):int {
    clean($source);return $snapshot[0]+count($snapshot);
}
class Reader {
    public static function size(int $base,int &...$xs):int {return $base+count($xs);}
    public function sizeOf(int &...$xs):int {return count($xs);}
}
'''
unpack_entry = '''
function prefix():int {echo "P";return 3;}
function items():vector<int> {echo "V";$xs vector<int> = [8];return $xs;}
$xs vector<int> = [7,9];echo inspect($xs,...$xs),":",count($xs),":";
echo Reader::size(base:prefix(),...items()),":";
$r=new Reader();echo $r->sizeOf(...$xs);return 8;
'''
unpack_trace = b'9:0:PV4:0'

with TemporaryDirectory(prefix='scpp-readonly-unpack-') as directory:
    revisions(Path(directory), 'php', dict(main=unpack_entry, provider=unpack_provider), [
        (None, None, unpack_trace),
        ('provider', unpack_provider.replace('sizeOf(int &...', 'sizeOf(float &...'), 'Unpacked argument requires vector<float64>'),
        ('provider', unpack_provider, unpack_trace),
    ])

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
