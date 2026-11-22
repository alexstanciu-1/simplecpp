"""Run reference parameter fixtures through the same instrumented native build proof as string locals."""
import argparse
from pathlib import Path
import tempfile
from collection_goal_native import revisions
from strings_native import prove


def prove_local_aliases(work):
    """Check that lexical aliases lower to the original storage and never own it."""
    base = """
        $value int = 10;
        $alias =& $value;
        $copy = $alias;
        $alias = 20;
    """
    main = base + """
        {
            $value int = 100;
            $inside int =& $alias;
            bump($inside);
            echo $value, ":";
        }
        $values vector<int> = [1, 2, 3];
        $vectorAlias =& $values;
        $replacement vector<int> = [8];
        $vectorAlias = $replacement;
        $vectorAlias[] = 9;
        echo $value, ":", $copy, ":", count($values), ":", $values[0], ":", count($replacement);
        return 8;
    """
    worker = 'function bump(int &$item): void { $item += 3; }'
    expected = b'100:23:10:2:8:1'
    target_error = 'Local alias requires a previously initialized named local'
    work.mkdir(parents=True, exist_ok=True)
    revisions(work, 'php', dict(main=main, worker=worker), [
        (None, None, expected),
        (None, None, expected),
        ('worker', worker.replace('3', '4'), b'100:24:10:2:8:1'),
        ('main', base + '$alias =& $value; return 8;', 'Duplicate local: alias'),
        ('main', '$first =& $future; $future int = 1; return 8;', target_error),
        ('main', '$value int = 10; $a =& value; return 8;', target_error),
        ('main', '$v vector<int> = [1]; $a =& $v[0]; return 8;', target_error),
        ('main', '$a =& (1 + 2); return 8;', target_error),
        ('main', '$v int = 1; $a string =& $v; return 8;', 'Local alias requires the exact storage type'),
        ('main', '$v int = 1; { $a =& $v; } echo $a; return 8;', 'Unknown or not-yet-initialized local/global: a'),
        ('main', base + '$fn = fn(): int => $alias; return 8;', 'Unknown or not-yet-initialized local/global: alias'),
        ('main', 'struct Box {public int $x=1;} $box Box; $a =& $box->x; return 8;', target_error),
        ('main', 'function f(int $v): void { $a =& $v; } return 8;', target_error),
        ('worker', None, None),
        ('main', main, 'Unknown function: bump'),
        ('worker', worker, expected),
    ])
    generated = '\n'.join(p.read_text() for p in (work / 'php-out').rglob('*.cpp'))
    assert all(label not in generated for label in ('var_alias_', 'var_inside_', 'var_vectorAlias_'))


def prove_reference_returns(work):
    """Exercise the reference ABI and the caller-side slot through incremental builds."""
    select = 'function &select(int &$slot): int { echo "S"; $alias =& $slot; { $nested int =& $alias; $nested += 1; return $nested; } }'
    after = """
        function &select_after(string $first, int &$slot, string $second): int {
            echo $first, $second;
            $slot += 2;
            return $slot;
        }
        function &select_vector(vector<int> &$slot): vector<int> { return $slot; }
    """
    main = """
        function tag(string $text): string { echo $text; return $text; }
        $value int = 10;
        $selected =& select($value);
        $alias =& select_after(tag("A"), $value, tag("B"));
        $copy = select($value);
        $alias = 20;
        $selected += 1;
        $copy += 1;
        $vector vector<int> = [1];
        $vectorAlias =& select_vector($vector);
        $vectorCopy = select_vector($vector);
        $vectorAlias[] = 2;
        echo $value, ":", $copy, ":", count($vector), ":", count($vectorCopy);
        return 8;
    """
    expected = b'SABABS21:15:2:1'
    return_error = 'Reference return must originate in its writable parameter'
    signature_error = 'Reference return requires exactly one writable parameter of the return type'
    work.mkdir(parents=True, exist_ok=True)
    revisions(work, 'php', dict(main=main, select=select, after=after), [
        (None, None, expected),
        (None, None, expected),
        ('select', select.replace('+= 1', '+= 2'), b'SABABS21:17:2:1'),
        ('select', select.replace('function &', 'function '), 'Local alias call requires a reference return'),
        ('select', select.replace('return $nested;', '$local int = 7; $unsafe =& $local; return $unsafe;'), return_error),
        ('select', select.replace('return $nested;', 'return 7;'), return_error),
        ('select', select.replace('return $nested;', '$local int = 7; return $local;'), return_error),
        ('select', 'function &select(int &$slot): int { $r =& select($slot); return $r; }', 'Recursive reference return is unsupported'),
        ('select', 'function &select(int &$slot, int $copy): int { return $copy; }', return_error),
        ('select', 'function &select(int &$slot): int { { $alias =& $slot; } return $alias; }', return_error),
        ('select', 'function &select(int &$slot, vector<int> $values): int { return $values[0]; }', return_error),
        ('select', 'function &select(int &$slot): int { try { return $slot; } finally {} }', 'Reference returns across finally are deferred'),
        ('select', 'class Ops { public function &select(int &$slot): int { return $slot; } }', 'Method reference return requires a final non-overriding instance method'),
        ('select', 'function &select(vector<int> &$slot): int { return $slot[0]; }', signature_error),
        ('select', 'function &select(int $slot): int { return $slot; }', signature_error),
        ('select', 'function &select(int &$slot, int &$other): int { return $slot; }', signature_error),
        ('select', None, 'Unknown function: select'),
        ('select', select, expected),
        ('main', main.replace('select($value)', 'select(1)', 1), 'Reference argument requires stable variable storage'),
        ('main', main, expected),
    ])


