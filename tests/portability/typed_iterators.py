"""Prove production lazy cursor code through PHP, conversion, strict build and native execution."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--target-checkout', type=Path, required=True)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
work = args.results.resolve()
work.mkdir(parents=True, exist_ok=True)
source = work / 'source'
project = work / 'project'
source.mkdir(exist_ok=True)
fixture = ROOT / 'tests/portability/fixtures/typed_iterators'
for path in fixture.glob('*.php'):
    (source / path.name).write_text(path.read_text())
# Use real compiler cursor code, not a hand-copied proof that can drift from it.
production = (ROOT / 'compiler/my-try/03_parse/structures/iterators.php').read_text()
common = production[:production.index('/** Retain the source and visit its named fields')]
composite = production[production.index('enum function_children_stage'):]
(source / 'iterators.php').write_text(common + composite)


def run(name, command, cwd=ROOT, success=True):
    result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True,
                            text=True, timeout=180)
    (work / (name + '.stdout')).write_text(result.stdout)
    (work / (name + '.stderr')).write_text(result.stderr)
    assert (result.returncode == 0) == success, name + '\n' + result.stdout + result.stderr
    return result

helpers = ROOT / 'compiler/my-try/helpers'
bootstrap = '; '.join('require ' + json.dumps(str(path)) for path in [
    helpers / 'storage_abstract.php', helpers / 'storage.php', helpers / 'storage_cursor.php',
    source / 'iterators.php', source / 'nodes.php', source / 'main.php']) + ';'
host = run('php', ['php', '-r', bootstrap])
assert host.stdout == '0:3;1:7;2:11;3:13;\n\n13:11\nempty rejected;rewind rejected\n', host.stdout
# Convert every production node definition as a separate structural checkpoint.
# The native fixture below supplies only the owners needed by the cursor proof.
model_source = work / 'model-source'
model_source.mkdir(exist_ok=True)
for name in ['abstractions.php', 'structures_specialization.php', 'iterators.php', 'structures_kinds.php']:
    (model_source / name).write_text((ROOT / 'compiler/my-try/03_parse/structures' / name).read_text())
run('convert-model', ['php', ROOT / 'tools/php_portability/convert.php', model_source, work / 'model-converted'])
run('convert', ['php', ROOT / 'tools/php_portability/convert.php', source, project])
run('framework', ['php', ROOT / 'tools/php_portability/install_native_runtime.php', project])
(project / 'prism.json').write_text(json.dumps({
    'config_version': 1, 'project_name': 'typed_iterators', 'entrypoint': 'main.phs',
    'runtime': {'languages': {'php': {'profile': 'strict'}}, 'modules': ['compiler']},
    'build': {'cxx': 'clang++-18'}}))
run('build', ['php', args.target_checkout.resolve() / 'bin/scpp.php', 'build', '--build-runtime'], project)
native = run('native', [project / '.prism/build/main'], project)
assert native.stdout == host.stdout, native.stdout
print('PASS PHP/native: production typed cursors, holes, composite order, retention, empty and independent traversal')
