"""Compile the actual published record header and exercise its native codec adapters."""
import json
from pathlib import Path
import subprocess
from tempfile import TemporaryDirectory

from app_namespace_native import ROOT, invoke, service


def run(work):
    app, out = work / 'app', work / 'out'
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.php'}))
    (app / 'main.php').write_text('$p Point = {x:2};return $p->x;')
    (app / 'records.php').write_text('data struct Point {public readonly int $x;public int $y=7;}')
    compiler = service(app, out)
    try:
        result = invoke(compiler)
        assert 'error' not in result, result
    finally:
        compiler.stdin.close()
        compiler.wait()
    header, = [p for p in out.rglob('*.hpp') if 'struct Point {' in p.read_text()]
    exe = work / 'codec'
    subprocess.run(['clang++', '-std=c++20', '-O2', '-g0', '-I', ROOT / 'runtime/generated/public',
                    '-I', ROOT / 'runtime/src', '-include', header,
                    Path(__file__).with_suffix('.cpp'), '-o', exe], check=True)
    subprocess.run([exe], check=True)


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        run(Path(tmp))
