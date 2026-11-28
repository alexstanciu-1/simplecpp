"""A declared PHP field is read and formatted at its position in the string."""
import sys
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import guard, revisions


def fields(work):
    model = 'class User {public string $name="Ada";public int $score=-12;}'
    main = """
        function render(User $u):string {return "{$u->score}/{$u->name}";}
        $u=new User();$saved=render($u);$u->score=0;
        echo "{$u->name}:{$u->score} ",$saved;return 8;
    """
    result = b'Ada:0 -12/Ada'
    revisions(work, 'php', dict(main=main, model=model), [
        (None, None, result),
        ('model', model.replace('$name', '$label'), 'Unknown field User.name'),
        ('model', model, result),
        ('main', '$u dynamic=[];echo "{$u->name}";return 8;', 'Field interpolation requires a declared record field'),
        ('main', main.replace('{$u->name}', '{$u->name()}'), 'Expected closing interpolation brace'),
    ])


def order(work):
    setup = 'class Row {public float $number;public mixed $box;} $r=new Row();$r->number=1e300*1e300;echo "start";'
    for first, second, error in [('number', 'box', 'nonfinite_decimal_text'), ('box', 'number', 'boxed_scalar_kind')]:
        template = '"prefix {$r->' + first + '} {$r->' + second + '}"'
        guard(work, 'php', first, setup + 'echo ' + template + ';return 8;', error, expected_stdout=b'start')


with TemporaryDirectory(prefix='interpolation-field-') as tmp:
    {'fields': fields, 'order': order}[sys.argv[1]](Path(tmp))
