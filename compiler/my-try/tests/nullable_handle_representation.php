<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function representation_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
$folder = $argv[1];
Compiler_Lifecycle::reset();
$module = new module('/optional_model', '/optional_model', '/optional_model');
$file = new file();
$file->path = 'main.phs';
$file->content = 'class Node {} $node = new Node();';
Source_Registry::add($module, $file);
Model::$modules->add($module->name, $module);
(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
$registry = Model::$type_catalog->registry();
$definition = Model::$global_scope->types_named('Node')[0];
$required = $registry->use($registry->canonical($definition)->type_id());
$nullable = Model::$type_catalog->definition('nullable');
$optional = $registry->use($registry->intern_application($nullable, [$required], new Capability_Facts())->type_id());
representation_check(!$required->matches($optional), 'Optional semantic identity collapsed');
representation_check(!Type_Preparation::same_storage_type($required, $optional), 'Reference contracts collapsed');
representation_check(Type_Queries::nullable_handle_payload($optional)->matches($required), 'Missing optional handle interpretation');
representation_check(!Type_Queries::is_nominal_handle($optional), 'Optionality leaked into implicit ownership classification');
representation_check(CPP_Types::representation($required)->spelling === CPP_Types::representation($optional)->spelling, 'Optional handle introduced another native wrapper');
representation_check(CPP_Types::stored_recipe($required) === CPP_Types::stored_recipe($optional), 'Optional handle lost the storage recipe');
representation_check(CPP_Types::pointee($optional)->matches($required), 'Optional pointer surface lost its payload');
representation_check(CPP_Wrapper_Transport::payload($optional) === null, 'Handle incorrectly treated as a physical optional wrapper');

$before_signature = new prepared_source_callable();
$before_signature->signature->return_type = $required;
$after_signature = new prepared_source_callable();
$after_signature->signature->return_type = $optional;
representation_check(!Preparation_Changes::same_signature($before_signature, $after_signature), 'Return nullability edit was invisible');
$after_signature->signature->return_type = $required;
representation_check(Preparation_Changes::same_signature($before_signature, $after_signature), 'Return nullability revert did not recover');
$old_parameter = new prepared_parameter();
$old_parameter->contract = new callable_parameter();
$old_parameter->contract->type = $required;
$old_parameter->contract->mode = passing_mode::reference;
$new_parameter = new prepared_parameter();
$new_parameter->contract = new callable_parameter();
$new_parameter->contract->type = $optional;
$new_parameter->contract->mode = passing_mode::reference;
$before_signature->parameters->append($old_parameter);
$after_signature->parameters->append($new_parameter);
representation_check(!Preparation_Changes::same_signature($before_signature, $after_signature), 'Parameter nullability edit was invisible');
$renamed_nullable = $registry->define_nullable('MaybeNode', type_definition_origin::runtime,
	nominal_type_kind::structure, [new template_type_parameter('Item', [])], []);
$renamed_optional = $registry->use($registry->intern_application($renamed_nullable, [$required], new Capability_Facts())->type_id());
representation_check(Type_Queries::nullable_handle_payload($renamed_optional)->matches($required), 'Interpretation depends on nullable spelling');
representation_check(CPP_Types::representation($renamed_optional)->spelling === CPP_Types::representation($required)->spelling, 'Representation depends on nullable spelling');
$integer = $registry->use(Model::$type_catalog->canonical('int')->type_id());
$optional_int = $registry->use($registry->intern_application($nullable, [$integer])->type_id());
representation_check(Type_Queries::nullable_handle_payload($optional_int) === null && CPP_Wrapper_Transport::payload($optional_int)->matches($integer), 'Scalar optional lost physical wrapper');
$vector = Model::$type_catalog->definition('vector');
$required_vector = $registry->use($registry->intern_application($vector, [$required], new Capability_Facts())->type_id());
$optional_vector = $registry->use($registry->intern_application($vector, [$optional], new Capability_Facts())->type_id());
representation_check(!$required_vector->matches($optional_vector), 'Nested semantic keys collapsed');
representation_check(CPP_Types::representation($required_vector)->spelling === CPP_Types::representation($optional_vector)->spelling, 'Nested native representation differs');
$engine = new CPP_Specializations();
$required_entry = $engine->entry($engine->request_composed($required));
$optional_entry = $engine->entry($engine->request_composed($optional));
representation_check($required_entry->name !== $optional_entry->name, 'Concrete semantic keys collapsed');
$vector_entry = $engine->entry($engine->request_composed($optional_vector));
$context = new cpp_generation_context();
$make_required = CPP_Wrapper_Transport::provider_payload($required, 'native_value', true, $engine, $context);
$read_required = CPP_Wrapper_Transport::provider_payload($required, 'value', false, $engine, $context);
$make_optional = CPP_Wrapper_Transport::provider_payload($optional, 'native_value', true, $engine, $context);
$read_optional = CPP_Wrapper_Transport::provider_payload($optional, 'value', false, $engine, $context);
$make_vector = CPP_Wrapper_Transport::provider_payload($optional_vector, 'native_values', true, $engine, $context);
$read_vector = CPP_Wrapper_Transport::provider_payload($optional_vector, 'values', false, $engine, $context);
$artifacts = new Storage();
foreach (Model::$cpp_files as $artifact) {
	if ($artifact->kind() === cpp_artifact_kind::header) { $artifacts->append($artifact); }
}
$engine->append_artifacts($artifacts);
foreach ($artifacts as $artifact) {
	$path = $folder . '/' . $artifact->file_name;
	if (!is_dir(dirname($path))) { mkdir(dirname($path), 0777, true); }
	file_put_contents($path, $artifact->text);
}
$r = 'scpp::internal::' . $required_entry->name;
$o = 'scpp::internal::' . $optional_entry->name;
$v = 'scpp::internal::' . $vector_entry->name;
$header = '#pragma once' . "\n" . '#include "' . $required_entry->header . '"' . "\n"
	. '#include "' . $optional_entry->header . '"' . "\n" . '#include "' . $vector_entry->header . '"' . "\n"
	. $r . ' make_required(); ' . $o . ' make_optional(const ' . $r . '& value); bool absent(const ' . $o . '& value); '
	. $v . ' roundtrip_vector(const ' . $o . '& value); bool vector_ok(const ' . $v . '& values);' . "\n";
file_put_contents($folder . '/proof.hpp', $header);
$native_vector = CPP_Types::representation($optional_vector)->spelling;
$implementation = '#include "proof.hpp"' . "\n#include <memory>\n#include <new>\n#include \"scpp/memory.hpp\"\n"
	. CPP_Artifacts::includes($context->headers, '')
	. $r . ' make_required() { auto native_value = scpp::shared<record_Node>(); return ' . $make_required . '; }' . "\n"
	. $o . ' make_optional(const ' . $r . '& value) { auto native_value = ' . $read_required . '; return ' . $make_optional . '; }' . "\n"
	. 'bool absent(const ' . $o . '& value) { auto native_value = ' . $read_optional . '; return !native_value; }' . "\n"
	. $v . ' roundtrip_vector(const ' . $o . '& value) { ' . $native_vector . ' native_values; native_values.append(' . $read_optional . '); native_values.append(scpp::null); return ' . $make_vector . '; }' . "\n"
	. 'bool vector_ok(const ' . $v . '& values) { auto native_values = ' . $read_vector . '; return native_values.size() == 2 && static_cast<bool>(native_values.at(0)) && !native_values.at(1); }' . "\n";
file_put_contents($folder . '/provider.cpp', $implementation);
file_put_contents($folder . '/proof.cpp', '#include "proof.hpp"' . "\n"
	. 'static_assert(sizeof(' . $r . ') == sizeof(' . $o . '));' . "\n"
	. 'int main() { auto required = make_required(); auto optional = make_optional(required); if (required.operator->() != optional.operator->()) return 1; '
	. $o . ' empty; if (!absent(empty)) return 2; auto copy = optional; required = {}; optional = {}; '
	. 'if (absent(copy)) return 3; auto values = roundtrip_vector(copy); if (!vector_ok(values)) return 4; return 0; }' . "\n");
echo "Nullable handle semantic identity, storage and provider model passed\n";
