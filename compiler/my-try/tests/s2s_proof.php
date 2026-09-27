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
		$input_module = new module();
		$input_module->path = '/s2s-proof';
		$source = new file();
		$source->path = '/s2s-proof/main.phs';
		$source->content = '$a = 10; $b int = $a; $a = 12; return $b;';
		$stable = Source_Registry::add($input_module, $source);
		Model::$modules[] = $input_module;
		$compiler = new Compiler();
		$compiler->exec_cpp();

		$prepared_files /** Storage<prepared_file> */ = Model::$prepared_files;
		$prepared = $prepared_files[0];
		$children /** Storage<ast_node> */ = Syntax_Nodes::block_data($prepared->source->root)->children;
		$first_data = Syntax_Nodes::binding_data($children[0]);
		$assignment_data = Syntax_Nodes::binding_data($children[2]);
		$literal_data = Syntax_Nodes::integer_data($first_data->value);
		$reference_data = Syntax_Nodes::reference_data(Syntax_Nodes::binding_data($children[1])->value);
		$first = $first_data->require_preparation();
		$assignment = $assignment_data->require_preparation();
		$literal = $literal_data->require_preparation();
		$reference = $reference_data->require_preparation();
		if (($literal->decimal !== '10') || (weakref_get($reference->declaration) !== weakref_get($first->declaration)) || ($reference->type !== $first->type)) {
			throw new \LogicException('Specialized expression facts lost literal value or reference identity');
		}
		$integer = Language_Types::integer(Model::$language_scope);
		$resolved /** vector<type_definition> */ = Scope_Lookup::types(Model::$global_scope, 'int');
		if (($resolved[0] !== $integer) || ($integer->origin !== type_origin::language) || ((int) $integer->value_bits !== 64) || (!$integer->signed) || ($integer->declaration !== null)) {
			throw new \LogicException('Canonical integer identity or representation changed');
		}
		if ((Model::$global_scope->parent_scope() !== Model::$language_scope) || ($first->type !== $integer) || ($literal->type !== $integer)) {
			throw new \LogicException('Literal or local did not retain the canonical integer type');
		}
		if (($first->resolved_kind !== binding_kind::declaration) || ($assignment->resolved_kind !== binding_kind::assignment) || (weakref_get($assignment->declaration) !== weakref_get($first->declaration))) {
			throw new \LogicException('First assignment and reassignment lost declaration identity');
		}
		if ($first_data->syntax_kind !== binding_kind::unresolved) {
			throw new \LogicException('Preparation mutated the parsed binding');
		}
		$outputs /** Storage<cpp_module> */ = Model::$cpp_files;
		$old_output = $outputs[0];
		$expected = "#include \"scpp/int_t.hpp\"\n\nint main()\n{\n\tauto local_0 = static_cast<scpp::int_t<>>(10LL);\n\tauto local_4 = local_0;\n\tlocal_0 = static_cast<scpp::int_t<>>(12LL);\n\treturn static_cast<int>((local_4).native_value());\n\treturn 0;\n}\n";
		if ($old_output->text !== $expected) {
			throw new \LogicException('Unexpected C++ integer lowering');
		}
		$compiler->cpp();
		$outputs = Model::$cpp_files;
		if (($outputs[0]->text !== $expected) || ($outputs[0] === $old_output)) {
			throw new \LogicException('Repeated generation changed bytes or reused output records');
		}
		if ($first_data->require_preparation() !== $first) {
			throw new \LogicException('Emission replaced shared prepared facts');
		}
		Compiler_Lifecycle::reset_cpp();
		if (($literal_data->require_preparation() !== $literal) || ($reference_data->require_preparation() !== $reference)) {
			throw new \LogicException('C++ output reset discarded shared facts');
		}
		Compiler_Lifecycle::reset_preparation();
		if (($first_data->preparation() !== null) || ($assignment_data->preparation() !== null) || ($literal_data->preparation() !== null) || ($reference_data->preparation() !== null)) {
			throw new \LogicException('Specializations did not clear their prepared facts');
		}
		$compiler->prepare();
		$compiler->cpp();
		$current_inputs /** Storage<source_record> */ = $input_module->sources;
		$current_inputs[0]->file->content = '$a = 13; return $a;';
		$paths /** vector<string> */ = ['/s2s-proof/main.phs'];
		$compiler->update_cpp($paths);
		if (Source_Registry::find('/s2s-proof/main.phs') !== $stable) {
			throw new \LogicException('Incremental update replaced stable source membership');
		}
		$outputs = Model::$cpp_files;
		if (($outputs[0]->text === $expected) || ($old_output->text !== $expected)) {
			throw new \LogicException('Update damaged old output or failed to regenerate');
		}
		if (($first_data->preparation() !== null) || ($literal_data->preparation() !== null) || ($reference_data->preparation() !== null)) {
			throw new \LogicException('Incremental replacement left facts on the detached old tree');
		}
		$current_inputs[0]->file->content = '$a = 1; $b = $missing;';
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
		$failed_syntax /** Storage<parsed_file> */ = Model::syntax_files();
		$failed_children /** Storage<ast_node> */ = Syntax_Nodes::block_data($failed_syntax[0]->root)->children;
		$partial = Syntax_Nodes::binding_data($failed_children[0]);
		$partial_literal = Syntax_Nodes::integer_data($partial->value);
		if (($partial->preparation() !== null) || ($partial_literal->preparation() !== null)) {
			throw new \LogicException('Failed preparation left partial facts attached to syntax');
		}
		return $expected;
	}

	/** Exercise the same duplicate-key/snapshot contract in PHP and the native compiler. */
	private static function collection_membership(): void
	{
		$first = Language_Types::integer(Model::$language_scope);
		$second = Language_Types::boolean(Model::$language_scope);
		$groups /** Key_Storage_List<type_definition> */ = new Key_Storage_List();
		$groups->add('1', $first);
		$groups->add('01', $second);
		$groups->add('1', $first);
		$all /** vector<type_definition> */ = $groups->items();
		$named /** vector<type_definition> */ = $groups->named('1');
		$other /** vector<type_definition> */ = $groups->named('01');
		$missing /** vector<type_definition> */ = $groups->named('missing');
		if ((q_count($all) !== 3) || (q_count($named) !== 2) || (q_count($other) !== 1) || (q_count($missing) !== 0)) {
			throw new \LogicException('Duplicate-key collection lost membership');
		}
		if (($all[0] !== $first) || ($all[1] !== $second) || ($all[2] !== $first) || ($named[0] !== $first) || ($named[1] !== $first) || ($other[0] !== $second)) {
			throw new \LogicException('Duplicate-key collection lost ordering or object identity');
		}
		$all[] = $second;
		$alias /** Key_Storage_List<type_definition> */ = $groups;
		$alias->add('1', $second);
		$after /** vector<type_definition> */ = $groups->items();
		if ((q_count($after) !== 4) || ($after[3] !== $second) || $groups->is_empty()) {
			throw new \LogicException('Collection alias or snapshot membership changed');
		}
	}
}
