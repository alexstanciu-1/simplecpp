"""Native duplicate-link translation compared with the production PHP AST contract."""
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

HERE = Path(__file__).resolve().parent
EXAMPLE = HERE.parent / 'examples' / 'duplicate_links'


def candidate(kind):
    return f'function candidate_declaration(): Declaration {{ return new {kind}("var:item"); }}'


def main():
    local, parameter, global_declaration = 'Local_Declaration', 'Parameter_Declaration', 'Global_Declaration'
    expected = {
        kind: subprocess.check_output(['php', str(HERE / 'duplicate_link_oracle.php'), kind])
        for kind in (local, parameter, global_declaration)
    }
    assert expected == {local: b'000010011', parameter: b'000010011', global_declaration: b'000010010'}, expected
    sources = {
        **{name: (EXAMPLE / f'{name}.php.scpp').read_text() for name in ('declarations', 'proof')},
        'candidate': candidate(parameter),
        'main': 'prove_duplicate_links(); return 8;',
        'idle': 'function idle(): int { return 17; }',
    }
    with tempfile.TemporaryDirectory(prefix='scpp-duplicate-links-') as directory:
        revisions(Path(directory), 'php', sources, [
            (None, None, expected[parameter]),
            (None, None, expected[parameter]),
            ('candidate', candidate(global_declaration), expected[global_declaration]),
            ('candidate', candidate('Missing_Declaration'), 'Missing_Declaration'),
            (None, None, 'Missing_Declaration'),
            ('candidate', None, 'candidate_declaration'),
            ('candidate', candidate(local), expected[local]),
            ('candidate', candidate(parameter), expected[parameter]),
        ], unaffected_source='idle.php')


if __name__ == '__main__':
    main()
