"""Capture-free arrow lambdas, PHP block closures and callback conversion."""
import argparse
from pathlib import Path
import tempfile

from app_namespace_native import artifacts
from classes_native import fixture
from collection_goal_native import revisions


def prove(work, flavor):
    form = fixture(flavor)
    v, fn, infer = form['v'], form['function'], form['infer']

    def arrow(name, input_type, body, result='int'):
        parameter = f'{name}: {input_type}' if flavor == 'js' else f'{input_type} {v}{name}'
        return f'fn({parameter}): {result} => {body}'

    consumer = fn('apply', 'int', [('op', 'callback<int,int>')], f'return {v}op(3);')
    consumer += fn('read', 'int', [('op', 'callback<int,Box>')], f'return {v}op(new Box());')
    helper = fn('helper', 'int', [], 'return 4;')
    box = form['klass']('Box', form['field']('rank', 'int', '7'))
    local = arrow('a', 'int', f'{v}a + helper()')
    inline = arrow('b', 'int', f'{v}b * 2')
    nominal = arrow('item', 'Box', f'{v}item{form["access"]}rank')
    entry = infer('outer', '8') + infer('increment', local) + (
        f'echo {v}increment(2), ":", apply({v}increment), ":", apply({inline}), ":", read({nominal}); return 8;'
    )
    sources = dict(main=entry, consumer=consumer, helper=helper, box=box)
    work.mkdir()
    revisions(work, flavor, sources, [
        (None, None, b'6:7:6:7'),
        (None, None, b'6:7:6:7'),
        ('helper', helper.replace('4', '5'), b'7:8:6:7'),
        ('helper', fn('helper', 'int', [('extra', 'int')], 'return 5;'), 'Argument count mismatch'),
        ('helper', None, 'Unknown function: helper'),
        ('helper', helper, b'6:7:6:7'),
        ('main', entry.replace('helper()', f'{v}outer'), 'Unknown or not-yet-initialized local/global: outer'),
        ('main', entry.replace(inline, arrow('b', 'int', f'{v}b > 0', 'bool')), 'Expected exact callback signature'),
        ('main', entry, b'6:7:6:7'),
    ], unaffected_source=f'main.{flavor}', unaffected_revisions={2})
    generated = b'\n'.join(artifacts(work / f'{flavor}-out').values())
    assert b'auto var_increment_' in generated and b' = [](' in generated
    assert b'(*var_op_' in generated
    assert b'std::function' not in generated and b'#include <functional>' not in generated


def prove_block(work):
    worker = """function select_text(int $flag):string {
        $outer = 9;
        $choose = function(int $value):int {
            $outer = $value + 1;
            if ($value > 0) { return $outer; }
            return helper();
        };
        echo $choose($flag), ":", $outer;
        return "!";
    }"""
    entry = 'echo select_text(2), select_text(0); return 8;'
    helper = 'function helper():int { return 7; }'
    expected = b'3:9!7:9!'
    missing = worker.replace('return helper();', '')
    protected = worker.replace('$choose =', 'try { $choose =').replace(
        'return "!";', 'return "!"; } finally { echo "."; }')
    work.mkdir()
    revisions(work, 'php', dict(main=entry, worker=worker, helper=helper), [
        (None, None, expected),
        (None, None, expected),
        ('worker', missing, 'Non-void closure requires a value return'),
        (None, None, 'Non-void closure requires a value return'),
        ('worker', worker, expected),
        ('worker', worker.replace('$outer = $value + 1;', ''),
         'Unknown or not-yet-initialized local/global: outer'),
        ('worker', worker, expected),
        ('helper', helper.replace('helper()', 'helper(int $required)'), 'Argument count mismatch'),
        ('helper', helper, expected),
        ('worker', worker.replace('return "!";', ''), 'Non-void function requires a value return'),
        ('worker', protected, b'3:9.!7:9.!'),
    ], unaffected_source='main.php', unaffected_revisions={4, 6, 8, 10})


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-capture-free-lambdas-') as temporary:
        root = (args.output or Path(temporary)).resolve()
        root.mkdir(parents=True, exist_ok=True)
        prove_block(root / 'block')
        for flavor in ('php', 'cpp', 'js'):
            prove(root / flavor, flavor)


if __name__ == '__main__':
    main()
