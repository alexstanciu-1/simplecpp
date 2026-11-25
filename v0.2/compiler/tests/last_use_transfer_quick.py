"""Edit the scanner's final uses and verify both native output and transfer selection."""
from pathlib import Path
import tempfile

from collection_goal_native import revisions


if __name__ == '__main__':
    root = Path(__file__).resolve().parents[2]
    source = (root / 'specs/planning/benchmarks/tokenizer/scanner.php').read_text()
    records, scanner = source.split('function scan(', 1)
    scanner = 'function scan(' + scanner
    field_store = '$out->tokens=$items;'
    also_read = scanner.replace(field_store, field_store + 'echo count($items), $text;')
    entry = '$r=scan("if",0); echo count($r->tokens), $r->error; return 8;'
    with tempfile.TemporaryDirectory(prefix='scpp-last-use-') as tmp:
        work = Path(tmp)
        revisions(work, 'php', dict(main=entry, records=records, scanner=scanner), [
            (None, None, b'1-1'),
            ('scanner', also_read, b'1if1-1'),
            ('scanner', scanner, b'1-1'),
        ], unaffected_source='main.php')
        for revision, moved in enumerate([True, False, True]):
            units = (work / f'php-fresh-{revision}' / 'units').glob('*.cpp')
            body, = [code for unit in units if '.tokens = ' in (code := unit.read_text())]
            assert ('.tokens = std::move(var_items_' in body) == moved
            assert ('.source = std::move(var_text_' in body) == moved
            assert ('#include <utility>' in body) == ('std::move(' in body)
