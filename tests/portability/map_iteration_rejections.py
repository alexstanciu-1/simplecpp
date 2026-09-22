"""By-value foreach and keyed isset reject unsupported binding/probe forms."""
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
            '$xs /** vector<int> */ = []; foreach ($xs as $x) { continue; }',
            '$xs /** hash<int, int> */ = []; foreach ($xs as $k => $v) { break; }',
            '$xs /** hash<int> */ = []; echo isset($xs["key"]);',
        ]:
            (src / 'case.php').write_text('<?php\n' + body)
            run('sync_imports.php', src)
            run('check.php', src)
            run('convert.php', src, output)
        for body in [
            '$xs /** vector<int> */ = []; foreach ($xs as &$x) {}',
            '$xs /** vector<int> */ = []; foreach ($xs as $k => &$x) {}',
            '$xs /** vector<int> */ = []; foreach ($xs as [$x]) {}',
            '$xs /** vector<int> */ = []; foreach ($xs as $x): endforeach;',
            '$xs /** vector<int> */ = []; foreach ($xs as $x) { break 2; }',
            '$xs /** hash<int> */ = []; echo isset($xs["a"], $xs["b"]);',
            '$xs /** hash<int> */ = []; echo isset($xs[strlen("a")]);',
            '$xs /** hash<int> */ = []; echo isset($xs[$k++]);',
            '$xs /** hash<int> */ = []; echo isset($xs);',
        ]:
            (src / 'case.php').write_text('<?php\n' + body)
            run('sync_imports.php', src)
            before = {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
            run('check.php', src, ok=False)
            run('convert.php', src, output, ok=False)
            assert before == {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
    print('Map/iteration syntax and rejection checks passed.')


if __name__ == '__main__':
    main()
