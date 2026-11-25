"""Portable control flow preserves preparation order and lazy selections."""
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
text = (APP / '04_analyze/prepare/semantics/compiler_calls.php').read_text()
start = text.index('$enum_type /** canonical_type_use */;')
branch = text[start:text.index('\n\t\tif (!Type_Queries::is_enum', start)]
for path in ['04_analyze/prepare/semantics/members.php', 'compiler/preparation.php']:
    assert 'for (;;)' in (APP / path).read_text()
source = r"""<?php
namespace scpp\compiler;
class canonical_type_use { public int $value = 0; }
class prepared_expression { public function __construct(public canonical_type_use $type) {} }
class expression_node { public function __construct(public canonical_type_use $type) {} }
class preparation_context { public int $trace = 0; }
enum expression_consumer { case consuming_argument; }
class Expression_Preparation {
 public static function prepare_for_consumer(expression_node $node, preparation_context $context, ?canonical_type_use $expected, expression_consumer $consumer): prepared_expression {
  $context->trace = $context->trace * 10 + 2;
  return new prepared_expression($node->type);
 }
}
class Proof {
 private static function type_argument(expression_node $node, preparation_context $context): canonical_type_use {
  $context->trace = $context->trace * 10 + 1;
  return $node->type;
 }
 public static function enum_input(bool $from_value, Storage $arguments /** Storage<expression_node> */, preparation_context $context): canonical_type_use {
  $source_index = $from_value ? 1 : 0;
""" + branch + r"""
  return $enum_type;
 }
 public static function selected(bool $yes): array /** vector<int> */ {
  $items /** vector<int> */ = [];
  if ($yes) { $items = [1, 2]; }
  else { $items = [3, 4]; }
  return $items;
 }
 public static function optional(bool $yes, canonical_type_use $type): ?canonical_type_use {
  $selected /** nullable<canonical_type_use> */ = null;
  if ($yes) { $selected = $type; }
  return $selected;
 }
 public static function retry(int $limit): int {
  $count = 0;
  for (;;) {
   try {
    $count += 1;
    if ($count < $limit) { throw new \RuntimeException('retry'); }
    return $count;
   } catch (\RuntimeException $error) { }
  }
 }
 public static function spelling(bool $first): string {
  $native /** string */ = '';
  if ($first) { $native = 'first'; } else { $native = 'second'; }
  return $native;
 }
}
$inputs /** Storage<expression_node> */ = new Storage();
$target_type = new canonical_type_use(); $target_type->value = 7;
$value_type = new canonical_type_use(); $value_type->value = 9;
$inputs->append(new expression_node($target_type));
$inputs->append(new expression_node($value_type));
$context = new preparation_context();
$result = Proof::enum_input(true, $inputs, $context);
echo $context->trace, ':', $result->value, '|';
$context->trace = 0;
$result = Proof::enum_input(false, $inputs, $context);
echo $context->trace, ':', $result->value, '|';
$left /** vector<int> */ = Proof::selected(true); $right /** vector<int> */ = Proof::selected(false);
echo $left[0], $left[1], $right[0], $right[1], '|';
$absent = Proof::optional(false, $target_type); $present = Proof::optional(true, $target_type);
echo $absent === null && $present !== null ? 'optional|' : 'bad|';
echo Proof::retry(3), '|', Proof::spelling(false);
"""
(out / 'source/main.php').write_text(source)
expected = b'12:7|2:7|1234|optional|3|second'
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('[$argv[3]]', "[$argv[3], 'strings']"))
    assert runner.run('host', ['php8.5', '-r', 'foreach(array_slice($argv,1) as $path){require $path;}',
                             TOOLS / 'runtime/bootstrap.php', APP / 'helpers/storage_abstract.php',
                             APP / 'helpers/storage.php', APP / 'helpers/keyed_storage.php', out / 'source/main.php']) == expected
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
