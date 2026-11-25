"""Variadic static targets retain their source marker across dispatch and incremental edits."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


base = '''
class Scores {
    public static function via(int ...$items):int {return static::sum(1,...$items);}
    public static function sum(int $start,int ...$items):int {
        foreach ($items as $item) {$start=$start+$item;}
        $items[]=99;return $start;
    }
}
'''
child = '''
class Bonus extends Scores {
    public static function sum(int $start,int ...$items):int {
        return 10+parent::sum($start,...$items);
    }
}
'''
direct = '''
function mark(int $n):int {echo $n;return $n;}
$items vector<int> = [2,3];
echo Scores::sum(1),":",Scores::sum(mark(1),mark(2),mark(3)),":",
    Scores::sum(1,...$items),":",count($items);return 8;
'''
adapt = '''
function apply(callback<int,int,int...> $op):int {return $op(2,3);}
$op function<int(int,int...)> = Scores::sum(...);
echo apply(Scores::sum(...)),":",$op(4,5);return 8;
'''
fixed = base.replace('int ...$items', 'vector<int> $items')
fixed_child = child.replace('int ...$items', 'vector<int> $items')
trace = b'1:1236:6:2'

with TemporaryDirectory(prefix='scpp-variadic-static-') as directory:
    revisions(Path(directory), 'php', dict(main=direct, base=base), [
        (None, None, trace),
        ('main', '$xs vector<int> = [];echo Scores::sum(7,...$xs);return 8;', b'7'),
        ('base', fixed, 'Unpacking requires'),
        ('base', base, b'7'),
        ('main', adapt, b'5:9'),
        ('base', fixed, 'Callback target signature mismatch'),
        ('base', base, b'5:9'),
        ('main', 'function apply(callback<int,int,int...> $op):int {return $op(1);}echo apply(Scores::sum(...));return 8;', b'1'),
        ('base', fixed, 'Callback target signature mismatch'),
        ('base', base, b'1'),
        ('main', adapt, b'5:9'),
        ('child', child, b'5:9'),
        ('main', adapt.replace('Scores::sum(...)', 'Bonus::sum(...)'), b'15:19'),
        ('child', fixed_child, 'Static redeclaration signature must match exactly'),
        ('child', child, b'15:19'),
        ('base', fixed, 'Static redeclaration signature must match exactly'),
        ('base', base, b'15:19'),
        ('main', 'echo Scores::via(2,3),":",Bonus::via(2,3);return 8;', b'6:16'),
    ])
