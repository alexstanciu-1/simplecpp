"""Reject ambiguous nullable/list fields and promotion without element annotations."""
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'


def run(script, *args, ok=True):
    p = subprocess.run(['php', str(TOOLS / script), *map(str, args)], capture_output=True, text=True)
    assert (p.returncode == 0) == ok, (script, p.stdout, p.stderr)
    return p


def main():
    with tempfile.TemporaryDirectory(prefix='scpp-nullable-fields-') as temp:
        src = Path(temp) / 'php'; src.mkdir()
        output = Path(temp) / 'out'
        for body in [
            'class Good { public ?array $names /** vector<string> */ = null; }',
            'class Good { public function __construct(public readonly ?array $names /** vector<string> */ = null) {} }',
            'class Good { public ?Good $other = null; }',
            'const LIMIT = 0xffffffff;',
            'class Good { private array $rows /** vector< vector<int> > */ = []; }',
            'class Good { protected array $ids /** hash<int, int> */ = []; }',
            '$paths /** hash<int> */ = [];',
        ]:
            (src / 'case.php').write_text('<?php\n' + body)
            run('sync_imports.php', src)
            run('check.php', src)
            run('convert.php', src, output)
        for body in [
            'class Bad { public function __construct(public array $names /** vector<string> */ = []) {} }',
            'class Bad { public ?array $names = null; }',
            'class Bad { public ?array $names /** vector<mixed> */ = null; }',
            'class Bad { public ?array $names /** vector<string> */ = []; }',
            'class Bad { public ?Bad $other = 1; }',
            'class Bad { public function __construct(public ?array $names = null) {} }',
            'const LIMIT = 1 + 2;',
            '$x /** vector<int, int> */ = [];',
            '$x /** hash<int, int, int> */ = [];',
            '$x /** hash<int, vector<int>> */ = [];',
            '$x /** vector<vector<mixed>> */ = [];',
            '$x /** vector<vector<int> */ = [];',
            '$x /** hash<> */ = [];',
            '$x /** hash<int> extra */ = [];',
        ]:
            (src / 'case.php').write_text('<?php\n' + body)
            run('sync_imports.php', src)
            before = {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
            run('check.php', src, ok=False)
            run('convert.php', src, output, ok=False)
            assert before == {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
    print('Nullable/list fields, promoted annotations and constant rejection checks passed.')


if __name__ == '__main__':
    main()
