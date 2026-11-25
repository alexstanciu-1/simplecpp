<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Host integration fixture: real preparation, binding selection, engine and publisher.
$source = $argv[1];
$published = $argv[2];
$revision = (int)$argv[3];
$alignment = (int)($argv[4] ?? 8);
$broken = (int)($argv[5] ?? 0);
$metadata = new runtime_catalog_input();
foreach (Model::require_runtime_metadata()->modules as $module) { $metadata->modules->add($module->name, $module); }
$metadata->modules->add('type_proof', Runtime_Catalog_Reader::decode(file_get_contents(dirname(__DIR__) . '/runtime_types.json'), 'type_proof.json'));
Compiler_Lifecycle::configure_runtime($metadata);
if (!is_dir($source)) { mkdir($source); }
file_put_contents($source . '/main.phs', '$a vector<vector<int>>; $b hash<int>; $c vector<unique<int>>; $p parcel<int>; $q parcel<receipt>; $n = count($a); $m = count($b); $z = count($c); $w = unpack($p); $r = unpack($q);');
$compiler = new Compiler(new cpp_lowering_options(false));
$compiler->init([$source]);
$compiler->exec_cpp();
$body = Model::syntax_files()[0]->root->single_body()->body;
$version = $body->work()->version;
$calls = [];
for ($i = 5; $i < 10; $i++) { $calls[] = $body->statements[$i]->expression->value->require_call_preparation(); }
$engine = new CPP_Specializations();
$operation = $engine->entry($engine->request_observation($calls[0]));
$input_type = $calls[0]->signature->parameters[0]->type;
$result_type = $calls[0]->signature->return_type;
$input = $engine->entry($engine->request_opaque($input_type));
$result = $engine->entry($engine->request_opaque($result_type));
foreach ($calls as $call) { $engine->request_observation($call); }
$provider_call = $calls[4];
$provider_operation = $engine->entry($engine->request_observation($provider_call));
$provider_input = $engine->entry($engine->request_opaque($provider_call->signature->parameters[0]->type));
$provider_result = $engine->entry($engine->request_opaque($provider_call->signature->return_type));
// Integer widths exercise distinct registered bindings through the same mechanism.
foreach (['int8', 'uint64'] as $name) {
    $engine->request_opaque(Model::$type_catalog->registry()->use(Model::$type_catalog->canonical($name)->type_id()));
}
$artifacts = new Storage();
$engine->append_artifacts($artifacts);
$provider_header = str_replace('T value;', 'alignas(alignof(T) > ' . $alignment . ' ? alignof(T) : ' . $alignment . ') T value;', file_get_contents(dirname(__DIR__) . '/runtime_type_support.hpp'));
$provider_header = str_replace('struct receipt { scpp::int_t<> number; };', 'struct receipt { scpp::int_t<> number; Tracked payload{}; };', $provider_header);
$provider_header = '#include "instrument.hpp"' . "\n" . $provider_header;
if ($broken !== 0) { $provider_header .= "\n#error Broken layout dependency\n"; }
$artifacts->append(new cpp_artifact('proof:provider', cpp_artifact_kind::header, 'runtime_type_support.hpp', $provider_header));
if ($body->work()->version !== $version) { throw new \LogicException('Backend request changed semantic preparation'); }
$invalid = new prepared_call_argument();
$invalid->boundary = value_boundary_operation::copy_value;
$failed = false;
try { CPP_Opaque_Representation::borrowed_argument($invalid, 'value'); }
catch (\LogicException $error) { $failed = true; }
if (!$failed) { throw new \LogicException('Copy boundary accepted as borrow'); }
$input_mapping = CPP_Types::representation($input_type);
$result_mapping = CPP_Types::representation($result_type);
$target = weakref_get($calls[0]->target);
$binding = Model::$cpp_callable_bindings->function_binding($target);
$borrow = CPP_Opaque_Representation::borrowed_argument($calls[0]->arguments[0], 'value');
$opaque = 'scpp::internal::' . $input->name;
$opaque_result = 'scpp::internal::' . $result->name;
$invoke = 'scpp::internal::' . $operation->name;
$instrument = file_get_contents(__DIR__ . '/prelower_boundary/instrument.hpp');
$artifacts->append(new cpp_artifact('proof:instrument-header', cpp_artifact_kind::header, 'instrument.hpp', $instrument));
$artifacts->append(new cpp_artifact('proof:instrument', cpp_artifact_kind::translation_unit, 'instrument.cpp', file_get_contents(__DIR__ . '/prelower_boundary/instrument.cpp')));
$api = "#pragma once\nvoid seed(void*,const void*); long read_result(const void*); void* nested_leaf(void*); const void* const_nested_leaf(const void*); void mutate(void*); void direct(); void direct_provider(); long read_receipt(const void*);\n";
$artifacts->append(new cpp_artifact('proof:api', cpp_artifact_kind::header, 'proof.hpp', $api));
$support = '#include "instrument.hpp"' . "\n#include \"proof.hpp\"\n#include <new>\n"
    . CPP_Artifacts::includes($input_mapping->headers, '') . CPP_Artifacts::includes($result_mapping->headers, '')
    . '#include "' . $binding->header() . '"' . "\n"
    . 'using Input = ' . $input_mapping->spelling . '; using Result = ' . $result_mapping->spelling . ";\n"
    . 'Input initial() { Input value; value.append({Result(7), Result(9)}); return value; }' . "\n"
    . 'void seed(void* destination, const void*) { ::new(destination) Input(initial()); }' . "\n"
    . 'long read_result(const void* p) { return static_cast<const Result*>(p)->native_value(); }' . "\n"
    . 'void* nested_leaf(void* p) { return &static_cast<Input*>(p)->at(0).at(1); }' . "\n"
    . 'const void* const_nested_leaf(const void* p) { return &static_cast<const Input*>(p)->at(0).at(1); }' . "\n"
    . 'void mutate(void* p) { *static_cast<Result*>(p) = 21; }' . "\n"
    . 'void direct() { begin(); { Input value = initial(); Input copy(value); Input moved(std::move(copy));'
    . 'auto result = ' . $binding->symbol() . '(value); require(result.native_value()==1);'
    . 'auto* leaf = &value.at(0).at(1); *leaf = 21; require(value.at(0).at(1).native_value()==21);'
    . 'require(moved.at(0).at(1).native_value()==9); } report("direct"); }' . "\n";
