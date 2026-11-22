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
        ('main', 'function f(int &$v): void { $a =& $v; } return 8;', target_error),
        ('worker', None, None),
        ('main', main, 'Unknown function: bump'),
        ('worker', worker, expected),
    ])
    generated = '\n'.join(p.read_text() for p in (work / 'php-out').rglob('*.cpp'))
    assert all(label not in generated for label in ('var_alias_', 'var_inside_', 'var_vectorAlias_'))


def run(work):
    prove(work, 'reference_parameters.php', check_copy=False)
    prove_local_aliases(work / 'aliases')

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        run(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory(prefix='scpp-reference-parameters-native-') as directory:
            run(Path(directory))
