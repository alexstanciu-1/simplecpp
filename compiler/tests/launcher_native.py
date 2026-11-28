"""Link the translated launcher to a real process boundary and compare it with PHP."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
EXAMPLE = ROOT / 'compiler/examples/launcher'
PHP, CXX, LINKER, RUNTIME = sys.argv[1:]
CLI = ROOT / 'compiler/main.php'


def result(*command):
    run = subprocess.run(command, capture_output=True, timeout=15)
    return run.stdout, run.stderr, run.returncode


with tempfile.TemporaryDirectory(prefix='scpp-launcher-') as directory:
    work = Path(directory)
    app, bundle = work / 'launcher', work / 'bundle'
    app.mkdir()
    for name in ('cli.phs', 'main.phs'):
        shutil.copyfile(EXAMPLE / name, app / name)
    (app / 'module.json').write_text('{"entry":"main.phs","general_api":true}')
    # Publish and type-check the stderr host signature. Link their native implementation below.
    (app / 'host.phs').write_text('''
function host_write_error(string $message): void { throw Exception::make("Host adapter required"); }
''')
    facts = Path(RUNTIME).parent / 'native-layout/facts.json'
    subprocess.run([PHP, CLI, 'project', app, facts, bundle], check=True)
    manifest = json.loads((bundle / 'manifest.json').read_text())
    host_unit = next(unit for source, unit in manifest['sources'].items() if source.endswith('/host.phs'))
    units = [bundle / unit for unit in manifest['translation_units'] if unit != host_unit]
    # Include the compiler-published prototypes in the adapter TU so signature drift fails the build.
    header = (bundle / host_unit).with_suffix('.hpp')
    host = work / 'adapter.o'
    flags = [CXX, '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror',
             '-I', ROOT / 'runtime/generated/public']
    subprocess.run([*flags, '-include', header, '-c', EXAMPLE / 'host.cpp', '-o', host], check=True)
    executable = work / 'compiler'
    subprocess.run([*flags, f'--ld-path={LINKER}', *units, host, RUNTIME, '-o', executable], check=True)

    help_out, help_err, help_status = result(PHP, CLI, '--help')
    assert help_out and (help_err, help_status) == (b'', 0)
    assert result(PHP, CLI, 'frontend') == (b'', help_out, 2)
    assert result(executable, '--help') == (help_out, b'', 0)
    assert result(executable, 'frontend') == (b'', help_out, 2)
