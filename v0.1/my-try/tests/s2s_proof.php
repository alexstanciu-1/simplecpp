<?php

/* Portable proof shared by PHP and the converted native compiler harness. */
namespace scpp\compiler;

final class S2S_Proof
{
	/** Exercise production publication and native build owners through a portable request. */
	public static function chapter14(string $directory): string
	{
		try
		{
			$published = fs_read_text($directory . '/published.txt');
			$mode = fs_read_text($directory . '/mode.txt');
			if (($mode === 'publish') || ($mode === 'publish-prelowered'))
			{
				$modules = self::request_modules(fs_read_text($directory . '/module.txt'));
				Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load(fs_read_text($directory . '/catalog.txt'), $modules));
				$publication = new Output_Publication($published);
				try
				{
					$paths /** vector<string> */ = [fs_read_text($directory . '/source.txt')];
					$compiler = new Compiler(new cpp_lowering_options(false));
					$compiler->cpp_lowering->prelower_templates = $mode === 'publish-prelowered';
					$compiler->init($paths);
					$compiler->select_entry('main', 'main.phs');
					$compiler->exec_cpp($publication);
					$outputs /** Storage<cpp_artifact> */ = Model::$cpp_files;
					$first = CPP_Output::encode($outputs);
					if ($compiler->cpp_lowering->prelower_templates)
					{
						$compiler->prepare();
						$compiler->cpp();
						$outputs = Model::$cpp_files;
						if (CPP_Output::encode($outputs) !== $first) {
							throw new \LogicException('Repeated native prelowering changed artifacts');
						}
					}
					return $first;
				}
				finally
				{
					$publication->close();
				}
			}
			$cxx = '/usr/bin/clang++';
			if ($mode === 'build-recorded') {
				$cxx = fs_read_text($directory . '/cxx.txt');
			}
			$config = new native_cpp_config($cxx, fs_read_text($directory . '/runtime.txt'), fs_read_text($directory . '/cache.txt'));
			$config->pch = fs_read_text($directory . '/pch.txt') === 'yes';
			$result = (new Native_CPP_Build($config))->build($published);
			return '{"executable":' . json_quote($result->executable) . ',"compiled":' . $result->compiled
				. ',"reused":' . $result->reused . ',"pch_built":' . $result->pch_built . ',"linked":' . $result->linked
				. ',"diagnostics":' . json_quote($result->diagnostics) . '}';
		}
		catch (\Throwable $error)
		{
			return "ERROR\n" . $error->getMessage();
		}
	}

	/** Requests may select the same module list as the public CLI, or one legacy gate module. */
	private static function request_modules(string $text): array /** vector<string> */
	{
		$modules /** vector<string> */ = [];
		if (string_byte_starts_with($text, '['))
		{
			$reader = new \scpp\Json_Reader($text, 'native request modules', 128);
			$reader->begin_array();
			while ($reader->next_element()) {
				$modules[] = $reader->read_string();
			}
		}
		else {
			$modules[] = $text;
		}
		return $modules;
	}

	/** Run bounded feature edits through the same retained compiler in PHP and native form. */
	public static function edited_source(string $valid, string $invalid, string $changed): string
	{
		Compiler_Lifecycle::reset();
		$input_module = new module('/edit-proof', '/edit-proof', '/edit-proof');
		$source = new file();
		$source->path = 'main.phs';
		$source->content = $valid;
		$record = Source_Registry::add($input_module, $source);
		$modules /** Keyed_Storage<module> */ = Model::$modules;
		$modules->add($input_module->name, $input_module);
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->exec_cpp();
		$outputs /** Storage<cpp_artifact> */ = Model::$cpp_files;
		$original = CPP_Output::encode($outputs);
		$paths /** vector<string> */ = ['/edit-proof/main.phs'];
		$record->file->content = $invalid;
		$rejected = false;
		try {
			$compiler->update_cpp($paths);
		}
		catch (\RuntimeException $error) {
			$rejected = true;
		}
		$outputs = Model::$cpp_files;
		if ((!$rejected) || (!$outputs->is_empty())) {
			throw new \LogicException('Invalid edit published stale or partial C++');
		}
		$record->file->content = $changed;
		$compiler->update_cpp($paths);
		$outputs = Model::$cpp_files;
		if (CPP_Output::encode($outputs) === $original) {
			throw new \LogicException('Valid edit reused stale C++');
		}
		$record->file->content = $valid;
		$compiler->update_cpp($paths);
		$outputs = Model::$cpp_files;
		if (CPP_Output::encode($outputs) !== $original) {
			throw new \LogicException('Edited-source repair changed output');
		}
		Compiler_Lifecycle::reset();
		$input_module = new module('/edit-proof', '/edit-proof', '/edit-proof');
		$source = new file();
		$source->path = 'main.phs';
		$source->content = $valid;
		Source_Registry::add($input_module, $source);
		$modules = Model::$modules;
		$modules->add($input_module->name, $input_module);
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->exec_cpp();
		$outputs = Model::$cpp_files;
		if (CPP_Output::encode($outputs) !== $original) {
			throw new \LogicException('Edited-source fresh output differs');
		}
		return $original;
	}

	/** Native control-flow identity and unselected-arm dependency recovery use one session. */
	public static function control_flow(): string
	{
		Compiler_Lifecycle::reset();
		$input_module = new module('/control-proof', '/control-proof', '/control-proof');
		$source = new file();
		$source->path = 'main.phs';
		$body = 'function choose(): int { if ("0") { return 1; } '
			. 'return match (1) { default => branch(), 1 => (true ? 8 : 2) }; } '
			. 'function stable(): int { return 9; } return choose();';
		$valid = 'function branch(): int { return 2; } ' . $body;
		$source->content = $valid;
		$record = Source_Registry::add($input_module, $source);
		$modules /** Keyed_Storage<module> */ = Model::$modules;
		$modules->add($input_module->name, $input_module);
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->exec_cpp();
		$outputs /** Storage<cpp_artifact> */ = Model::$cpp_files;
		$original = CPP_Probe::text($outputs);
		$functions /** vector<collected_function> */ = Model::$global_scope->functions_named('choose');
		$retained_body = $functions[0]->callable_syntax()->require_body();
		$stable_functions /** vector<collected_function> */ = Model::$global_scope->functions_named('stable');
		$stable_work = $stable_functions[0]->callable_syntax()->require_body()->work();
		$stable_version = $stable_work->version;
		$paths /** vector<string> */ = ['/control-proof/main.phs'];
		$record->file->content = "\n\n" . $valid;
		$compiler->update_cpp($paths);
		$functions = Model::$global_scope->functions_named('choose');
		$outputs = Model::$cpp_files;
		if (($functions[0]->callable_syntax()->require_body() !== $retained_body) || (CPP_Probe::text($outputs) !== $original)) {
			throw new \LogicException('Native control-flow span move lost body identity or output');
		}

		$record->file->content = 'function branch(): float { return 2.0; } ' . $body;
		$compiler->sync($paths);
		for ($attempt = 0; $attempt < 2; $attempt++)
		{
			$rejected = false;
			try {
				$compiler->prepare();
			}
			catch (\RuntimeException $error) {
				$rejected = true;
			}
			$outputs = Model::$cpp_files;
			if ((!$rejected) || (!$outputs->is_empty()) || Model::$rebuild_required) {
				throw new \LogicException('Native match dependency failure was not withheld and retryable');
			}
		}
		if ($stable_work->version !== $stable_version) {
			throw new \LogicException('Native match failure invalidated an unrelated body');
		}
		$record->file->content = $valid;
		$compiler->update_cpp($paths);
		$outputs = Model::$cpp_files;
		if (CPP_Probe::text($outputs) !== $original) {
			throw new \LogicException('Native match repair changed generated output');
		}
		return $original;
	}

	/** Prove the canonical type model through the portable compiler and emitted C++. */
	public static function types(): string
	{
		Compiler_Lifecycle::reset();
		$initial_applications = Model::$type_catalog->registry()->application_count();
		$input_module = new module('/type-proof', '/type-proof', '/type-proof');
		$source = new file();
		$source->path = 'main.phs';
		$source->content = 'struct Envelope { value<int> $item; vector<value<int>> $items; } '
			. 'function carry(value<int> $item): value<int> { return $item; } '
			. '$first vector<int>; $second vector<int>; '
			. '$nested vector<vector<hash<int>>>; $maybe nullable<string>; '
			. '$shared_value shared<int>; $weak_value weak<int>; $unique_value unique<int>; '
			. '$by_value value<int>; $nested_value vector<value<int>>; return 0;';
		Source_Registry::add($input_module, $source);
		$modules /** Keyed_Storage<module> */ = Model::$modules;
		$modules->add($input_module->name, $input_module);
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->exec_cpp();

		$catalog = Model::$type_catalog;
		$registry = $catalog->registry();
		$mixed_type = $catalog->canonical('mixed');
		$dynamic_type = $catalog->canonical('dynamic');
		if (!($mixed_type->definition() instanceof mixed_semantics)
			|| !($dynamic_type->definition() instanceof dynamic_semantics)
			|| ($mixed_type->type_id() === $dynamic_type->type_id())
			|| !$registry->is_value_type($mixed_type->type_id())
			|| !$registry->is_value_type($dynamic_type->type_id())) {
			throw new \LogicException('Dynamic carrier roles, identities or storage changed');
		}
		if (($catalog->canonical('byte') !== $catalog->canonical('uint8'))
			|| ($catalog->definition('byte')->name() !== 'uint8')) {
			throw new \LogicException('byte did not reuse the stable uint8 identity');
		}
		$vector = object_cast($catalog->definition('vector'), template_type_definition::class);
		$vector_contracts /** vector<generic_contract> */ = $vector->parameter(0)->contracts();
		$string_type = $catalog->canonical('string');
		$string_use = $registry->use($string_type->type_id());
		if ((q_count($vector_contracts) !== 1)
			|| ($vector_contracts[0] !== generic_contract::value_storable)
			|| (!$registry->has_capability($string_use, generic_contract::copyable_value))
			|| (!$registry->has_capability($string_use, generic_contract::value_storable))
			|| (!$registry->has_capability($string_use, generic_contract::hashable))
			|| (!$registry->has_capability($string_use, generic_contract::comparable))) {
			throw new \LogicException('Declarative type capabilities changed');
		}

		$prepared_files /** Storage<prepared_file> */ = Model::$prepared_files;
		$statements /** Storage<statement_node> */ = $prepared_files[0]->source->root->single_body()->body->statements;
		$first = object_cast($statements[0], variable_declaration_node::class);
		$second = object_cast($statements[1], variable_declaration_node::class);
		$first_syntax = object_cast($first->require_type_syntax(), template_application_type_node::class);
		$second_syntax = object_cast($second->require_type_syntax(), template_application_type_node::class);
		$first_use = $first_syntax->require_preparation();
		$second_use = $second_syntax->require_preparation();
		if (($first_use === $second_use) || (!$first_use->matches($second_use))) {
			throw new \LogicException('Type occurrences did not reuse one canonical application identity');
		}
		$vector_int = object_cast(Type_Queries::canonical($first_use), applied_template_type::class);
		if (($vector_int->definition() !== $vector) || ($registry->application_count() !== $initial_applications + 8)) {
			throw new \LogicException('Demand-created runtime applications changed');
		}
		$nullable_definition = $catalog->definition('nullable');
		if (!($nullable_definition instanceof nullable_semantics) || ($vector instanceof nullable_semantics)) {
			throw new \LogicException('Template structure and nullable semantics were conflated');
		}
		$nullable = object_cast($nullable_definition, abstract_template_type_definition::class);
		$nullable_role = object_cast($nullable_definition, nullable_semantics::class);
		$payload_arguments /** vector<canonical_type_use> */ = [$string_use];
		$nullable_application = $registry->intern_application($nullable, $payload_arguments);
		if (($registry->intern_application($nullable, $payload_arguments) !== $nullable_application)
			|| (!$nullable_role->payload_type($nullable_application)->matches($string_use))) {
			throw new \LogicException('Nullable lost shared interning or payload identity');
		}
		$by_value = object_cast($statements[7], variable_declaration_node::class);
		$by_value_use = $by_value->require_type_syntax()->require_preparation();
		$nested_value = object_cast($statements[8], variable_declaration_node::class);
		$nested_value_type = object_cast(Type_Queries::canonical(
			$nested_value->require_type_syntax()->require_preparation()), applied_template_type::class);
		$nested_value_arguments /** vector<canonical_type_use> */ = $nested_value_type->arguments();
		if (($by_value_use->by_value())
			|| ($by_value_use->type_id() !== Language_Types::integer(Model::$language_scope)->type_id())
			|| ($nested_value_arguments[0]->by_value())) {
			throw new \LogicException('Redundant value<T> did not normalize its semantic use');
		}

		$before_rejection = $registry->application_count();
		$rejected = false;
		try {
			$no_value_type = $catalog->canonical('void');
			$rejected_arguments /** vector<canonical_type_use> */ = [
				$registry->use($no_value_type->type_id()),
			];
			$registry->intern_application($vector, $rejected_arguments);
		}
		catch (\InvalidArgumentException $error) {
			$rejected = q_strpos($error->getMessage(), 'value_storable') !== false;
		}
		if ((!$rejected) || ($registry->application_count() !== $before_rejection)) {
			throw new \LogicException('Capability rejection published a canonical application');
		}

		$outputs /** Storage<cpp_artifact> */ = Model::$cpp_files;
		$text = CPP_Probe::text($outputs);
		if ((q_strpos($text, '#include "scpp/vector_t.hpp"') === false)
			|| (q_strpos($text, '#include "scpp/hash_t.hpp"') === false)
			|| (q_strpos($text, '#include "scpp/value_p.hpp"') !== false)
			|| (q_strpos($text, 'scpp::vector_t<scpp::int_t<>> local_nestedU_value;') === false)
			|| (q_strpos($text, 'scpp::int_t<> field_item;') === false)
			|| (q_strpos($text, 'scpp::int_t<> function_carry(') === false)) {
			throw new \LogicException('Recursive constructed-type C++ representation changed');
		}
		return $text;
	}

	/** Exercise language identity, inference, reassignment, source purity and regeneration. */
	public static function run(): string
	{
		Compiler_Lifecycle::reset();
		self::collection_membership();
		$input_module = new module('/s2s-proof', '/s2s-proof', '/s2s-proof');
		$source = new file();
		$source->path = 'main.phs';
		$source->content = '$a = 10; $b int = $a; $a = 12; return $b;';
		$stable = Source_Registry::add($input_module, $source);
		$modules /** Keyed_Storage<module> */ = Model::$modules;
		$modules->add($input_module->name, $input_module);
		$compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->exec_cpp();

		$prepared_files /** Storage<prepared_file> */ = Model::$prepared_files;
		$prepared = $prepared_files[0];
		$children /** Storage<statement_node> */ = $prepared->source->root->single_body()->body->statements;
		$first_data = object_cast(object_cast($children[0], expression_statement_node::class)->expression, assignment_expression_node::class);
		$assignment_data = object_cast(object_cast($children[2], expression_statement_node::class)->expression, assignment_expression_node::class);
		$literal_data = object_cast($first_data->value, integer_literal_node::class);
		$reference_data = object_cast(object_cast($children[1], variable_declaration_node::class)->initializer, variable_reference_node::class);
		$first = $first_data->require_assignment_preparation()->binding;
		$assignment = $assignment_data->require_assignment_preparation()->binding;
		$literal = $literal_data->require_integer_literal_preparation();
		$reference = $reference_data->require_variable_reference_preparation();
		if (($literal->decimal !== '10') || (weakref_get($reference->declaration) !== weakref_get($first->declaration)) || (!$reference->type->matches($first->type))) {
			throw new \LogicException('Specialized expression facts lost literal value or reference identity');
		}
		$integer = Language_Types::integer(Model::$language_scope);
		$resolved /** vector<type_definition_i> */ = Scope_Lookup::types(Model::$global_scope, 'int');
		$integer_definition = object_cast($resolved[0], integer_type_definition::class);
		if (($integer_definition->origin() !== type_definition_origin::language)
			|| ($integer_definition->bit_width() !== 64) || (!$integer_definition->signed())
			|| (Type_Queries::canonical($integer)->definition() !== $integer_definition)) {
			throw new \LogicException('Canonical integer identity or representation changed');
		}
		if ((Model::$global_scope->parent_scope() !== Model::$language_scope)
			|| (!$first->type->matches($integer)) || (!$literal->type->matches($integer))) {
			throw new \LogicException('Literal or local did not retain the canonical integer type');
		}
		if (($first->resolved_kind !== binding_kind::declaration) || ($assignment->resolved_kind !== binding_kind::assignment) || (weakref_get($assignment->declaration) !== weakref_get($first->declaration))) {
			throw new \LogicException('First assignment and reassignment lost declaration identity');
		}
		if ($first_data->kind() !== node_kind::assignment_expression) {
			throw new \LogicException('Preparation mutated the parsed binding');
		}
		$outputs /** Storage<cpp_artifact> */ = Model::$cpp_files;
		$old_output = CPP_Output::single_unit($outputs);
		$expected = "#include \"scpp/int_t.hpp\"\n#include \"lang/php/cli_context.hpp\"\n#include \"scpp/runtime_error.hpp\"\n\nint main(int argc, char** argv)\n{\n// SCPP-ENTRY-TRY\n\ttry {\n\tscpp::php::set_cli_args(argc, argv);\n\tauto local_a = static_cast<scpp::int_t<>>(10LL);\n\tscpp::int_t<> local_b = local_a;\n\tlocal_a = static_cast<scpp::int_t<>>(12LL);\n\treturn static_cast<int>((local_b).native_value());\n\treturn 0;\n// SCPP-ENTRY-CATCH\n\t} catch (const std::exception& failure) {\n\t\tscpp::print_runtime_exception(failure);\n\t\treturn 1;\n\t}\n// SCPP-ENTRY-END\n}\n";
		if ($old_output->text !== $expected) {
			throw new \LogicException('Unexpected C++ integer lowering');
		}
		$compiler->cpp();
		$outputs = Model::$cpp_files;
		if ((CPP_Probe::text($outputs) !== $expected) || (CPP_Output::single_unit($outputs) === $old_output)) {
			throw new \LogicException('Repeated generation changed bytes or reused output records');
		}
		if ($first_data->require_assignment_preparation()->binding !== $first) {
			throw new \LogicException('Emission replaced shared prepared facts');
		}
		Compiler_Lifecycle::reset_cpp();
		if (($literal_data->require_integer_literal_preparation() !== $literal) || ($reference_data->require_variable_reference_preparation() !== $reference)) {
			throw new \LogicException('C++ output reset discarded shared facts');
		}
		Compiler_Lifecycle::reset_preparation();
		if (($first_data->preparation() !== null) || ($assignment_data->preparation() !== null) || ($literal_data->preparation() !== null) || ($reference_data->preparation() !== null)) {
			throw new \LogicException('Specializations did not clear their prepared facts');
		}
		$compiler->prepare();
		$compiler->cpp();
		$current_inputs /** Keyed_Storage<source_record> */ = $input_module->sources;
		$current_inputs['main.phs']->file->content = '$a = 13; return $a;';
		$paths /** vector<string> */ = ['/s2s-proof/main.phs'];
		$compiler->update_cpp($paths);
		if (Source_Registry::find('/s2s-proof/main.phs') !== $stable) {
			throw new \LogicException('Incremental update replaced stable source membership');
		}
		$outputs = Model::$cpp_files;
		if ((CPP_Probe::text($outputs) === $expected) || ($old_output->text !== $expected)) {
			throw new \LogicException('Update damaged old output or failed to regenerate');
		}
		// Detached body handles may retain old facts; only the current tree is eligible for generation.
		$current_inputs['main.phs']->file->content = '$a = 1; $b = $missing;';
		$failed = false;
		try {
			$compiler->update_cpp($paths);
		}
		catch (\RuntimeException $error) {
			$failed = true;
		}
		$outputs = Model::$cpp_files;
		$prepared_files = Model::$prepared_files;
		if ((!$failed) || (!$outputs->is_empty()) || (!$prepared_files->is_empty())) {
			throw new \LogicException('Failed preparation retained stale C++ results');
		}
		// Partial mutable facts after failure are a recorded recovery debt; no result is published.
		return $expected;
	}

	/** Exercise the same duplicate-key/snapshot contract in PHP and the native compiler. */
	private static function collection_membership(): void
	{
		$first = Language_Types::integer(Model::$language_scope);
		$second = Language_Types::boolean(Model::$language_scope);
		$groups /** Key_Storage_List<canonical_type_use> */ = new Key_Storage_List();
		$groups->add('1', $first);
		$groups->add('01', $second);
		$groups->add('1', $first);
		$all /** vector<canonical_type_use> */ = $groups->items();
		$named /** vector<canonical_type_use> */ = $groups->named('1');
		$other /** vector<canonical_type_use> */ = $groups->named('01');
		$missing /** vector<canonical_type_use> */ = $groups->named('missing');
		if ((q_count($all) !== 3) || (q_count($named) !== 2) || (q_count($other) !== 1) || (q_count($missing) !== 0)) {
			throw new \LogicException('Duplicate-key collection lost membership');
		}
		if (($all[0] !== $first) || ($all[1] !== $second) || ($all[2] !== $first) || ($named[0] !== $first) || ($named[1] !== $first) || ($other[0] !== $second)) {
			throw new \LogicException('Duplicate-key collection lost ordering or object identity');
		}
		$all[] = $second;
		$alias /** Key_Storage_List<canonical_type_use> */ = $groups;
		$alias->add('1', $second);
		$after /** vector<canonical_type_use> */ = $groups->items();
		if ((q_count($after) !== 4) || ($after[3] !== $second) || $groups->is_empty()) {
			throw new \LogicException('Collection alias or snapshot membership changed');
		}
	}
}
