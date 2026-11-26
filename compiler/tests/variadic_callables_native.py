"""Direct and indirect calls share a by-value tail and an exact source signature."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


target = 'function score(int $base,string ...$labels):int {echo $base;foreach ($labels as $s) {echo $s;}return count($labels);}'
consumer = '''
function apply(callback<int,int,string...> $op):int {return $op(3,label("a"),label("b"));}
function label(string $s):string {echo $s;return $s;}
function owned(function<int(int,string...)> $op):int {return $op(4,label("c"),label("d"));}
'''
main = 'echo score(1),score(2,label("x"),label("y")),apply(score(...)),owned(score(...));return 8;'
expected = b'10xy2xy2ab3ab2cd4cd2'
with TemporaryDirectory(prefix='scpp-variadic-') as directory:
    revisions(Path(directory), 'php', dict(main=main, target=target, consumer=consumer), [
        (None, None, expected),
        ('consumer', consumer.replace('label("a")', '1'), 'string'),
        ('consumer', consumer, expected),
        ('main', 'echo apply(score(...));return 8;', b'ab3ab2'),
        ('target', target.replace('string ...', 'vector<string> '), 'Callback target signature mismatch'),
        ('target', target, b'ab3ab2'),
        ('main', main.replace('score(1)', 'score()'), 'Argument count mismatch'),
        ('main', 'echo owned(score(...));return 8;', b'cd4cd2'),
        ('target', target.replace('string ...', 'vector<string> '), 'Callback target signature mismatch'),
        ('target', target, b'cd4cd2'),
        ('main', """
            function bump(int &$n):int {$n=$n+1;return $n;}
            function show(int $base=7,int ...$xs):int {echo $base;foreach ($xs as $x) {echo $x;}return count($xs);}
            $n=1;echo show(),show($n,bump($n),bump($n));return 8;
        """, b'701232'),
        ('main', '$op function<int(int,string...)> = score(...);echo $op(6);return 8;', b'60'),
        ('main', main, expected),
    ])
