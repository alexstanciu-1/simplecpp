"""Compare resident and fresh compilation across transitive constant edits and cycles."""
from pathlib import Path
from tempfile import TemporaryDirectory

from collection_goal_native import revisions


def prove(work):
    base = 'namespace Units { const int KIB = 1024; const int OTHER = 2; }'
    sizes = r'namespace Sizes { use const Units\KIB as UNIT; const int PAGE = 4 * UNIT; }'
    total = r'namespace Totals { use const Sizes\PAGE; const int TOTAL = PAGE + PAGE; }'
    sources = {
        'main': r'use const Totals\TOTAL; echo TOTAL; return 8;',
        'sizes': sizes,
        'units': base,
        'totals': total,
    }
    cycle = r'namespace Units { use const Totals\TOTAL; const int KIB = TOTAL; const int OTHER = 2; }'
    diagnostic = r'Cyclic integer constant dependency: const:Sizes\PAGE -> const:Units\KIB -> const:Totals\TOTAL -> const:Sizes\PAGE'
    revisions(work, 'php', sources, [
        (None, None, b'8192'),
        (None, None, b'8192'),
        ('units', base.replace('1024', '1025'), b'8200'),
        ('units', cycle, diagnostic),
        (None, None, diagnostic),
        ('units', base, b'8192'),
        ('sizes', sizes.replace('KIB', 'OTHER'), b'16'),
        ('units', base.replace('1024', '1025'), b'16'),
        ('units', base.replace('OTHER = 2', 'OTHER = 3'), b'24'),
        ('units', base.replace('const int OTHER = 2;', ''), 'Unknown namespace integer constant'),
        ('units', base, b'16'),
    ])


if __name__ == '__main__':
    with TemporaryDirectory(prefix='scpp-computed-constants-') as directory:
        prove(Path(directory))
