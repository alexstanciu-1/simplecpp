"""Retained class receivers preserve mutations through casts, calls and optional storage."""
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
production = (APP / '03_parse/parser.php').read_text()
start = production.index('if ($constant instanceof member_constant_node) {')
end = production.index('\n\t\tif ($previous === null)', start)
mutation = production[start:end]
source = r"""<?php
namespace scpp\compiler;
abstract class constant_declaration_node { abstract public function kind(): int; }
class member_constant_node extends constant_declaration_node {
 public array $modifiers /** vector<int> */ = [];
 public function kind(): int { return 1; }
}
class Body { public bool $syntax_changed = true; }
class Holder {
 public ?Body $prepared = null;
 public function require_body(): Body { return $this->prepared; }
}
class Mutations { public static function apply_modifiers(constant_declaration_node $constant, array $modifiers /** vector<int> */): void {
""" + mutation + r"""
}}
$member = new member_constant_node();
$modifiers /** vector<int> */ = [3, 7];
Mutations::apply_modifiers($member, $modifiers);
echo $member->modifiers[1];
$body = new Body();
$holder = new Holder();
$holder->prepared = $body;
$prepared_body = $holder->require_body();
$prepared_body->syntax_changed = false;
echo ':', $body->syntax_changed ? 'bad' : 'changed';
$prepared_source /** Body */ = $holder->prepared;
$prepared_source->syntax_changed = true;
echo ':', $body->syntax_changed ? 'restored' : 'bad';
echo ':', $prepared_source === $body ? 'same' : 'bad';
"""
(out / 'source/main.php').write_text(source)
expected = b'7:changed:restored:same'
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', out / 'source/main.php']) == expected
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
            runner.run(mode + '-publish', ['php8.5', publisher, folder,
                                           ROOT / 'runtime/metadata/modules', 'compiler', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        assert runner.run(mode + '-run', [executable]) == expected
    results = run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