$provider_input_mapping = CPP_Types::representation($provider_call->signature->parameters[0]->type);
$provider_result_mapping = CPP_Types::representation($provider_call->signature->return_type);
$provider_binding = Model::$cpp_callable_bindings->function_binding(weakref_get($provider_call->target));
$support .= '#include "runtime_type_support.hpp"' . "\n"
    . 'long read_receipt(const void* p) { return static_cast<const ' . $provider_result_mapping->spelling . '*>(p)->number.native_value(); }' . "\n"
    . 'void direct_provider() { begin(); { ' . $provider_input_mapping->spelling . ' value{}; auto result = '
    . $provider_binding->symbol() . '(value); require(result.number.native_value()==0); } report("direct_provider"); }' . "\n";
$artifacts->append(new cpp_artifact('proof:support', cpp_artifact_kind::translation_unit, 'proof.cpp', $support));
$main = '#include "' . $input->header . '"' . "\n" . '#include "' . $result->header . '"' . "\n"
    . '#include "' . $operation->header . '"' . "\n#include \"instrument.hpp\"\n#include \"proof.hpp\"\n#include <utility>\n"
    . 'int main() { direct(); begin(); { ' . $opaque . ' value(' . $opaque . '::operation_result{}, seed, nullptr);'
    . $opaque . ' copy(value); ' . $opaque . ' moved(std::move(copy));'
    . $opaque_result . ' result(' . $opaque_result . '::operation_result{}, ' . $invoke . ', ' . $borrow . ');'
    . 'require(read_result(result.address())==1); auto* leaf = nested_leaf(value.address());'
    . 'require(leaf==const_nested_leaf(value.address())); mutate(leaf); require(read_result(leaf)==21);'
    . 'require(read_result(nested_leaf(moved.address()))==9); } report("opaque"); PROVIDER_CASE return ' . $revision . '; }' . "\n";
$provider_cpp = 'scpp::internal::' . $provider_input->name;
$provider_return_cpp = 'scpp::internal::' . $provider_result->name;
$provider_case = 'direct_provider(); begin(); { ' . $provider_cpp . ' value; '
    . $provider_return_cpp . ' result(' . $provider_return_cpp . '::operation_result{}, scpp::internal::'
    . $provider_operation->name . ', ' . CPP_Opaque_Representation::borrowed_argument($provider_call->arguments[0], 'value')
    . '); require(read_receipt(result.address())==0); } report("opaque_provider"); require(live_values==0);';
$main = '#include "' . $provider_input->header . '"' . "\n" . '#include "' . $provider_result->header . '"' . "\n"
    . '#include "' . $provider_operation->header . '"' . "\n" . str_replace('PROVIDER_CASE', $provider_case, $main);
$artifacts->append(new cpp_artifact('proof:main', cpp_artifact_kind::translation_unit, 'entry.cpp', $main));
$publisher = new Output_Publication($published);
try { $publisher->publish($artifacts, new runtime_cpp_build_spec()); }
finally { $publisher->close(); }
echo "published prepared layout requests\n";
