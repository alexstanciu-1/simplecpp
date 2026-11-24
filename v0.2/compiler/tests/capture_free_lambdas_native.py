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
        ('main', entry.replace('helper()', f'{v}outer'), 'Typed callbacks require a capture-free callable'
         if flavor == 'php' else 'Unknown or not-yet-initialized local/global: outer'),
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
        ('main', edited.replace('$offset=5;', '$offset="5";').replace('$offset=10;', '$offset="10";').replace('return $n + $offset;', 'echo $offset; return $n;'), b'54'),
        ('main', edited.replace('return $n + $offset;', '$offset=8; return $n;'), 'Cannot write to by-value capture'),
        ('main', edited.replace('int $n)', 'int $offset)'), 'Capture conflicts with closure parameter'),
        ('main', entry, b'7'),
        ('main', edited.replace('return $n + $offset;', 'bump($offset);return $n;'), 'Cannot write to by-value capture'),
        ('main', edited.replace('use ($offset)', 'use (&$offset)'), 'Expected variable'),
        ('main', edited.replace('return $n + $offset;', 'return $n;'), b'4'),
        ('main', entry.replace('$offset=3;', '$offset=seed();'), 'Unknown function: seed'),
        ('seed', 'function seed():int {return 3;}', b'7'),
        ('seed', 'function seed():Iterator<int> {yield 3;}', 'Generator cursors are move-only'),
        ('seed', 'function seed():int {return 5;}', b'9'),
    ], unaffected_source='consumer.php', unaffected_revisions={2, 4, 11})


def prove_multiple_int_captures(work):
    captures = 'use ($base, $step)'
    closure = f'function () {captures}:int {{ return $base * 10 + $step; }}'
    entry = f'$base=2; $step=initialStep(); $digits={closure}; $base=8; $step=9; echo $digits(); return 8;'
    duplicate = entry.replace(captures, 'use ($base, $base)')
    column = duplicate.index('$base)', duplicate.index('use (')) + 1
    helper = 'function initialStep():int {return 3;}'
    work.mkdir()
    revisions(work, 'php', dict(main=entry, helper=helper), [
        (None, None, b'23'), (None, None, b'23'),
        ('main', duplicate, f'main.php:1:{column}: Duplicate closure capture: base'),
        ('main', entry, b'23'),
        ('main', entry.replace(captures, 'use ($step, $base)'), b'23'),
        ('helper', 'function initialStep():Iterator<int> {yield 3;}', 'Generator cursors are move-only'),
        ('helper', helper, b'23'),
    ])


def prove_capture_copy_contract(work):
    model = 'class Counter { public int $n=1; }'
    entry = """$items vector<int> = [2]; $enabled=true; $label="before"; $owner=new Counter();
        $alias=$owner;
        $read=function () use ($enabled, $label, $items, $owner):void {
            if ($enabled) { echo $label, $items[0], $owner->n; }
        };
        $enabled=false; $label="after"; $items[0]=8; $owner=new Counter();
        $owner->n=4; $alias->n=7; $read(); return 8;"""
    binding = '$value=sourceValue();'
    capture = binding + ' $read=function () use ($value):void {echo $value;}; $read(); return 8;'
    cursor = 'function sourceValue():Iterator<int> {yield 2;}'
    string = 'function sourceValue():string {return "ok";}'
    line_prefix = f"main.php:1:{capture.index('$value)') + 1}: "
    work.mkdir()
    revisions(work, 'php', dict(main=entry, model=model, source=string), [
        (None, None, b'before27'), (None, None, b'before27'),
        ('main', entry.replace('if ($enabled)', '$items[0]=0; if ($enabled)'), 'Cannot write to by-value capture'),
        ('main', capture, b'ok'),
        ('source', cursor, line_prefix + 'Generator cursors are move-only'),
        ('main', binding + ' $copy=$value; return 8;', 'Generator cursors are move-only'),
        ('main', capture, line_prefix + 'Generator cursors are move-only'),
        ('source', string, b'ok'),
    ])


def prove_php_arrow_captures(work):
    entry = '$factor=seed(); $scale=fn(int $x):int => $x*$factor+$factor; $factor=10; echo $scale(3); return 8;'
    shadow = entry.replace('int $x', 'int $factor').replace('$x*$factor+$factor', '$factor*$factor')
    helper = 'function seed():int {return 3;}'
    interpolation = '$factor=seed(); $f=fn():string => "value=$factor"; echo $f(); return 8;'
    name_column = interpolation.index('factor', interpolation.index('=>')) + 1
    first_use = entry.index('$factor', entry.index('=>')) + 1
    block = '$factor=3; $run=function () use ($factor):int { $f=fn():int => $factor; return $f(); }; $factor=9; echo $run(); return 8;'
    work.mkdir()
    revisions(work, 'php', dict(main=entry, helper=helper), [
        (None, None, b'12'), (None, None, b'12'),
        ('helper', 'function seed():Iterator<int> {yield 3;}', f'main.php:1:{first_use}: Generator cursors are move-only'),
        ('helper', helper, b'12'),
        ('main', shadow, b'9'),
        ('main', block, b'3'),
        ('main', block.replace(' use ($factor)', ''), 'Unknown or not-yet-initialized local/global: factor'),
        ('main', interpolation, b'value=3'),
        ('helper', 'function seed():Iterator<int> {yield 3;}', f'main.php:1:{name_column}: Generator cursors are move-only'),
        ('helper', helper, b'value=3'),
        ('main', 'global $factor int=3; function read(callback<int> $op):int {return $op();} '
                 '$f=fn():int => $factor; echo read($f); return 8;', b'3'),
        ('main', 'function apply(callback<int,int> $op):int {return $op(2);} '
                 '$factor=3; $f=fn():int => $factor+apply(fn(int $factor):int => $factor); '
                 '$factor=10; echo $f(); return 8;', b'5'),
        ('main', entry, b'12'),
    ])


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
        prove_multiple_int_captures(root / 'multiple-int-captures')
        prove_capture_copy_contract(root / 'capture-copy-contract')
        prove_php_arrow_captures(root / 'php-arrow-captures')
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
