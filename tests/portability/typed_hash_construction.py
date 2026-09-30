"""Prove explicitly typed empty hashes survive conversion and direct property assignment."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
source = output / 'source'
source.mkdir()
shutil.copyfile(ROOT / 'tests/portability/fixtures/typed_hash_construction.php', source / 'main.php')
project = output / 'project'


def run(name, command, cwd=ROOT, success=True):
    result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True, timeout=180)
    (output / (name + '.stdout')).write_text(result.stdout)
    (output / (name + '.stderr')).write_text(result.stderr)
    assert (result.returncode == 0) == success, f'{name}: see {output}'
    return result.stdout


host = run('php', ['php', source / 'main.php'])
run('conversion', ['php', ROOT / 'tools/php_portability/convert.php', source, project])
assert 'new hash<int, shared<key_record>>()' in (project / 'main.phs').read_text()
(project / 'prism.json').write_text(json.dumps({
    'config_version': 1, 'project_name': 'typed_hash_construction', 'entrypoint': 'main.phs',
    'runtime': {'languages': {'php': {'profile': 'strict'}}},
    'build': {'cxx': 'clang++-18'}}))
cli = ROOT / 'bin/scpp.php'
# Isolate this local conversion/lowering proof from independent STAN gaps.
run('build', ['php', cli, 'build', '--no-stan', '--build-runtime'], project)
assert run('native', [project / '.prism/build/main'], project) == host == '7:empty:9\n5:ok\n8:empty\n'
cpp = (project / '.prism/generated/main.cpp').read_text()
assert 'this->items = hash_t<int_t<>, shared_p<hash_construction::key_record>>{};' in cpp
assert 'this->indices = vector_t<int_t<>>{};' in cpp
assert 'this->labels = hash_t<string_t>{};' in cpp
assert 'shared_table_' not in cpp
(project / 'main.phs').write_text('''$invalid = new hash<int>(1);''')
run('arguments_rejected', ['php', cli, 'build', '--no-stan'], project, success=False)
assert 'Explicit container construction accepts no arguments' in (output / 'arguments_rejected.stderr').read_text() + (output / 'arguments_rejected.stdout').read_text()
print('PASS: annotated empty vectors/scalar-key hashes, typed hash property construction/reset, return, identity-key access, native parity and argument rejection')
