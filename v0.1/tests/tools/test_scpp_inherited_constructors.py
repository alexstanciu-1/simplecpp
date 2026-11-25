"""Prove public constructor inheritance across abstract classes and source units."""
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
base = '''namespace ctor_contract;
abstract class root_record {
    public int $value;
    public function __construct(int $value = 7) { $this->value = $value; }
    abstract public function tag(): int;
}
abstract class middle_record extends root_record {}
'''
leaf = '''namespace ctor_contract;
final class leaf_record extends middle_record {
    public int $extra = 9;
    public function tag(): int { return 1; }
}
final class explicit_record extends middle_record {
    public function __construct(int $value) { parent::__construct($value); }
    public function tag(): int { return 2; }
}
class protected_record { protected function __construct(int $value) {} }
class protected_child extends protected_record {}
class private_record { private function __construct(int $value) {} }
class private_child extends private_record {}
'''
entry = '''namespace ctor_contract;
$a = new leaf_record();
$b = new leaf_record(4);
$c = new explicit_record(6);
echo $a->value, ":", $b->value, ":", $b->extra, ":", $c->value, "\\n";
'''
for name, text in [('nodes', base + leaf.replace('namespace ctor_contract;', '')), ('main', entry)]:
    (project / (name + '.phs')).write_text(text)
    (output / (name + '.php')).write_text('<?php\n' + text)
(project / 'prism.json').write_text(json.dumps({
    'config_version': 1, 'project_name': 'constructor_contract', 'entrypoint': 'main.phs',
    'runtime': {'languages': {'php': {'profile': 'strict'}}},
    'build': {'cxx': 'clang++-18'}}))


def run(name, command, success=True):
    result = subprocess.run(list(map(str, command)), cwd=project, capture_output=True,
                            text=True, timeout=180)
    (output / (name + '.stdout')).write_text(result.stdout)
    (output / (name + '.stderr')).write_text(result.stderr)
    assert (result.returncode == 0) == success, f'{name}: see {output}'
    return result.stdout


cli = ROOT / 'bin/scpp.php'
host = run('php', ['php', '-r', 'foreach (array_slice($argv, 1) as $file) require $file;',
                   output / 'nodes.php', output / 'main.php'])
# Isolate lowering from STAN's separate constructor-initialization analysis.
run('build', ['php', cli, 'build', '--no-stan', '--build-runtime'])
assert run('native', [project / '.prism/build/main']) == host == '7:4:9:6\n'
headers = '\n'.join(p.read_text() for p in (project / '.prism/generated').rglob('*.hpp'))
assert 'using root_record::root_record;' in headers
assert 'using middle_record::middle_record;' in headers
assert 'using protected_record::protected_record;' not in headers
assert 'using private_record::private_record;' not in headers
# An explicit constructor must not silently acquire the parent's default argument.
(project / 'main.phs').write_text(entry + '\n$invalid = new explicit_record();\n')
run('explicit_rejection', ['php', cli, 'build', '--no-stan'], success=False)
print('PASS: inherited public constructors, abstract chain, cross-file use, defaults, fields, explicit constructor and non-public exclusion')
