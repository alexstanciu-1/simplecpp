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


def prove_defaults(work, flavor):
    form = fixture(flavor)
    v, fn = form['v'], form['function']
    params = 'a:int,b:byte=2,c:int=3' if flavor == 'js' else f'int {v}a,byte {v}b=2,int {v}c=3'
    definition = f'fn({params}):int => {v}a*100+{v}b*10+{v}c'
    entry = (form['infer']('digits', definition)
             + f'echo {v}digits(4),":",{v}digits(4,c:9),":",apply({v}digits);return 8;')
    consumer = fn('apply', 'int', [('op', 'callback<int,int,byte,int>')], f'return {v}op(4,8,6);')
    edited = entry.replace('=2', '=5')
    required = edited.replace('=5', '')
    initial_trace, edited_trace = b'423:429:486', b'453:459:486'
    work.mkdir()
    revisions(work, flavor, dict(main=entry, consumer=consumer), [
        (None, None, initial_trace), (None, None, initial_trace),
        ('main', edited, edited_trace),
        ('main', required, 'Argument count mismatch for function: digits'),
        ('main', required.replace('digits(4)', 'digits(4,1)'), 'Missing required argument: b'),
        ('main', edited, edited_trace),
        ('consumer', consumer.replace('op(4,8,6)', 'op(4)'), 'Argument count mismatch for function: op'),
        ('consumer', consumer.replace('op(4,8,6)', 'op(4,b:8,c:6)'), 'Typed callback calls require positional arguments'),
        ('consumer', consumer, edited_trace),
        ('main', edited.replace('=3', ''), 'Required parameter follows optional parameter'),
        ('main', edited.replace('=5', '=4+1'), 'Parameter defaults require literals'),
        ('main', edited.replace('=5', '=300'), 'outside uint8 range'),
        ('main', entry, initial_trace),
    ])


def prove_enum_default(work, flavor):
    form = fixture(flavor)
    v = form['v']
    enum = 'enum Kind {case First=1;case Selected=7;}' if flavor == 'php' else 'enum Kind {First=1,Selected=7}'
    case = 'Kind.Selected' if flavor == 'js' else 'Kind::Selected'
    param = 'k:Kind' if flavor == 'js' else f'Kind {v}k'
    local = form['infer']('readKind', f'fn({param}={case}):int => enum_value({v}k)')
    work.mkdir()
    revisions(work, flavor, dict(main=local + f'echo {v}readKind();return 8;', kind=enum), [
        (None, None, b'7'), ('kind', enum.replace('=7', '=5'), b'5'),
        ('kind', enum.replace('Selected', 'Other'), 'Unknown enum case'),
        ('kind', enum, b'7'),
    ])


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


def prove_int_capture(work):
    closure = 'function (int $n) use ($offset):int { return $n + $offset; }'
    entry = f'$offset=3; $add={closure}; $offset=10; echo $add(4); return 8;'
    edited = entry.replace('$offset=3', '$offset=5')
    callback = 'function apply(callback<int,int> $op):int {return $op(4);} function bump(int &$n):void {$n+=1;}'
    callback_error = 'Typed callbacks require a capture-free callable'
    work.mkdir()
    revisions(work, 'php', dict(main=entry, consumer=callback), [
        (None, None, b'7'), (None, None, b'7'), ('main', edited, b'9'),
        ('main', edited.replace(' use ($offset)', ''), 'Unknown or not-yet-initialized local/global: offset'),
        ('main', edited, b'9'),
        ('main', edited.replace('$add(4)', 'apply($add)'), callback_error),
        ('main', f'$offset=5; echo apply({closure}); return 8;', callback_error),
        ('main', edited.replace('$offset=5;', ''), 'Capture requires an initialized local: offset'),
        ('main', edited.replace('$offset=5;', '$offset="5";'), 'By-value capture requires an int local: offset'),
        ('main', edited.replace('return $n + $offset;', '$offset=8; return $n;'), 'Cannot write to by-value capture'),
        ('main', edited.replace('int $n)', 'int $offset)'), 'Capture conflicts with closure parameter'),
        ('main', entry, b'7'),
        ('main', edited.replace('return $n + $offset;', 'bump($offset);return $n;'), 'Cannot write to by-value capture'),
        ('main', edited.replace('use ($offset)', 'use (&$offset)'), 'Expected variable'),
        ('main', edited.replace('return $n + $offset;', 'return $n;'), b'4'),
        ('main', entry.replace('$offset=3;', '$offset=seed();'), 'Unknown function: seed'),
        ('seed', 'function seed():int {return 3;}', b'7'),
        ('seed', 'function seed():string {return "3";}', 'By-value capture requires an int local'),
        ('seed', 'function seed():int {return 5;}', b'9'),
    ], unaffected_source='consumer.php', unaffected_revisions={2, 4, 11})


def prove_void_block(work):
    consumer = 'function notify(callback<void,int> $op):void { $op(7); }'
    main = '''$report = function (int $n): void {
        if ($n === 0) { return; }
        echo $n;
    };
    $report(0);
    notify($report);
    return 8;'''
    work.mkdir()
    revisions(work, 'php', dict(main=main, consumer=consumer), [
        (None, None, b'7'), (None, None, b'7'),
        ('main', main.replace('return;', 'return $n;'), 'Void function cannot return a value'),
        ('main', main, b'7'),
        ('main', main.replace('$report(0);', 'echo $report(0);'), 'Void call cannot be used as a value'),
        ('main', '$f = fn(int $n):void => $n; return 8;', 'Expression-bodied lambda requires a non-void result type'),
        ('main', main, b'7'),
    ], unaffected_source='consumer.php', unaffected_revisions={3, 6})


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
        ('main', valid.replace('int $x, int $y', 'int $x=3, int $y=9').replace('y: 9, x: 3', ''), b'12'),
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
        prove_void_block(root / 'void-block')
        prove_int_capture(root / 'int-capture')
        prove_block_requires_result_type(root / 'block-requires-result-type')
        prove_block_call_arity(root / 'block-call-arity')
        prove_block_append_placement(root / 'block-append-placement')
        prove_block_local_scope(root / 'block-local-scope')
        prove_block_callback_result(root / 'block-callback-result')
        for flavor in ('php', 'cpp', 'js'):
            prove(root / flavor, flavor)
            prove_named_calls(root / (flavor + "-named"), flavor)
            prove_defaults(root / (flavor + "-defaults"), flavor)
            prove_enum_default(root / (flavor + "-enum-default"), flavor)


if __name__ == '__main__':
    main()
