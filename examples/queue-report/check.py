"""Build the queue report with the project's Linux Clang preset and check its CLI."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile

app = Path(__file__).resolve().parent
root = app.parents[1]
build = root / 'build/clang'
out = build / 'examples/queue-report'


def run(*args):
    subprocess.run(args, cwd=root, check=True, env={**os.environ, 'XDEBUG_MODE': 'off'})


def check(args, status, output):
    result = subprocess.run([out / 'native/program', *args], capture_output=True)
    if (result.returncode, result.stdout, result.stderr) != (status, output, b''):
        raise SystemExit(f'queue report {args}: exit {result.returncode}, '
                         f'stdout {result.stdout!r}, stderr {result.stderr!r}')


parser = argparse.ArgumentParser(description=__doc__)
mode = parser.add_mutually_exclusive_group()
mode.add_argument('--configured', action='store_true', help='use the already built filesystem profile')
mode.add_argument('--check-disabled', action='store_true', help='verify the core-only setup diagnostic')
options = parser.parse_args()
if not (options.configured or options.check_disabled):
    run('cmake', '--preset', 'default', '-DSCPP_ENABLE_FILESYSTEM=ON')
    run('cmake', '--build', '--preset', 'default', '--target', 'scpp_filesystem')

out.mkdir(parents=True, exist_ok=True)
toolchain = out / 'toolchain.json'
toolchain.write_text(json.dumps({'cxx': 'clang++', 'flags': ['-std=c++23', '-O2', '-stdlib=libc++',
                                '-I', str(root / 'runtime/generated/public')]}))
command = ['php', root / 'compiler/main.php', 'build', app, build / 'native-layout/facts.json',
           out / 'generated', out / 'native', toolchain]
if options.check_disabled:
    result = subprocess.run(command, capture_output=True, text=True)
    if result.returncode != 1 or '-DSCPP_ENABLE_FILESYSTEM=ON' not in result.stderr:
        raise SystemExit(f'Expected filesystem setup diagnostic, got {result.returncode}: {result.stderr}')
    raise SystemExit(0)

run(*command)
check([app / 'snapshot.txt'], 0, (app / 'expected.txt').read_bytes())
check([], 2, b'usage: queue-report SNAPSHOT\n')
with tempfile.TemporaryDirectory() as directory:
    path = Path(directory) / 'new snapshot'
    check([path], 1, b'unable to read snapshot\n')
    path.touch()
    check([path], 0, b'waiting 0 running 0 failed 0 invalid 0\n')
print(f'Queue report checks passed. Executable: {out / "native/program"}')
