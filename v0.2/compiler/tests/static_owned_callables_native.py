"""Owned callable contracts on selected static methods, including inherited dispatch."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    base = """
        class Factory {
            public static function build(function<int(int)> $op):function<int(int)> {return $op;}
            public static function via(int $offset):function<int(int)> {
                return static::build(fn($v)=>$v+$offset);
            }
        }
    """
    child = """
        class Child extends Factory {
            public static function build(function<int(int)> $task):function<int(int)> {
                $copy=$task;unset($task);return fn($v)=>$copy($v)+10;
            }
        }
    """
    entry = """
        $offset=3;$op function<int(int)> = fn($v)=>$offset+$v;
        $fromBase=Factory::via(4);$fromChild=Child::via(4);$selected=Child::build(task:$op);
        echo $fromBase(2),":",$fromChild(2),":",$selected(2),":",$op(2);return 8;
    """
    trace = b'6:16:15:5'
    work.mkdir()
    revisions(work, 'php', dict(main=entry, base=base, child=child), [
        (None, None, trace), (None, None, trace),
        ('child', child.replace('$task', '$renamed'), 'Unknown named argument: task'),
        ('child', child, trace),
        ('child', child.replace('int(int)> $task', 'int(string)> $task'), 'Static redeclaration signature must match exactly'),
        ('child', child.replace('function<int(int)> $task', 'callback<int,int> $task'), 'Static redeclaration signature must match exactly'),
        ('child', child.replace('):function<int(int)>', '):function<int()>'), 'Static redeclaration signature must match exactly'),
        ('child', child, trace),
        ('base', base.replace('via(int $offset):function<int(int)>', 'via(int $offset):function<int()>')
                     .replace('static::build(fn($v)=>$v+$offset)', 'fn()=>$offset'), 'Argument count mismatch'),
        ('base', base, trace),
        ('main', entry.replace('task:$op', 'task:function(int $v) use (&$offset):int {return $v+$offset;}'),
         'cannot erase a borrowed environment'),
        ('main', entry, trace),
    ])


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-static-owned-callables-') as directory:
        prove(Path(directory) / 'app')
