"""Prove additive assignments and single receiver/index evaluation through conversion and native execution."""
import argparse
import json
from pathlib import Path
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
parser.add_argument('--compact', action='store_true', help='Reproduce mixed-width uint32 field updates; requires target runtime support')
parser.add_argument('--target-checkout', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
source = output / 'source'
source.mkdir()
fixture = (ROOT / 'tests/portability/fixtures/additive_assignment.php').read_text()
if args.compact:
    fixture = fixture.replace('public int $offset = 7;', 'public int $offset /** uint32 */ = 7;')
(source / 'main.php').write_text(fixture)
commands = []


def run(name, command, cwd=ROOT):
    started = time.monotonic()
    result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True, timeout=180)
    (output / (name + '.stdout')).write_text(result.stdout)
    (output / (name + '.stderr')).write_text(result.stderr)
    commands.append(dict(name=name, command=list(map(str, command)), code=result.returncode,
                         seconds=round(time.monotonic() - started, 3)))
    (output / 'commands.json').write_text(json.dumps(commands, indent=2))
    if result.returncode:
        raise RuntimeError(f'{name} failed; see {output}')
    return result.stdout


run('syntax', ['php', ROOT / 'tests/portability/additive_assignment.php'])
project = output / 'phpp'
run('convert', ['php', ROOT / 'tools/php_portability/convert.php', source, project])
run('framework', ['php', ROOT / 'tools/php_portability/install_native_runtime.php', project])
cli = args.target_checkout.resolve() / 'bin/scpp.php'
run('init', ['php', cli, 'init', '--php-profile=strict'], project)
config_path = project / 'prism.json'
config = json.loads(config_path.read_text())
config['runtime']['modules'] = ['compiler']
config['build']['cxx'] = 'clang++-18'
config_path.write_text(json.dumps(config, indent=2))
run('build', ['php', cli, 'build', '--build-runtime'], project)
native = run('native', [project / '.prism/build/main'])
# Source paths are passed as arguments, not interpolated PHP literals.
host = run('php', ['php', '-r', 'require $argv[1]; require $argv[2];',
                  ROOT / 'tools/php_portability/runtime/bootstrap.php', source / 'main.php'])
if native != '-2:13:4:2' or host != '-2:13:4:2':
    raise RuntimeError('Unexpected additive values or repeated receiver/index evaluation in PHP/native')
(output / 'summary.json').write_text(json.dumps(dict(php=host, native=native, passed=True), indent=2))
print('Additive assignments: conversion, STAN, native build and PHP/native execution passed')
