"""Compare the translated global declaration parser with production PHP syntax."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

HERE = Path(__file__).resolve().parent
EXAMPLE = HERE.parent / 'examples' / 'global_declaration' / 'parser.php.scpp'


def source(flavor, annotation):
    explicit = annotation or ''
    if flavor == 'cpp':
        return f'global auto offset = -8; global {annotation or "auto"} width = +16;'
    if flavor == 'php':
        return f'global $offset = -8; global $width {" " + explicit if explicit else ""}= +16;'
    return f'global let offset = -8; global let width{": " + explicit if explicit else ""} = +16;'


def fixture(annotation):
    main, expected = [], []
    for flavor in ('cpp', 'php', 'js'):
        data = json.loads(subprocess.check_output(
            ['php', HERE / 'global_declaration_oracle.php', flavor],
            input=source(flavor, annotation).encode()))
        assert len(data['globals']) == 2 and data['globals'][0][3] is None
        second_type = data['globals'][1][3]
        assert (second_type[0] if second_type else None) == annotation
        tokens = ''.join(
            f'$token{i} Global_Token(Global_Token_Kind::{kind.title()},{text!r});$tokens[]=$token{i};'
            for i, (kind, text) in enumerate(data['tokens']))
        main.append('{$tokens vector<Global_Token> = [];' + tokens
                    + f'print_globals($tokens,Global_Flavor::{flavor.title()});}}')
        for name, start, end, written, literal, literal_start, literal_end in data['globals']:
            spelling = f'{written[0]}@{written[1]}' if written else 'absent'
            expected.append(f'{name}|{start}|{end}|{spelling}|{literal}|{literal_start}|{literal_end}\n')
    return ''.join(main) + 'return 8;', ''.join(expected).encode()


if __name__ == '__main__':
    inferred, inferred_output = fixture(None)
    explicit, explicit_output = fixture('int64')
    unresolved, unresolved_output = fixture('FutureWidth')
    with tempfile.TemporaryDirectory(prefix='scpp-global-declaration-') as directory:
        revisions(Path(directory), 'php',
                  {'parser': EXAMPLE.read_text(), 'main': inferred},
                  [(None, None, inferred_output), (None, None, inferred_output),
                   ('main', explicit, explicit_output),
                   ('main', unresolved, unresolved_output),
                   ('main', inferred, inferred_output)],
                  unaffected_source='parser.php')
