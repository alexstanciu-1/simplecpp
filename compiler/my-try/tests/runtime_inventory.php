<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Current production catalogs must compose, not merely pass in isolated fixtures.
$names = ['process', 'source', 'regex', 'filesystem', 'datetime', 'strings', 'json', 'core'];
$input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', $names);
Compiler_Lifecycle::configure_runtime($input);
Compiler_Lifecycle::reset();
$before = Model::$type_catalog->definition('process_handle');
$build = CPP_Runtime_Build::requirements($input);
if ($build->sources !== ['core/runtime.cpp', 'modules/process/process.cpp', 'modules/regex/regex.cpp',
	'modules/datetime/datetime.cpp', 'modules/json/document.cpp', 'modules/json/json.cpp'] || $build->libraries !== ['pcre2-8']) {
	throw new \LogicException('Production module build closure changed');
}
Compiler_Lifecycle::reset();
if (Model::$runtime_metadata !== $input || Model::$type_catalog->definition('process_handle') === $before) {
	throw new \LogicException('Production reset lost retained input/fresh type identities');
}
echo "Production runtime catalogs compose and reset\n";

// Loading declarations is not a request to prepare their C++ implementations.
function inventory_specializations(CPP_Specializations $engine): array
{
	$artifacts = new Storage /** Storage<cpp_artifact> */();
	$engine->append_artifacts($artifacts);
	$headers = [];
	foreach ($artifacts as $artifact) {
		if ($artifact->kind() === cpp_artifact_kind::header && $artifact->file_name !== 'specializations/base.hpp') {
			$headers[$artifact->file_name] = $artifact->text;
		}
	}
	return $headers;
}

$engine = new CPP_Specializations();
$null = Model::$type_catalog->null_type();
$null_mapping = CPP_Types::representation($null, new cpp_generation_context($engine));
if ($null_mapping->opaque || $null_mapping->spelling !== 'scpp::null_t'
	|| (new Capability_Facts())->has_capability($null, generic_contract::value_storable)) {
	throw new \LogicException('Null expression binding changed storage admission');
}
if (inventory_specializations($engine) !== []) {
	throw new \LogicException('Catalog loading eagerly generated concrete runtime types');
}
$registry = Model::$type_catalog->registry();
$integer = $registry->use(Model::$type_catalog->canonical('int')->type_id());
$vector_definition = object_cast(Model::$type_catalog->definition('vector'), abstract_template_type_definition::class);
$hash_definition = object_cast(Model::$type_catalog->definition('hash'), abstract_template_type_definition::class);
$vector = $registry->use($registry->intern_application($vector_definition, [$integer])->type_id());
$nested = $registry->use($registry->intern_application($hash_definition, [$vector])->type_id());
$types_before = $registry->type_count();
$first = $engine->request_composed($nested);
$headers = inventory_specializations($engine);
$integer_entry = $engine->entry($engine->request_composed($integer));
$string = $registry->use(Model::$type_catalog->canonical('string')->type_id());
$string_entry = $engine->entry($engine->request_composed($string));
$vector_entry = $engine->entry($engine->request_composed($vector));
$nested_entry = $engine->entry($first);
$expected = [];
foreach ([$integer_entry, $string_entry, $vector_entry, $nested_entry] as $entry) {
	$expected[$entry->header] = true;
	$expected[CPP_Opaque_Representation::declaration_header('scpp::internal::' . $entry->name)] = true;
}
if ((count($headers) !== count($expected)) || (array_diff_key($expected, $headers) !== [])) {
	throw new \LogicException('Nested demand omitted a dependency or prepared unrelated runtime types');
}
$again = $engine->request_composed($nested);
if ($again->entry_id !== $first->entry_id || inventory_specializations($engine) !== $headers
	|| $registry->type_count() !== $types_before) {
	throw new \LogicException('Repeated demand changed output or semantic type identities');
}
$fresh_engine = new CPP_Specializations();
if (inventory_specializations($fresh_engine) !== []) {
	throw new \LogicException('Demand leaked into a new engine through catalog state');
}
echo "All catalogs loaded; only requested hash<vector<int>,string>, vector<int>, string and int prepared; null stays expression-only\n";
