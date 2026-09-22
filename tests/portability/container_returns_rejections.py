"""Explicit container return annotations and scalar visibility boundaries."""
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
            'class Good { private int $id = 0; protected bool $ready = false; private string $label = ""; }',
            'class Good { public function ids(): array /** vector<int> */ { $ids /** vector<int> */ = []; return $ids; } }',
        ]:
            (src / 'case.php').write_text('<?php\n' + body)
            run('sync_imports.php', src)
            run('check.php', src)
            run('convert.php', src, output)
        for body in [
            'class Bad { public function ids(): array { return []; } }',
            'class Bad { public function ids(): array /** vector<int, int> */ { return []; } }',
            'class Bad { public function ids(): array /** hash<int, bool> */ { return []; } }',
            'class Bad { private bool $ready = 1; }',
            'class Bad { protected int $id; }',
            'interface Bad { public function ids(): array /** vector<int> */; }',
        ]:
            (src / 'case.php').write_text('<?php\n' + body)
            run('sync_imports.php', src)
            before = {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
            run('check.php', src, ok=False)
            run('convert.php', src, output, ok=False)
            assert before == {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
    print('Container return annotations and scalar field rejection checks passed.')


if __name__ == '__main__':
    main()
