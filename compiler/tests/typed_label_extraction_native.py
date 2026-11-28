"""Typed label keys and incremental repair through switch and match."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def integer_switch(label):
    return f'''function number(int $subject):string {{
    $local = 3;
    switch ($subject) {{
        case ZERO: return "zero";
        case {label}: return "hit";
        default: return "miss";
    }}
}}'''


def enum_match(label):
    return f'''function mode(Mode $subject):string {{
    return match ($subject) {{
        Mode::Read => "read",
        {label} => "hit",
        default => "miss",
    }};
}}'''


def prove(work):
    sources = dict(
        labels='const int ZERO = 0; enum Mode { case Read=1; case Write=2; case Close=3; }'
               ' enum OtherMode { case Read=1; }',
        numbers=integer_switch('1'),
        modes=enum_match('Mode::Close'),
        main='echo number(0), "/", number(-1), "/", number(1), "|", '
             'mode(Mode::Read), "/", mode(Mode::Write), "/", mode(Mode::Close); return 8;',
    )
    revisions(work, 'php', sources, [
        (None, None, b'zero/miss/hit|read/miss/hit'),
        ('numbers', integer_switch('$local'), 'numbers.phs:5:14: Switch label requires a literal, named constant or enum case'),
        ('numbers', integer_switch('0'), 'numbers.phs:5:14: Duplicate switch label'),
        ('numbers', integer_switch('-1'), b'zero/hit/miss|read/miss/hit'),
        ('modes', enum_match('Mode::Read'), 'modes.phs:4:9: Duplicate match label'),
        ('modes', enum_match('OtherMode::Read'), 'modes.phs:4:9: Match label type must equal subject type'),
        ('modes', enum_match('Mode::Write'), b'zero/hit/miss|read/hit/miss'),
    ])


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        prove(Path(tmp))
