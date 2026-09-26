<?php

/* First C++ vertical slice: resolved straight-line integer locals in a program entry. */
namespace scpp\compiler;

final class CPP_Generator
{
	private array $headers /** hash<bool> */ = [];

	/** Emit only after preparation succeeds; generated names cannot collide with C++ keywords. */
	public function generate(prepared_file $prepared): cpp_module
	{
		$empty /** hash<bool> */ = [];
		$this->headers = $empty;
		$body = "\nint main()\n{\n";
		$child = $prepared->source->root->first_child();
		while ($child !== null) {
			$node /** ast_node */ = $child;
			$body .= $this->statement($node);
			$child = $node->next();
		}
		$body .= "\treturn 0;\n}\n";
		$text = '';
		foreach ($this->headers as $header => $used) {
			$text .= '#include "' . $header . '"' . "\n";
		}
		$text .= $body;
		$result = new cpp_module();
		$result->file_name = 'main.cpp';
		$result->text = $text;
		return $result;
	}

	/** Binding classification and declaration identity come from preparation, not C++ heuristics. */
	private function statement(ast_node $node): string
	{
		if ($node->kind() === node_kind::variable_binding_statement)
		{
			$binding_data = Syntax_Nodes::binding_data($node);
			$binding = $binding_data->require_preparation();
			$initializer = object_cast($binding_data->value, ast_node::class);
			$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
			$prefix = $binding->resolved_kind === binding_kind::declaration ? 'auto ' : '';
			return "\t" . $prefix . self::local_name($declaration) . ' = ' . $this->expression($initializer) . ";\n";
		}
		if ($node->kind() === node_kind::return_statement)
		{
			$return_data = Syntax_Nodes::return_data($node);
			if ($return_data->expression === null) {
				return "\treturn 0;\n";
			}
			$syntax /** ast_node */ = $return_data->expression;
			return "\treturn static_cast<int>((" . $this->expression($syntax) . ").native_value());\n";
		}
		throw new \RuntimeException('C++ statement emission is not implemented for this form');
	}

	/** Integer spelling supplies an exact native carrier before constructing the runtime value. */
	private function expression(ast_node $node): string
	{
		$expression = Syntax_Nodes::expression_data($node)->require_preparation();
		$mapping = CPP_Types::representation($expression->type);
		$this->headers[$mapping->header] = true;
		if ($node->kind() === node_kind::integer_literal) {
			if ($mapping->literal !== cpp_literal_kind::signed_integer) {
				throw new \RuntimeException('C++ literal emission is not implemented for this type');
			}
			return 'static_cast<' . $mapping->spelling . '>(' . $expression->literal . 'LL)';
		}
		$declaration = weakref_get($expression->declaration);
		if ($declaration !== null) {
			$target /** collected_name */ = $declaration;
			return self::local_name($target);
		}
		throw new \RuntimeException('C++ expression lacks prepared lowering facts');
	}

	private static function local_name(collected_name $declaration): string
	{
		return 'local_' . $declaration->token_index;
	}
}
