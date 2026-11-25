"""Prove enum switch lowering uses the project type-kind catalog across units."""
import argparse
import json
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
project = output / 'project'
project.mkdir()
definitions = '''namespace enum_contract;
enum operation { case add; case subtract; case other; }
final class decision {
    public operation $selected;
    public function __construct(operation $input) { $this->selected = $input; }
}
'''
consumer = '''namespace enum_contract;
function select(operation $input_operation): int {
    switch ($input_operation) {
        case operation::add:
        case operation::subtract:
            return 7;
        default: return 9;
    }
}
function from_decision(decision $input_decision): int {
    $selected /** operation */ = $input_decision->selected;
    switch ($selected) {
        case operation::add: return 1;
        case operation::subtract: return 2;
        default: return 3;
    }
}
'''
entry = '''namespace enum_contract;
echo select(operation::add), ":", select(operation::subtract), ":", select(operation::other), ":",
    from_decision(new decision(operation::add)), ":",
    from_decision(new decision(operation::subtract)), ":",
    from_decision(new decision(operation::other)), "\\n";
'''
for name, source in [('definitions', definitions), ('consumer', consumer), ('main', entry)]:
    (project / (name + '.phs')).write_text(source.replace('/** operation */', 'operation'))
    (output / (name + '.php')).write_text('<?php\n' + source)
(project / 'prism.json').write_text(json.dumps({
    'config_version': 1, 'project_name': 'external_enum_switch', 'entrypoint': 'main.phs',
    'runtime': {'languages': {'php': {'profile': 'strict'}}},
    'build': {'cxx': 'clang++-18'}}))
runner = CommandRunner(output / 'logs', project)


def prove():
    host = runner.run('php', ['php', '-r', 'foreach (array_slice($argv, 1) as $file) require $file;',
                             output / 'definitions.php', output / 'consumer.php', output / 'main.php'])
    runner.run('build', ['php', ROOT / 'bin/scpp.php', 'build', '--no-stan', '--build-runtime'], timeout=300)
    native = runner.run('native', [project / '.prism/build/main'])
    assert host == native == b'7:7:9:1:2:3\n'
    generated = '\n'.join(path.read_text() for path in (project / '.prism/generated').rglob('*.cpp'))
    assert 'unsupported-switch-case' not in generated
    assert '(input_operation).native_value()' not in generated
    assert '(selected).native_value()' not in generated


try:
    run_tasks([Task('external_enum_switch', prove)])
finally:
    runner.close()
