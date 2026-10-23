"""Production source ownership and cursor promotion across PHP and native routes."""
import argparse
import json
import shutil
import sys
from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
TOOLS = ROOT / 'tools/php_portability'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
(out / 'source').mkdir(parents=True, exist_ok=False)
def declaration(path, label):
    text = path.read_text()
    start = text.index(label)
    opening = text.index('{', start)
    depth = 1
    end = opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]

iterators = APP / '03_parse/structures/iterators.php'
source = r'''<?php
namespace scpp\compiler;
enum change_state { case added; }
class module {}
class file { public string $path = 'input.phs'; }
class token_list {}
class parsed_file {}
class ast_node { public int $value = 0; }
class return_node extends ast_node { public ?ast_node $value_node = null; }
''' + declaration(APP / '01_prepare_inputs/structures.php', 'final class source_record') + '\n'
source += declaration(iterators, 'interface child_iterator_i') + '\n'
source += declaration(iterators, 'abstract class children_iterator') + '\n'
source += declaration(iterators, 'final class pair_children_iterator') + '\n'
source += r'''
$owner = new module();
$snapshot = new file();
$record = new source_record($owner, $snapshot);
$snapshot->path = 'changed';
echo $record->path, ':', $record->file->path, ':';
$left = new ast_node(); $left->value = 3;
$right = new ast_node(); $right->value = 7;
$cursor = new pair_children_iterator($left, $right);
foreach ($cursor as $key => $item) { echo $key, ':', $item->value, ':'; }
$second = new pair_children_iterator($right, $left);
foreach ($second as $item) { echo $item->value, ':'; }
$signal = new \LogicException('message');
$other_signal = new \LogicException('message');
echo same_exception($signal, $signal) ? 'same:' : 'bad:';
echo same_exception($signal, $other_signal) ? 'bad' : 'distinct';
'''
(out / 'source/main.php').write_text(source)
expected = b'input.phs:changed:0:3:1:7:7:3:same:distinct'
with CommandRunner(out / 'logs', ROOT) as runner:
    assert runner.run('host', ['php8.5', '-r', 'require $argv[1]; require $argv[2];', TOOLS / 'runtime/bootstrap.php', out / 'source/main.php']) == expected
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-convert', ['php8.5', TOOLS / 'convert.php', out / 'source', out / route, '--target=' + route])
    for route in ['v0.1', 'v0.2']:
        runner.run(route + '-framework', ['php8.5', TOOLS / 'install_native_runtime.php', out / route])
    def check(mode):
        if mode == 'legacy':
            project = out / 'v0.1'
            runner.run('legacy-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['runtime']['modules'] = ['compiler']
            config['build']['cxx'] = 'clang++-18'
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run('legacy-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            folder = out / mode
            (folder / 'source').mkdir(parents=True)
            shutil.copyfile(out / 'v0.2/main.phs', folder / 'source/main.phs')
            # Include the full exception framework, including its identity helper.
            shutil.copyfile(out / 'v0.2/scpp_framework/exceptions.phs', folder / 'source/exceptions.phs')
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
