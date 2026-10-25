"""Production nullable enum boundaries preserve absence and lazy evaluation across routes."""
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
model = (APP / '03_parse/types/model.php').read_text()
start = model.index('if ($by_value) {', model.index('public function use('))
use_check = model[start:model.index('\n\t\tif ($by_value && ($definition->origin()', start)]
start = model.index('$registered_storage = $this->runtime_storage($definition);', model.index('public function is_value_type('))
value_check = model[start:model.index('\n\t\tif ($definition instanceof', start)]
start = model.index('if (!$type_use->by_value()) {', model.index('public function has_registered_capability('))
capability_check = model[start:model.index('\n\t\tif (!$definition->declares_capability', start)]
indexing = (APP / 'compiler/indexing/contracts.php').read_text()
start = indexing.index('if ($store_operation !== null) {')
store_check = indexing[start:indexing.index('\n\t}\n', start)]
source = r"""<?php
namespace scpp\compiler;
enum record_storage_kind { case inline_value; case shared; }
enum generic_contract { case copyable_value; case value_storable; case other; }
enum operator_operation { case index_store; case index_checked_read; }
class Definition {}
class Type_Use {
 public function __construct(private bool $value) {}
 public function by_value(): bool { return $this->value; }
}
class Proof {
 public int $reads = 0;
 public ?record_storage_kind $storage = null;
 private function runtime_storage(Definition $definition): ?record_storage_kind {
  $this->reads += 1; return $this->storage;
 }
 public function use_check(Definition $definition, bool $by_value): void {
""" + use_check + r"""
 }
 public function value_check(Definition $definition): bool {
""" + value_check + r"""
  return false;
 }
 public function capability_check(Definition $definition, Type_Use $type_use, generic_contract $capability): bool {
""" + capability_check + r"""
  return false;
 }
 public static function store_check(?operator_operation $store_operation): void {
""" + store_check + r"""
 }
}
$proof = new Proof(); $definition = new Definition();
$by_value = new Type_Use(true); $by_handle = new Type_Use(false);
$proof->use_check($definition, false);
$absent = $proof->capability_check($definition, $by_value, generic_contract::copyable_value);
echo $proof->reads === 0 && !$absent ? 'lazy:' : 'bad:';
$proof->use_check($definition, true);
echo !$proof->value_check($definition) && !$proof->capability_check($definition, $by_handle, generic_contract::copyable_value) ? 'absent:' : 'bad:';
$proof->storage = record_storage_kind::inline_value;
$proof->use_check($definition, true);
echo $proof->value_check($definition) && !$proof->capability_check($definition, $by_handle, generic_contract::copyable_value) ? 'inline:' : 'bad:';
$proof->storage = record_storage_kind::shared;
try { $proof->use_check($definition, true); echo 'bad:'; }
catch (\InvalidArgumentException $error) { echo 'rejected:'; }
echo !$proof->value_check($definition) && $proof->capability_check($definition, $by_handle, generic_contract::copyable_value)
 && $proof->capability_check($definition, $by_handle, generic_contract::value_storable)
 && !$proof->capability_check($definition, $by_handle, generic_contract::other) ? 'shared:' : 'bad:';
Proof::store_check(null); Proof::store_check(operator_operation::index_store);
try { Proof::store_check(operator_operation::index_checked_read); echo 'bad'; }
catch (\InvalidArgumentException $store_error) { echo 'store-checked'; }
"""
(out / 'source/main.php').write_text(source)
expected = b'lazy:absent:inline:rejected:shared:store-checked'
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
