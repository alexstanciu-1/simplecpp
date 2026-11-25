"""Extracted production callable constructors/factories across PHP and native routes."""
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

declarations = APP / '03_parse/structures/structures_declarations.php'
def member(owner, name):
    text = declaration(declarations, owner)
    start = text.index(name)
    opening = text.index('{', start)
    depth, end = 1, opening + 1
    while depth:
        depth += (text[end] == '{') - (text[end] == '}')
        end += 1
    return text[start:end]
source = r"""<?php
namespace scpp\compiler;
// Focused dependency doubles; actual scope behavior is checked in callable_construction.php.
class scope {
 public bool $function_boundary = false;
 public ?scope $parent = null;
 public function mark_function(): void { $this->function_boundary = true; }
 public function set_parent(scope $parent): void { $this->parent = $parent; }
}
class function_body_node {}
class closure_capture_node {}
class callable_signature_syntax { public int $value = 0; }
trait Callable_Syntax {
 public Storage $retired_bodies /** Storage<function_body_node> */;
"""
source += member('trait Callable_Syntax', 'private function initialize_callable_state')
source += member('trait Callable_Syntax', 'public function signature_scope') + '\n}\n'
source += 'abstract class callable_declaration_node { use Callable_Syntax;\n'
source += member('abstract class callable_declaration_node', 'public function __construct') + '\n}\n'
for name in ['function_node', 'method_node']:
    source += 'final class ' + name + ' extends callable_declaration_node {\n'
    source += member('final class ' + name, 'public static function fresh_node') + '\n}\n'
source += 'final class closure_node { use Callable_Syntax; public Storage $captures /** Storage<closure_capture_node> */;\n'
for name in ['public static function fresh_node', 'public function __construct', 'public function body_scope']:
    source += member('final class closure_node', name)
source += r"""
}
$function = function_node::fresh_node();
$method = method_node::fresh_node();
$closure = closure_node::fresh_node();
$other = closure_node::fresh_node();
echo $function->signature_syntax === $method->signature_syntax ? 'bad' : 'fresh';
echo ':', $closure->signature_syntax === $other->signature_syntax ? 'bad' : 'fresh';
echo ':', $closure->signature_scope() === $other->signature_scope() ? 'bad' : 'fresh';
echo ':', $closure->body_scope() === $other->body_scope() ? 'bad' : 'fresh';
$body = $closure->body_scope();
echo ':', $body->parent === $closure->signature_scope() ? 'linked' : 'bad';
echo ':', $body->function_boundary ? 'marked' : 'bad';
$signature_scope = $closure->signature_scope();
echo ':', $signature_scope->function_boundary ? 'marked' : 'bad';
echo ':', $signature_scope->parent === null ? 'root' : 'bad';
$closure->signature_syntax->value = 7;
echo ':', $other->signature_syntax->value;
$signature = new callable_signature_syntax();
$owned_scope = new scope();
$owned_body = new scope();
$supplied = new closure_node($signature, $owned_scope, $owned_body);
echo ':', $supplied->signature_syntax === $signature ? 'input' : 'bad';
echo ':', $supplied->signature_scope() === $owned_scope ? 'input' : 'bad';
echo ':', $supplied->body_scope() === $owned_body ? 'input' : 'bad';
"""
(out / 'source/main.php').write_text(source)
expected = b'fresh:fresh:fresh:fresh:linked:marked:marked:root:0:input:input:input'
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
