"""Mutable callable inputs retain exact caller storage through native and incremental builds."""
import argparse
from pathlib import Path
from tempfile import TemporaryDirectory

from classes_native import fixture
from collection_goal_native import revisions


def typed(work, flavor):
    forms = fixture(flavor)
    fn, v = forms['function'], forms['v']
    refs = [('a', 'int&'), ('b', 'int&')] if flavor != 'js' else [('&a', 'int'), ('&b', 'int')]
    target = fn('change', 'void', refs, f'{v}a=3;{v}b={v}b+{v}a+4;')
    consumer = fn('apply', 'int', [('op', 'callback<void,int&,int&>')],
                  forms['infer']('n', '0') + f'{v}op({v}n,{v}n);return {v}n;')
    main = 'echo apply(change(...));return 8;'
    revisions(work, flavor, dict(main=main, target=target, consumer=consumer), [
        (None, None, b'10'), (None, None, b'10'),
        ('consumer', consumer.replace('int&,int&', 'int,int&'), 'Callback target signature mismatch'),
        ('target', target.replace('&', '', 1), b'7'),
        ('consumer', consumer, 'Callback target signature mismatch'),
        ('target', target, b'10'),
        ('consumer', consumer.replace(f'op({v}n,', 'op(1,'), 'Reference argument requires stable variable storage'),
        ('target', None, None), ('consumer', consumer, None), ('target', target, b'10'),
        ('main', f'echo apply(fn(&{v}a,&{v}b)=>change({v}a,{v}b));return 8;', b'10'),
    ])


def owned(work, quick=False):
    producer = """
        function make(int $step):function<void(int&,int&)> {
            return function(&$a, &$b) use ($step) {$a=$a+$step;$b=$b+$step;};
        }
    """
    consumer = """
        function run(function<void(int&,int&)> $op):int {
            $n=2;$op($n,$n);return $n;
        }
    """
    main = 'echo run(make(3));return 8;'
    if quick:
        typed_api = 'function alias(int &$a,int &$b):void {$a=3;$b=$b+$a+4;}' \
                    'function apply(callback<void,int&,int&> $op):int {$n=0;$op($n,$n);return $n;}'
        source = producer + consumer + typed_api + 'echo apply(alias(...)), ":";' + main
        revisions(work, 'php', dict(main=source), [
            (None, None, b'10:8'),
            ('main', source.replace('$op($n,$n)', '$op(3,$n)', 1), 'Reference argument requires stable variable storage'),
            ('main', source.replace('callback<void,int&,int&>', 'callback<void,int,int&>'), 'Callback target signature mismatch'),
            ('main', source.replace('return $n;', 'return $n+1;', 1), b'10:9'),
        ])
        return
    revisions(work, 'php', dict(main=main, producer=producer, consumer=consumer), [
        (None, None, b'8'), (None, None, b'8'),
        ('producer', producer.replace('int&,int&', 'int,int&').replace('&$a', '$a'), 'Expected exact callable signature'),
        ('producer', producer, b'8'),
        ('producer', producer.replace('&$a', '$a'), 'Expected exact callable signature'),
        ('producer', producer, b'8'),
        ('consumer', consumer.replace('$op($n,$n)', '$op(3,$n)'), 'Reference argument requires stable variable storage'),
        ('consumer', consumer.replace('$op($n,$n)', '$s="x";$op($s,$n)'), 'Reference argument requires exact storage type'),
        ('consumer', consumer + 'function denied(const vector<int> &$items,function<void(vector<int>&)> $op):void {$op($items);}',
         'Cannot write through read-only reference'),
        ('consumer', consumer, b'8'),
        ('main', """
            function setLater(int &$n):int {$n=10;return 1;}
            $op function<void(int,int&,int)> = function($before,&$slot,$extra) {$slot=$before+$slot+$extra;};
            $n=2;$op($n,$n,setLater($n));echo $n;return 8;
        """, b'13'),
        ('main', """
            $refs vector<function<int(int&)>> = [fn(&$n)=>$n];
            $values vector<function<int(int)>> = $refs;return 8;
        """, 'vector<function<int64(int64&)>> to vector<function<int64(int64)>>'),
        ('main', main, b'8'),
    ])


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--quick', action='store_true')
    args = parser.parse_args()
    with TemporaryDirectory(prefix='scpp-callable-refs-') as directory:
        root = Path(directory)
        if args.quick:
            owned(root, quick=True)
        else:
            for flavor in ('php', 'cpp', 'js'):
                typed(root, flavor)
            owned_root = root / 'owned'
            owned_root.mkdir()
            owned(owned_root)
