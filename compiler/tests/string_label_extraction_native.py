"""Exercise string label admission and byte-exact dispatch through resident compilation."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    def dispatch(second_label):
        return f'''function classify(string $subject):string {{
    $local = "other";
    switch ($subject) {{
        case EMPTY_LABEL: return "empty";
        case {second_label}: return "second";
        default: return "other";
    }}
}}'''

    main = r'''echo classify(""), "|", classify("\0"), "|", classify("x"); return 8;'''
    sources = dict(labels='const string EMPTY_LABEL = "";', dispatch=dispatch('"x"'), main=main)
    duplicate = 'dispatch.phs:5:14: Duplicate switch label'
    admission = 'dispatch.phs:5:14: Switch label requires a literal, named constant or enum case'
    revisions(work, 'php', sources, [
        (None, None, b'empty|other|second'),
        ('dispatch', dispatch('$local'), admission),
        ('dispatch', dispatch('""'), duplicate),
        ('dispatch', dispatch(r'"\0"'), b'empty|second|other'),
    ])


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        prove(Path(tmp))
