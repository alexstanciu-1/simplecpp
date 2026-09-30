"""Prove cross-file accessor bridges and incremental ancestor-signature invalidation."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
project = output / 'project'
project.mkdir()
sources = {
    'facts': '''namespace covariance_data;
class facts { public int $value = 1; }
class middle_facts extends facts {}
class leaf_facts extends middle_facts {}
interface same_contract { public function same(): facts; }
class same_base implements same_contract { public function same(): facts { return new facts(); } }
''',
    'base': '''namespace covariance_base;
interface provider { public function item(): \\covariance_data\\facts; }
class base_node implements provider {
    public function item(): \\covariance_data\\facts { return new \\covariance_data\\middle_facts(); }
}
''',
    'middle': '''namespace covariance_middle;
abstract class middle_node extends \\covariance_base\\base_node {}
''',
    'leaf': '''namespace covariance_leaf;
final class leaf_node extends \\covariance_middle\\middle_node {
    public \\covariance_data\\leaf_facts $stored;
    public function __construct() { $this->stored = new \\covariance_data\\leaf_facts(); $this->stored->value = 8; }
    public function item(): \\covariance_data\\leaf_facts { return $this->stored; }
    public function parent_value(): int { return parent::item()->value; }
}
''',
    'same': '''namespace covariance_data;
final class same_leaf extends same_base { public function same(): facts { return new facts(); } }
''',
    'main': '''namespace covariance_entry;
function through_base(\\covariance_base\\base_node $source): \\covariance_data\\facts { return $source->item(); }
function through_interface(\\covariance_base\\provider $source): \\covariance_data\\facts { return $source->item(); }
function through_direct(\\covariance_leaf\\leaf_node $source): \\covariance_data\\facts { return $source->item(); }
$source = new \\covariance_leaf\\leaf_node();
$first = through_base($source);
$second = through_interface($source);
$direct = through_direct($source);
if (($first !== $direct) || ($second !== $direct)) { echo "Lost shared identity\\n"; }
echo $first->value, ":", $second->value, ":", $source->parent_value(), "\\n";
''',
}
includes = {'base': 'facts', 'middle': 'base', 'leaf': 'middle', 'same': 'facts', 'main': 'leaf'}
for name, source in sources.items():
    prologue = f'require_once "{includes[name]}.php";\n' if name in includes else ''
    (project / (name + '.phs')).write_text(prologue + source)
    (output / (name + '.php')).write_text('<?php\n' + source)
(project / 'prism.json').write_text(json.dumps({
    'config_version': 1, 'project_name': 'covariance_contract', 'entrypoint': 'main.phs',
    'runtime': {'languages': {'php': {'profile': 'strict'}}}, 'build': {'cxx': 'clang++-18'}}))


def run(name, command, success=True):
    result = subprocess.run(list(map(str, command)), cwd=project, capture_output=True,
                            text=True, timeout=180)
    (output / (name + '.stdout')).write_text(result.stdout)
    (output / (name + '.stderr')).write_text(result.stderr)
    assert (result.returncode == 0) == success, f'{name}: see {output}'
    return result.stdout


cli = ROOT / 'bin/scpp.php'
host = run('php', ['php', '-r', 'foreach (array_slice($argv, 1) as $path) require $path;',
                  *[output / (name + '.php') for name in sources]])
assert host == '8:8:1\n'
run('build', ['php', cli, 'build', '--no-stan', '--build-runtime'])
assert run('native', [project / '.prism/build/main']) == host
leaf_header = project / '.prism/generated/leaf.hpp'
first_header = leaf_header.read_text()
assert 'std::type_identity<shared_p<covariance_data::facts>>' in first_header
# Same-return overrides must share a slot even when their source spellings differ.
same_header = (project / '.prism/generated/same.hpp').read_text()
assert same_header.count('__scpp_return_same(std::type_identity<shared_p<covariance_data::facts>>);') == 1
run('noop', ['php', cli, 'build', '--no-stan'])
assert 'Transpiled PHP files: 0' in (output / 'noop.stdout').read_text()
# Only the ancestor file changes: the unchanged leaf needs a new middle-return bridge.
(project / 'base.phs').write_text('require_once "facts.php";\n' + sources['base'].replace('public function item(): \\covariance_data\\facts {',
                                                        'public function item(): \\covariance_data\\middle_facts {'))
run('ancestor_edit', ['php', cli, 'build', '--no-stan'])
assert 'std::type_identity<shared_p<covariance_data::middle_facts>>' in leaf_header.read_text()
assert leaf_header.read_text() != first_header
assert run('native_after_edit', [project / '.prism/build/main']) == host
# Native C++ must still reject an unrelated return; bridges cannot manufacture downcasts.
(project / 'leaf.phs').write_text('require_once "middle.php";\nrequire_once "unrelated.php";\n' + sources['leaf'].replace('\\covariance_data\\leaf_facts', '\\unrelated_data\\other_facts'))
(project / 'unrelated.phs').write_text('namespace unrelated_data; class other_facts { public int $value = 8; }')
run('unrelated_rejected', ['php', cli, 'build', '--no-stan'], success=False)
print('PASS: split-file class/interface dispatch, ancestor chain, qualified names, identity, no-op, ancestor invalidation and unrelated-return rejection')
