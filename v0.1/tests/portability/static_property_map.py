"""Runtime property-map installation preserves identities and partial failure state."""
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
production=(APP/'05_backend/cpp/runtime_catalog.php').read_text()
start=production.index('public static function install(runtime_catalog_input $input): void')
method=production[start:production.index('\n\tpublic static function property(',start)]
source=r"""<?php
namespace scpp\compiler;
class runtime_property_definition { public function __construct(public string $identity) {} }
class runtime_cpp_spec {
 public string $header = 'header'; public bool $source_context = false; public bool $native_payloads = false;
 public function __construct(public string $identity, public string $symbol) {}
}
class runtime_function_definition {}
class Members {
 public function __construct(private Keyed_Storage $entries /** Keyed_Storage<runtime_property_definition> */) {}
 public function property_identity(string $identity): runtime_property_definition {
  Model::$trace .= 'p';
  $entries /** Keyed_Storage<runtime_property_definition> */ = $this->entries;
  if (!isset($entries[$identity])) { throw new \RuntimeException('missing'); }
  return $entries[$identity];
 }
}
class Callables {
 public function function_definition(string $identity): runtime_function_definition { return new runtime_function_definition(); }
}
class Bindings {
 public function bind_function(runtime_function_definition $target, string $symbol, string $header, bool $source_context, bool $native_payloads): void { Model::$trace .= 'F'; }
}
class Module {
 public Storage $cpp_properties /** Storage<runtime_cpp_spec> */;
 public Storage $cpp /** Storage<runtime_cpp_spec> */;
 public function __construct() {
  $this->cpp_properties = new Storage /** Storage<runtime_cpp_spec> */();
  $this->cpp = new Storage /** Storage<runtime_cpp_spec> */();
 }
}
class runtime_catalog_input {
 public Storage $modules /** Storage<Module> */;
 public function __construct() { $this->modules = new Storage /** Storage<Module> */(); }
}
class Model {
 public static \SplObjectStorage $cpp_property_bindings /** hash<runtime_cpp_spec, runtime_property_definition> */;
 public static Members $runtime_members;
 public static Callables $runtime_callables;
 public static Bindings $cpp_callable_bindings;
 public static string $trace = '';
}
class Proof {
"""+method+r"""
}
$first = new runtime_property_definition('one'); $second = new runtime_property_definition('two');
$definitions /** Keyed_Storage<runtime_property_definition> */ = new Keyed_Storage();
$definitions->add('one',$first); $definitions->add('two',$second);
Model::$runtime_members = new Members($definitions);
Model::$runtime_callables = new Callables(); Model::$cpp_callable_bindings = new Bindings();
$one = new runtime_cpp_spec('one','first'); $two = new runtime_cpp_spec('two','second');
$module = new Module(); $properties /** Storage<runtime_cpp_spec> */ = $module->cpp_properties;
$properties->append($one); $properties->append($two);
$functions /** Storage<runtime_cpp_spec> */ = $module->cpp; $functions->append(new runtime_cpp_spec('fn','fn'));
$input = new runtime_catalog_input(); $modules /** Storage<Module> */ = $input->modules; $modules->append($module);
Proof::install($input);
$installed /** hash<runtime_cpp_spec, runtime_property_definition> */ = Model::$cpp_property_bindings;
$alias = $installed[$first]; $alias->symbol = 'changed';
echo Model::$trace, ':', q_count($installed), ':', $one->symbol, '|';
$bad_module = new Module(); $bad_properties /** Storage<runtime_cpp_spec> */ = $bad_module->cpp_properties;
$bad_properties->append($one); $bad_properties->append(new runtime_cpp_spec('missing','bad'));
$bad_input = new runtime_catalog_input(); $bad_modules /** Storage<Module> */ = $bad_input->modules; $bad_modules->append($bad_module);
Model::$trace = '';
try { Proof::install($bad_input); echo 'bad'; } catch (\RuntimeException $error) { echo 'caught:'; }
$partial /** hash<runtime_cpp_spec, runtime_property_definition> */ = Model::$cpp_property_bindings;
echo Model::$trace, ':', q_count($partial), ':', isset($partial[$second]) ? 'bad' : 'reset';
"""
(out/'source/main.php').write_text(source)
expected=b'ppF:2:changed|caught:pp:1:reset'
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
