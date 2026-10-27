import subprocess
import sys
import tempfile
from pathlib import Path

with tempfile.TemporaryDirectory(prefix='token-lifecycle-') as temporary:
    root = Path(temporary)
    a, b = root / 'a.phs', root / 'b.phs'
    a.write_text('return 1;')
    b.write_text('')
    process = subprocess.Popen([sys.argv[1], str(root)], stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                               stderr=subprocess.PIPE, text=True)
    def ready():
        assert process.stdout.readline().strip() == 'ready', process.stderr.read()
    def step(command):
        process.stdin.write(command)
        process.stdin.flush()
        ready()
    try:
        ready()
        step('u')
        step('n')
        step('n')
        a.write_text('return 22;')
        b.write_text('$')
        step('f')
        b.write_text('"fixed";')
        step('t')
        step('u')
        b.unlink()
        step('d')
        b.write_text('/* empty */')
        step('r')
        process.stdin.write('q')
        process.stdin.flush()
        _, error = process.communicate(timeout=20)
        assert process.returncode == 0, error
    finally:
        if process.poll() is None:
            process.kill()
            process.communicate()
