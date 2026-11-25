"""PHP variadic closures preserve ordered calls, capture snapshots and exact signatures."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


consumer = 'function run(callback<int,int...> $op):int {return $op(4,5);}'
source = '''
    function next_value(int &$n):int {$n=$n+1;echo $n;return $n;}
    $show=function(int $prefix=7,int ...$xs):int {
        echo $prefix;foreach ($xs as $x) {echo $x;}return count($xs);
    };
    $n=1;echo $show(),":",$show($n,next_value($n),next_value($n)),":";
    echo run(fn(...$xs)=>count($xs)),":";
    $offset=6;
    $op function<int(string...)> = function(...$labels) use ($offset) {return $offset+count($labels);};
    $offset=9;echo $op(),$op("a","b");return 8;
'''
expected = b'70:231232:2:68'
with TemporaryDirectory(prefix='scpp-variadic-closures-') as directory:
    revisions(Path(directory), 'php', dict(main=source, consumer=consumer), [
        (None, None, expected),
        ('consumer', consumer.replace('int...', 'vector<int>').replace('$op(4,5)', '$op([4,5])'),
         'Expected exact callback signature'),
        ('consumer', consumer, expected),
        ('main', source.replace('use ($offset)', 'use (&$offset)'), 'borrowed environment'),
        ('main', source.replace('fn(...$xs)', 'fn($xs)'), 'Expected exact callback signature'),
        ('main', source, expected),
    ])

readonly_source = source.replace('int ...$xs', 'int &...$xs').replace('fn(...$xs)', 'fn(&...$xs)').replace('function(...$labels)', 'function(&...$labels)')
with TemporaryDirectory(prefix='scpp-readonly-closures-') as directory:
    revisions(Path(directory), 'php', dict(main=readonly_source, consumer=consumer), [
        (None, None, expected),
        ('main', readonly_source.replace('echo $prefix;', '$xs[0]=0;echo $prefix;'), 'read-only variadic pack'),
        ('main', readonly_source.replace('return $offset+count($labels);', '$labels[]="x";return $offset;'), 'read-only variadic pack'),
        ('main', readonly_source, expected),
        ('consumer', consumer.replace('int...', 'vector<int>').replace('$op(4,5)', '$op([4,5])'), 'Expected exact callback signature'),
        ('consumer', consumer, expected),
    ])