def prove_forwarding(work):
    main = """
        $value int = 10;
        $alias =& forward($value);
        $copy = forward($value);
        $alias += 8;
        echo $value, ":", $copy;
        return 8;
    """
    forward = 'function &forward(int &$slot): int { $r =& middle($slot); return $r; }'
    middle = 'function &middle(int &$slot): int { $a =& $slot; return leaf($a); }'
    leaf = 'function &leaf(int &$v): int { echo "L"; $v += 1; return $v; }'
    expected = b'LL20:12'
    origin_error = 'Reference return must originate in its writable parameter'
    cycle_error = 'Recursive reference return is unsupported'
    work.mkdir(parents=True)
    revisions(work, 'php', dict(main=main, forward=forward, middle=middle, leaf=leaf), [
        (None, None, expected),
        (None, None, expected),
        ('middle', middle.replace('$a =& $slot;', '$a int = 1;'), origin_error),
        ('middle', middle.replace('$a =& $slot; return leaf($a);', '$v int = 1; $a =& leaf($v); return $a;'), origin_error),
        ('middle', middle, expected),
        ('leaf', leaf.replace('return $v;', 'return forward($v);'), cycle_error),
        (None, None, cycle_error),
        ('leaf', leaf, expected),
        ('leaf', leaf.replace('return $v;', 'helper($v); return $v;') +
         ' function helper(int &$v): int { return forward($v); }', cycle_error),
        ('leaf', leaf, expected),
        ('leaf', leaf.replace('return $v;', 'step(2); return $v;') +
         ' function step(int $n): int { if ($n > 0) { return step($n - 1); } return 0; }', expected),
    ])


def prove_method_returns(work):
    selector = """
        class Selector {
            public int $field = 99;
            public final function &select(int &$slot): int {
                echo "S";
                $alias =& $slot;
                $alias += 1;
                return $alias;
            }
        }
    """
    derived = 'class Derived extends Selector {}'
    forward = """
        function &forward(int &$slot): int {
            $selector = new Derived();
            return $selector->select($slot);
        }
    """
    main = """
        $selector = new Derived();
        $n int = 10;
        $copy = $selector->select($n);
        $alias =& forward($n);
        $alias += 8;
        $direct =& $selector->select($n);
        $direct += 1;
        echo $n, ":", $copy;
        return 8;
    """
    result = b'SSS22:11'
    origin_error = 'Reference return must originate in its writable parameter'
    method_error = 'Method reference return requires a final non-overriding instance method'
    cycle_error = 'Recursive reference return is unsupported'
    work.mkdir(parents=True)
    revisions(work, 'php', dict(main=main, selector=selector, derived=derived, forward=forward), [
        (None, None, result),
        (None, None, result),
        ('selector', selector.replace('return $alias;', 'return $this->field;'), origin_error),
        ('selector', selector.replace('return $alias;', '$local int = 7; return $local;'), origin_error),
        ('selector', selector.replace('public final function', 'public function'), method_error),
        ('selector', selector.replace('public final function', 'public final static function'), method_error),
        ('selector', selector.replace('return $alias;', 'return forward($alias);'), cycle_error),
        (None, None, cycle_error),
        ('selector', selector, result),
        ('selector', selector.replace('return $alias;', 'return $this->helper($alias);')
         .replace('public int $field', 'public final function &helper(int &$v): int { return forward($v); } public int $field'),
         cycle_error),
        ('selector', selector.replace('return $alias;', '$this->helper($alias); return $alias;')
         .replace('public int $field', 'public final function helper(int &$v): int { return forward($v); } public int $field'),
         cycle_error),
        ('selector', selector, result),
        ('derived', 'interface I {public function select(int &$slot): int;} class Derived extends Selector implements I {}',
         'signature must match exactly'),
        ('derived', derived, result),
    ])


def run(work):
    prove(work, 'reference_parameters.php', check_copy=False)
    prove_local_aliases(work / 'aliases')
    prove_reference_returns(work / 'returns')
    prove_forwarding(work / 'forwarding')
    prove_method_returns(work / 'methods')

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        run(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-reference-parameters-native-') as directory:
            run(Path(directory))
