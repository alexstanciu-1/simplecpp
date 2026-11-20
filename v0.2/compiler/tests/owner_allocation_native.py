"""Verify a heredoc-free owner allocation renderer against the production PHP emitter."""
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

HERE = Path(__file__).resolve().parent
EMITTER = HERE.parent / 'examples' / 'owner_allocation' / 'emitter.php.scpp'
ORACLE = HERE / 'owner_allocation_oracle.php'


def literal(value):
    if value is None:
        return 'null'
    return f'hex2bin("{value.encode().hex()}")'


def fixture(records):
    program = ''.join(records) + 'return 8;'
    rows = json.loads(subprocess.check_output(['php', ORACLE], input=program.encode()))
    assert len(rows) == len(records)
    main, expected = [], []
    for row in rows:
        abstract = str(row['abstract']).lower()
        args = ','.join(literal(row[key]) for key in ('owner', 'payload', 'parameters', 'factory'))
        main.append(f'echo owner_allocation({abstract},{args}), "\\0";')
        expected.append(row['fragment'].encode() + b'\0')
    return ''.join(main) + 'return 8;', b''.join(expected), rows


if __name__ == '__main__':
    records = [
        'class Implicit_Owner {}',
        'class Zero_Owner {public function __construct() {}}',
        'class Parameter_Owner {public function __construct(int $number, string $label) {}}',
        'abstract class Abstract_Owner {}',
    ]
    full, expected, rows = fixture(records)
    assert rows[0]['parameters'] is None and rows[0]['factory']
    assert rows[1]['parameters'] == '' and rows[1]['factory'] == ''
    assert rows[2]['parameters'] and rows[2]['factory'] == ''
    assert rows[3]['abstract'] and rows[3]['fragment'] == ''
    initial, initial_output, _ = fixture(records[:1])
    with tempfile.TemporaryDirectory(prefix='scpp-owner-allocation-') as directory:
        revisions(Path(directory), 'php', {'emitter': EMITTER.read_text(), 'main': initial},
                  [(None, None, initial_output), (None, None, initial_output), ('main', full, expected),
                   ('main', initial, initial_output)], unaffected_source='emitter.php')
