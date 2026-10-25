"""Production optional runtime queries and observation spelling across routes."""
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
queries=(APP/'compiler/types/queries.php').read_text()
start=queries.index('$storage = record_storage_kind::inline_value;',queries.index('public static function is_nominal_handle'))
nominal=queries[start:queries.index('\n\t\tif (self::is_source_class',start)]
operations=(APP/'05_backend/cpp/runtime_operations.php').read_text()
start=operations.index('$observation /** ownership_observation_kind */',operations.index('private static function native_operation'))
observation=operations[start:operations.index('\n\t\t}',start)]
patterns=(APP/'compiler/runtime_catalog/patterns.php').read_text()
start=patterns.index('$default_pattern /** runtime_type_pattern */')
default_pattern=patterns[start:patterns.index('\n\t\t\t$closed[]',start)]
source=r"""<?php
namespace scpp\compiler;
enum record_storage_kind { case inline_value; case shared; }
enum ownership_observation_kind { case derive_weak; case lock_weak; }
class Definition {}
class canonical_type_use { public int $value = 7; }
class runtime_type_pattern { public int $value = 0; }
class runtime_concrete_pattern extends runtime_type_pattern {
 public function __construct(canonical_type_use $type) { $this->value = $type->value; }
}
class Registry {
 public ?record_storage_kind $storage = null;
 public function runtime_storage(Definition $definition): ?record_storage_kind { return $this->storage; }
}
class Catalog {
 public function __construct(private Registry $registry_value) {}
 public function registry(): Registry { return $this->registry_value; }
}
class Model { public static Catalog $type_catalog; }
class Request { public ?ownership_observation_kind $ownership_observation = null; }
class Proof {
 public static function default_pattern(Storage $arguments /** Storage<runtime_type_pattern> */, canonical_type_use $default_type): void {
"""+default_pattern+r"""
 }
 public static function nominal(Definition $definition): bool {
"""+nominal+r"""
  return false;
 }
 public static function observation(Request $request, array $arguments /** vector<string> */): string {
"""+observation+r"""
 }
}
$registry = new Registry(); Model::$type_catalog = new Catalog($registry);
$definition = new Definition();
echo !Proof::nominal($definition) ? 'absent:' : 'bad:';
$registry->storage = record_storage_kind::inline_value;
echo !Proof::nominal($definition) ? 'inline:' : 'bad:';
$registry->storage = record_storage_kind::shared;
echo Proof::nominal($definition) ? 'shared:' : 'bad:';
$request = new Request(); $arguments /** vector<string> */ = ['x'];
$request->ownership_observation = ownership_observation_kind::derive_weak;
echo Proof::observation($request,$arguments), ':';
$request->ownership_observation = ownership_observation_kind::lock_weak;
echo Proof::observation($request,$arguments), ':';
$patterns /** Storage<runtime_type_pattern> */ = new Storage();
Proof::default_pattern($patterns, new canonical_type_use());
$pattern = $patterns[0];
echo $pattern->value;
"""
(out/'source/main.php').write_text(source)
expected=b'absent:inline:shared:::scpp::weak(x):(x).lock():7'
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
