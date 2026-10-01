<?php

/* Portable proof shared by PHP and the converted native compiler harness. */
namespace scpp\compiler;

final class S2S_Proof
{
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
		$compiler = new Compiler();
		$compiler->exec_cpp();

		$prepared_files /** Storage<prepared_file> */ = Model::$prepared_files;
		$prepared = $prepared_files[0];
		$children /** Storage<statement_node> */ = $prepared->source->root->body->statements;
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
			|| (Type_Preparation::canonical($integer)->definition() !== $integer_definition)) {
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
		$outputs /** Storage<cpp_module> */ = Model::$cpp_files;
		$old_output = $outputs[0];
		$expected = "#include \"scpp/int_t.hpp\"\n\nint main()\n{\n\tauto local_a = static_cast<scpp::int_t<>>(10LL);\n\tscpp::int_t<> local_b = local_a;\n\tlocal_a = static_cast<scpp::int_t<>>(12LL);\n\treturn static_cast<int>((local_b).native_value());\n\treturn 0;\n}\n";
		if ($old_output->text !== $expected) {
			throw new \LogicException('Unexpected C++ integer lowering');
		}
		$compiler->cpp();
		$outputs = Model::$cpp_files;
		if (($outputs[0]->text !== $expected) || ($outputs[0] === $old_output)) {
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
		if (($outputs[0]->text === $expected) || ($old_output->text !== $expected)) {
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
