"""Prove file-local class imports across inheritance, declarations and expression use sites."""
import argparse
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
project = out / 'project'
project.mkdir()
sources = {
    'models': '''namespace data;
class item { public int $value = 3; public static function tag(): int { return 5; } }
abstract class provider { abstract public function get(): item; }
namespace other;
class item { public int $value = 7; }
''',
    'left': '''namespace client;
use data as Models;
use data\\item as Item;
final class left_view extends Models\\provider {
    public Item $stored;
    public function __construct() { $this->stored = new Item(); }
    public function get(): Item { return $this->stored; }
    public function replace(Item $value): void { $this->stored = $value; }
    public function tag(): int { return Item::tag(); }
}
''',
    'right': '''namespace client;
use other\\item as Item;
final class right_view {
    public Item $stored;
    public function __construct() { $this->stored = new Item(); }
    public function get(): Item { return $this->stored; }
}
''',
    'main': '''namespace client;
use data\\item;
function read_provider(\\data\\provider $view): int { return $view->get()->value; }
$left = new left_view();
$right = new right_view();
$left->replace(new item());
echo read_provider($left), ":", $right->get()->value, ":", $left->tag(), "\\n";
'''
}
for name, text in sources.items():
    (out / (name + '.php')).write_text('<?php\n' + text)
    dependencies = [] if name == 'models' else (['left', 'right'] if name == 'main' else ['models'])
    prologue = ''.join(f'require_once "{dependency}.php";\n' for dependency in dependencies)
    (project / (name + '.phs')).write_text(prologue + text)
(project / 'prism.json').write_text(json.dumps({
    'config_version': 1, 'project_name': 'import_contract', 'entrypoint': 'main.phs',
    'runtime': {'languages': {'php': {'profile': 'strict'}}}, 'build': {'cxx': 'clang++-18'}}))


def run(name, args):
    result = subprocess.run(list(map(str, args)), cwd=project, capture_output=True, text=True, timeout=180)
    (out / (name + '.stdout')).write_text(result.stdout)
    (out / (name + '.stderr')).write_text(result.stderr)
    assert result.returncode == 0, f'{name}: see {out}'
    return result.stdout


host = run('php', ['php', '-r', 'foreach (array_slice($argv, 1) as $path) require $path;',
                  *[out / (name + '.php') for name in sources]])
assert host == '3:7:5\n'
run('build', ['php', ROOT / 'bin/scpp.php', 'build', '--no-stan', '--build-runtime'])
assert run('native', [project / '.prism/build/main']) == host
for path in (project / '.prism/generated').glob('*.[ch]pp'):
    text = path.read_text()
    assert 'class Item;' not in text and 'using Item =' not in text and 'using Models =' not in text
print('PASS: file-local aliases, namespace-prefix imports, defaults, inheritance, properties, parameters, returns, construction and static calls')
