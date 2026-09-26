<?php

/* Portable proof shared by PHP and the converted native compiler harness. */
namespace scpp\compiler;

final class S2S_Proof
{
	/** Exercise language identity, inference, reassignment, source purity and regeneration. */
	public static function run(): string
	{
		Compiler_Lifecycle::reset();
		$input_module = new module();
		$input_module->path = '/s2s-proof';
		$source = new file();
		$source->path = '/s2s-proof/main.phs';
		$source->content = '$a = 10; $b int = $a; $a = 12; return $b;';
		$inputs /** Storage<file> */ = $input_module->files;
		$inputs->append($source);
		Model::$modules[] = $input_module;
		$compiler = new Compiler();
		$compiler->exec_cpp();

		$prepared_files /** Storage<prepared_file> */ = Model::$prepared_files;
		$prepared = $prepared_files[0];
		$children /** Storage<ast_node> */ = Syntax_Nodes::block_data($prepared->source->root)->children;
		$first_node = object_cast($children[0], variable_binding_statement_node::class);
		$assignment_node = object_cast($children[2], variable_binding_statement_node::class);
		$literal_node = object_cast(Syntax_Nodes::binding_data($first_node)->value, integer_literal_node::class);
		$reference_node = object_cast(Syntax_Nodes::binding_data($children[1])->value, variable_reference_node::class);
		$first = object_cast($first_node, variable_binding_statement_node::class)->require_preparation();
		$assignment = object_cast($assignment_node, variable_binding_statement_node::class)->require_preparation();
		$literal = object_cast($literal_node, expression_node::class)->require_preparation();
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
		if (Syntax_Nodes::binding_data($first_node)->syntax_kind !== binding_kind::unresolved) {
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
		if (object_cast($first_node, variable_binding_statement_node::class)->require_preparation() === $first) {
			throw new \LogicException('Repeated preparation reused stale node facts');
		}
		Compiler_Lifecycle::reset_cpp();
		if (($first_node->preparation() !== null) || ($assignment_node->preparation() !== null) || ($literal_node->preparation() !== null) || ($reference_node->preparation() !== null)) {
			throw new \LogicException('Specialized nodes did not clear their prepared facts');
		}
		$compiler->cpp();
		$current_inputs /** Storage<file> */ = $input_module->files;
		$current_inputs[0]->content = '$a = 13; return $a;';
		$paths /** vector<string> */ = ['/s2s-proof/main.phs'];
		$compiler->update_cpp($paths);
		$outputs = Model::$cpp_files;
		if (($outputs[0]->text === $expected) || ($old_output->text !== $expected)) {
			throw new \LogicException('Update damaged old output or failed to regenerate');
		}
		if (($first_node->preparation() !== null) || ($literal_node->preparation() !== null) || ($reference_node->preparation() !== null)) {
			throw new \LogicException('Incremental replacement left facts on the detached old tree');
		}
		$current_inputs[0]->content = '$a = 1; $b = $missing;';
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
		$failed_syntax /** Storage<parsed_file> */ = Model::$syntax_files;
		$failed_children /** Storage<ast_node> */ = Syntax_Nodes::block_data($failed_syntax[0]->root)->children;
		$partial = object_cast($failed_children[0], variable_binding_statement_node::class);
		$partial_literal = object_cast(Syntax_Nodes::binding_data($partial)->value, integer_literal_node::class);
		if (($partial->preparation() !== null) || ($partial_literal->preparation() !== null)) {
			throw new \LogicException('Failed preparation left partial facts attached to syntax');
		}
		return $expected;
	}
}
