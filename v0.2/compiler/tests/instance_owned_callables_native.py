"""Owned callable method contracts, interface and base dispatch, and escaping snapshots."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    api = 'interface Mapper {public function wrap(function<int(int)> $op):function<int(int)>;}'
    base = """
        class BaseMapper implements Mapper {
            public int $offset=3;
            public override function wrap(function<int(int)> $baseOp):function<int(int)> {return $baseOp;}
            public function snapshot():function<int(int)> {
                $offset=$this->offset;return fn($n)=>$n+$offset;
            }
        }
    """
    child = """
        class DerivedMapper extends BaseMapper {
            public override function wrap(function<int(int)> $task):function<int(int)> {
                $saved=$task;unset($task);return fn($n)=>$saved($n)+10;
            }
        }
    """
    main = """
        function fromInterface(Mapper $m,int $k):function<int(int)> {return $m->wrap(op:fn($n)=>$n+$k);}
        function fromBase(BaseMapper $m,int $k):function<int(int)> {return $m->wrap(baseOp:fn($n)=>$n+$k);}
        function snapshot():function<int(int)> {
            $m=new BaseMapper();$copy=$m->snapshot();$m->offset=90;return $copy;
        }
        $i=fromInterface(new DerivedMapper(),4);$b=fromBase(new DerivedMapper(),7);$s=snapshot();
        echo $i(2),":",$b(2),":",$s(2);return 8;
    """
    trace = b'16:19:5'
    mismatch = 'Override signature must match exactly'
    revisions(work, 'php', dict(main=main, api=api, base=base, child=child), [
        (None, None, trace), (None, None, trace),
        ('base', base.replace('function<int(int)> $baseOp', 'function<int(string)> $baseOp'), mismatch),
        ('base', base, trace),
        ('child', child.replace('):function<int(int)>', '):function<int()>'), mismatch),
        ('child', child, trace),
        ('base', base.replace('fn($n)=>$n+$offset', 'fn($n)=>$n+$this->offset'), 'cannot erase a borrowed environment'),
        ('base', base, trace),
    ])


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-instance-owned-callables-') as directory:
        prove(Path(directory))
