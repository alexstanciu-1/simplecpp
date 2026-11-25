"""Production scalar constant branches preserve typed facts without narrowing a nonpolymorphic base."""
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
production = (APP / '04_analyze/prepare/semantics/constants.php').read_text()
start = production.index('if ($syntax instanceof integer_literal_node) {')
end = production.index("\n\t\tthrow new ", start)
branches = production[start:end]
source = r"""<?php
namespace scpp\compiler;
class literal_type { public int $marker = 17; }
class preparation_context {}
abstract class prepared_expression {
 public literal_type $type;
 public function __construct() { $this->type = new literal_type(); }
}
abstract class expression_node { public abstract function require_preparation(): prepared_expression; }
abstract class constant_value {
 public literal_type $type;
 public abstract function text(): string;
}
class Expression_Preparation {
 public static int $calls = 0;
 public static function prepare(expression_node $syntax, preparation_context $context): prepared_expression {
  self::$calls += 1;
  return $syntax->require_preparation();
 }
}
"""
# The source facts deliberately have a nonpolymorphic base, as in production.
for kind, value_type, field, value, text in [
    ('integer', 'string', 'decimal', "'42'", '$this->decimal'),
    ('float', 'string', 'decimal', "'1.5'", '$this->decimal'),
    ('boolean', 'bool', 'value', 'true', "$this->value ? 'true' : 'false'"),
    ('string', 'string', 'value', "'hello'", '$this->value'),
]:
    default = 'false' if value_type == 'bool' else "''"
    source += f"""
class prepared_{kind}_literal extends prepared_expression {{ public {value_type} ${field} = {value}; }}
class {kind}_literal_node extends expression_node {{
 public function __construct(private prepared_{kind}_literal $prepared_facts) {{}}
 public function require_{kind}_literal_preparation(): prepared_{kind}_literal {{ return $this->prepared_facts; }}
 public function require_preparation(): prepared_expression {{ return $this->require_{kind}_literal_preparation(); }}
}}
class {kind}_constant_value extends constant_value {{
 public {value_type} ${field} = {default};
 public function text(): string {{ return {text}; }}
}}
"""
source += "class Constant_Proof { public static function evaluate(expression_node $syntax, preparation_context $context): constant_value {\n" + branches + r"""
 throw new \RuntimeException('Unexpected fixture syntax');
}}
$context = new preparation_context();
"""
for kind in ['integer', 'float', 'boolean', 'string']:
    source += f"""
${kind}_facts = new prepared_{kind}_literal();
${kind}_syntax = new {kind}_literal_node(${kind}_facts);
${kind}_value = Constant_Proof::evaluate(${kind}_syntax, $context);
echo ${kind}_value->text(), ':', ${kind}_value->type === ${kind}_facts->type ? 'same' : 'bad', ':';
"""
construction = (APP / '04_analyze/prepare/semantics/data_construction.php').read_text()
start = construction.index('$key_node = object_cast($entry->key, string_literal_node::class);')
key_branch = construction[start:construction.index('\n\t\t\tif (!isset($positions', start)]
source += "class Key_Entry { public function __construct(public expression_node $key) {} }\n"
source += "class Key_Proof { public static function read(Key_Entry $entry, preparation_context $context): string {\n" + key_branch + "\nreturn $key;\n}}\n"
source += "echo Key_Proof::read(new Key_Entry($string_syntax), $context), ':';\n"
source += 'echo Expression_Preparation::$calls;\n'
snapshot = (APP / '04_analyze/prepare/semantics/call_snapshots.php').read_text()
start = snapshot.index('private static function is_scalar_literal(')
helper = snapshot[start:snapshot.rfind('\n}')]
source += 'class Snapshot_Proof { public static function scalar(expression_node $node): bool { return self::is_scalar_literal($node); }\n' + helper + '\n}\n'
source += r"""
class unknown_expression_node extends expression_node {
 public function require_preparation(): prepared_expression { throw new \LogicException('Unused fixture facts'); }
}
echo Snapshot_Proof::scalar($integer_syntax) && Snapshot_Proof::scalar($float_syntax)
 && Snapshot_Proof::scalar($boolean_syntax) && Snapshot_Proof::scalar($string_syntax)
 && !Snapshot_Proof::scalar(new unknown_expression_node()) ? ':shape' : ':bad';
"""
(out / 'source/main.php').write_text(source)
expected = b'42:same:1.5:same:true:same:hello:same:hello:5:shape'
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
