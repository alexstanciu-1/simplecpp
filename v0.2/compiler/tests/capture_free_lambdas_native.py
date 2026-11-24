"""Capture-free arrow lambdas, PHP block closures and callback conversion."""
import argparse
from pathlib import Path
import tempfile

from app_namespace_native import artifacts
from classes_native import fixture
from collection_goal_native import guard, revisions


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


def prove_named_calls(work, flavor):
    form = fixture(flavor)
    v, fn = form['v'], form['function']
    params = 'left:int,right:byte' if flavor == 'js' else f'int {v}left,byte {v}right'
    definition = f'fn({params}):int => {v}left - {v}right'
    local = form['infer']('diff', definition)
    call = f'{v}diff(right: nextRight(), left: nextLeft())'
    entry = local + f'echo {call}, apply({v}diff);return 8;'
    helpers = fn('nextRight', 'int', [], 'echo "R";return 3;') + fn('nextLeft', 'int', [], 'echo "L";return 10;')
    consumer = fn('apply', 'int', [('op', 'callback<int,int,byte>')], f'return {v}op(10,3);')
    renamed = entry.replace(definition, definition.replace('right', 'offset'))
    repaired = renamed.replace('right:', 'offset:')
    expected = b'RL77'
    work.mkdir()
    revisions(work, flavor, dict(main=entry, helpers=helpers, consumer=consumer), [
        (None, None, expected), (None, None, expected),
        ('main', renamed, 'Unknown named argument: right on diff'),
        ('main', repaired, expected),
        ('consumer', consumer.replace('op(10,3)', 'op(left: 10,right: 3)'), 'Typed callback calls require positional arguments'),
        ('consumer', consumer, expected),
        ('helpers', None, 'Unknown function: nextRight'), ('helpers', helpers, expected),
    ])
    guard(work, flavor, 'narrowing', helpers.replace('return 3;', 'return 300;') + consumer + entry,
          'integer_cast_out_of_range', expected_stdout=b'R')


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
        ('worker', worker.replace('return helper();', 'try { return helper(); } finally { echo "F"; }'), b'3:9!F7:9!'),
    ], unaffected_source='main.php', unaffected_revisions={4, 6, 8, 10})


def prove_block_callback_result(work):
    consumer = 'function run_text(callback<string> $read):string { return $read(); }'

    def entry(closure):
        return f'echo run_text({closure}); return 8;'

    mismatch = 'Expected exact callback signature callback<string>'
    work.mkdir()
    revisions(work, 'php', dict(consumer=consumer, main=entry('function ():string { return "original"; }')), [
        (None, None, b'original'),
        ('main', entry('function ():int { return 1; }'), mismatch),
        (None, None, mismatch),
        ('main', entry('function ():string { return "repaired"; }'), b'repaired'),
    ], unaffected_source='consumer.php', unaffected_revisions={3})


def prove_block_local_scope(work):
    declaration = '$g = function ():int { return 1; };'
    inside = f'if (true) {{ {declaration} echo $g(); }} return 8;'
    outside = f'if (true) {{ {declaration} }} echo $g(); return 8;'
    work.mkdir()
    revisions(work, 'php', dict(main=inside), [
        (None, None, b'1'),
        ('main', outside, 'Invocation requires a typed callable local: g'),
        ('main', inside, b'1'),
    ])


def prove_block_append_placement(work):
    closure = 'function ():int { return 1; }'

    def entry(append):
        return f'$items vector<int> = []; {append} echo count($items), ":", $items[0]; return 8;'

    direct = entry(f'$items[] = {closure};')
    repaired = entry(f'$g = {closure}; $items[] = $g();')
    work.mkdir()
    revisions(work, 'php', dict(main=direct), [
        (None, None, 'Lambda requires an inferred local initializer or typed callback argument'),
        ('main', repaired, b'1:1'),
    ])


def prove_block_call_arity(work):
    declaration = '$h = function (int $x, int $y): int { return $x + $y; };'
    valid = f'{declaration} echo $h(y: 9, x: 3); return 8;'
    missing_argument = f'{declaration} echo $h(3); return 8;'
    work.mkdir()
    revisions(work, 'php', dict(main=valid), [
        (None, None, b'12'),
        ('main', missing_argument, 'Argument count mismatch for function: h'),
        ('main', valid, b'12'),
    ])


def prove_block_requires_result_type(work):
    missing = '$f = function () { return 1; }; echo $f(); return 8;'
    repaired = missing.replace('function ()', 'function (): int')
    work.mkdir()
    revisions(work, 'php', dict(main=missing), [
        (None, None, 'Expected : (lambda result type)'),
        ('main', repaired, b'1'),
    ])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-capture-free-lambdas-') as temporary:
        root = (args.output or Path(temporary)).resolve()
        root.mkdir(parents=True, exist_ok=True)
        prove_block(root / 'block')
        prove_block_requires_result_type(root / 'block-requires-result-type')
        prove_block_call_arity(root / 'block-call-arity')
        prove_block_append_placement(root / 'block-append-placement')
        prove_block_local_scope(root / 'block-local-scope')
        prove_block_callback_result(root / 'block-callback-result')
        for flavor in ('php', 'cpp', 'js'):
            prove(root / flavor, flavor)
            prove_named_calls(root / (flavor + "-named"), flavor)


if __name__ == '__main__':
    main()
