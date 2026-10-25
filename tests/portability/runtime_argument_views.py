"""Production runtime argument views preserve shared identity, null guards and insertion order."""
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
def snippet(path, start, end):
    source = (APP / path).read_text()
    offset = source.index(start)
    return source[offset:source.index(end, offset)]
registration = snippet('compiler/callables/catalog.php', '$entry /** runtime_function_definition */', '\n\t\treturn $target;')
assert (APP / 'compiler/callables/catalog.php').read_text().count(registration) == 2
owner = snippet('05_backend/cpp/generate.php', '$contract_owner /** preparation_owner */', '\n\t\t\t\t\t}')
key = snippet('05_backend/cpp/initializer_buffer.php', 'if ($key_type !== null)', '\n\t\t$entry = CPP_Runtime_Operations::reference')
member = snippet('compiler/callables/instances.php', '$member /** declaration_node */', '\n\t\t$run = new Parser_Run')
receiver = snippet('05_backend/cpp/self.php', '$inputs /** Storage<canonical_type_use> */ = $request->inputs;', '\n\t\t$entry = CPP_Runtime_Operations::reference')
assert '$facts->target = weak($definition);' in (APP / '04_analyze/prepare/semantics/async.php').read_text()
source = r"""<?php
namespace scpp\compiler;
class runtime_function_definition { public int $value = 0; }
class registered_function extends runtime_function_definition {}
class registered_generic_function extends runtime_function_definition {}
class preparation_owner { public int $value = 0; }
class type_contract_work extends preparation_owner {}
class collected_interface { public function __construct(public type_contract_work $contract_work) {} }
class canonical_type_use { public int $value = 0; }
class declaration_node { public int $value = 0; }
class callable_declaration_node extends declaration_node {}
class Origin {
 public function __construct(private callable_declaration_node $syntax) {}
 public function callable_syntax(): callable_declaration_node { return $this->syntax; }
}
interface callable_definition_i { public function name(): string; }
class compiler_function_definition implements callable_definition_i {
 public string $label = 'before';
 public function name(): string { return $this->label; }
}
class prepared_call { public callable_definition_i $target /** weak<callable_definition_i> */; }
class Request {
 public Storage $inputs /** Storage<canonical_type_use> */;
 public function __construct() { $this->inputs = new Storage /** Storage<canonical_type_use> */(); }
}
class Reference { public function __construct(public canonical_type_use $type) {} }
class Proof {
 public static function retain_input(Request $request, Reference $reference): void {
""" + receiver + r"""
 }
 public static function attach(prepared_call $facts, compiler_function_definition $definition): void {
  $facts->target = weak($definition);
 }
public static function put_function(Keyed_Storage $functions /** Keyed_Storage<runtime_function_definition> */, string $key, registered_function $target): void {
""" + registration + r"""
}
public static function put_generic(Keyed_Storage $functions /** Keyed_Storage<runtime_function_definition> */, string $key, registered_generic_function $target): void {
""" + registration + r"""
}
public static function put_owner(Storage $ordered /** Storage<preparation_owner> */, collected_interface $definition): void {
""" + owner + r"""
}
public static function put_inputs(Storage $inputs /** Storage<canonical_type_use> */, ?canonical_type_use $key_type, canonical_type_use $value_type): void {
""" + key + r"""
}
public static function put_member(Storage $members /** Storage<declaration_node> */, Origin $origin): void {
""" + member + r"""
}
}
$functions /** Keyed_Storage<runtime_function_definition> */ = new Keyed_Storage();
$first = new registered_function(); $first->value = 1;
$second = new registered_generic_function(); $second->value = 2;
Proof::put_function($functions, 'first', $first); Proof::put_generic($functions, 'second', $second);
$order = 0;
foreach ($functions as $function) { $order = $order * 10 + $function->value; }
$stored = $functions['first']; $stored->value = 7;
echo $order === 12 && $first->value === 7 ? 'functions:' : 'bad:';
$owners /** Storage<preparation_owner> */ = new Storage();
$contract = new type_contract_work();
Proof::put_owner($owners, new collected_interface($contract));
$owner_view = $owners[0]; $owner_view->value = 8;
echo $contract->value === 8 ? 'owner:' : 'bad:';
$inputs /** Storage<canonical_type_use> */ = new Storage();
$key_type = new canonical_type_use(); $key_type->value = 1;
$value_type = new canonical_type_use(); $value_type->value = 2;
Proof::put_inputs($inputs, null, $value_type); Proof::put_inputs($inputs, $key_type, $value_type);
$input_order = 0;
foreach ($inputs as $input) { $input_order = $input_order * 10 + $input->value; }
$key_view = $inputs[1]; $key_view->value = 9;
echo $input_order === 212 && $key_type->value === 9 ? 'inputs:' : 'bad:';
$members /** Storage<declaration_node> */ = new Storage();
$syntax = new callable_declaration_node();
Proof::put_member($members, new Origin($syntax));
$syntax_view = $members[0]; $syntax_view->value = 10;
echo $syntax->value === 10 ? 'member:' : 'bad:';
$request = new Request();
Proof::retain_input($request, new Reference($value_type));
$stored_inputs /** Storage<canonical_type_use> */ = $request->inputs;
$stored_input = $stored_inputs[0]; $stored_input->value = 21;
echo $value_type->value === 21 ? 'receiver:' : 'bad:';
$definition = new compiler_function_definition(); $facts = new prepared_call();
Proof::attach($facts, $definition);
$definition->label = 'weak';
$observed /** callable_definition_i */ = weakref_get($facts->target);
echo $observed->name();
"""
(out / 'source/main.php').write_text(source)
expected = b'functions:owner:inputs:member:receiver:weak'
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
