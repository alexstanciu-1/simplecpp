"""Prologue placement, scoped policy and declaration-boundary proofs."""
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[3]
TOOLS = ROOT / 'tools/php_portability'
with tempfile.TemporaryDirectory(prefix='scpp-prologues-') as temporary:
    base = Path(temporary)
    source = base / 'source'
    source.mkdir()
    path = source / 'model.php'
    sync = ['php', str(TOOLS / 'sync_imports.php'), str(source)]
    convert = ['php', str(TOOLS / 'convert.php'), str(source), str(base / 'native')]
    def run(cmd, ok=True):
        p = subprocess.run(cmd, text=True, capture_output=True)
        assert (p.returncode == 0) == ok, (p.stdout, p.stderr)
        return p
    for newline in ['\n', '\r\n']:
        text = '<?php\n/* prologue */\ndeclare (strict_types = 1);\n/** scope */\nnamespace proof\\scope;\nclass Values { public bool $flag = false; public int $number = 7; public string $text = "hi"; }\n$out /** int */ = 0;\necho take_false($out, 7) ? "yes" : "no";\n'
        path.write_bytes(text.replace('\n', newline).encode())
        run(sync)
        saved = path.read_bytes()
        run(sync)
        assert saved == path.read_bytes()
        run(sync + ['--check'])
        assert saved.index(b'namespace proof\\scope;') < saved.index(b'// <scpp-imports>')
        php = run(['php', '-d', 'auto_prepend_file=' + str(TOOLS / 'runtime/bootstrap.php'), str(path)])
        assert php.stdout == 'yes'
        run(convert)
        output = (base / 'native/model.phs').read_text()
        assert 'namespace proof\\scope;' in output and 'declare' not in output and 'take($out, 7)' in output
        # Exact input line attribution survives strict-prologue/import stripping.
        path.write_bytes(saved + b'unknown();\n')
        line = saved.count(b'\n') + 1
        error = run(convert, ok=False).stderr
        assert f'model.php:{line}:' in error, error
    for text in [
        '<?php namespace Upper; class A {}',
        '<?php namespace lower { class A {} }',
        '<?php namespace one; class A {} namespace two; class B {}',
        '<?php declare(ticks=1); echo "x";',
        '<?php echo "x"; namespace late;',
        '<?php namespace one; use function scpp\\take_false as other;',
    ]:
        path.write_text(text)
        original = path.read_bytes()
        run(sync, ok=False)
        assert path.read_bytes() == original
print('Namespace/strict prologues, LF/CRLF, scalar fields, scoped calls and source diagnostics passed.')
