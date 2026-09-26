<?php

/* First C++ vertical slice: resolved straight-line integer locals in a program entry. */
namespace scpp\compiler;

final class CPP_Generator
{
	private array $headers /** hash<bool> */ = [];

	/** Emit only after preparation succeeds; generated names cannot collide with C++ keywords. */
	public function generate(prepared_file $prepared): cpp_module
	{
		$this->headers = [];
		$body = "\nint main()\n{\n";
		$children /** Storage<ast_node> */ = Syntax_Nodes::block_data($prepared->source->root)->children;
		foreach ($children as $node) {
			$body .= $this->statement($node);
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
		if ($node->kind === node_kind::variable_binding_statement) {
			$binding = Prepared_Nodes::binding($node);
			$initializer = object_cast(Syntax_Nodes::binding_data($node)->value, ast_node::class);
			$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
			$prefix = $binding->classification === binding_kind::declaration ? 'auto ' : '';
			return "\t" . $prefix . self::local_name($declaration) . ' = ' . $this->expression($initializer) . ";\n";
		}
		if ($node->kind === node_kind::return_statement)
		{
			$return_node = Syntax_Nodes::return_data($node);
			if ($return_node->expression === null) {
				return "\treturn 0;\n";
			}
			$syntax = object_cast($return_node->expression, ast_node::class);
			return "\treturn static_cast<int>((" . $this->expression($syntax) . ").native_value());\n";
		}
		throw new \RuntimeException('C++ statement emission is not implemented for this form');
	}

	/** Integer spelling supplies an exact native carrier before constructing the runtime value. */
	private function expression(ast_node $node): string
	{
		$expression = Prepared_Nodes::expression($node);
		$mapping = CPP_Types::representation($expression->type);
		$this->headers[$mapping->header] = true;
		if ($node->kind === node_kind::integer_literal) {
			if ($mapping->literal !== cpp_literal_kind::signed_integer) {
				throw new \RuntimeException('C++ literal emission is not implemented for this type');
			}
			return 'static_cast<' . $mapping->spelling . '>(' . $expression->literal . 'LL)';
		}
		$declaration = weakref_get($expression->declaration);
		if ($declaration !== null) {
			return self::local_name(object_cast($declaration, collected_name::class));
		}
		throw new \RuntimeException('C++ expression lacks prepared lowering facts');
	}

	private static function local_name(collected_name $declaration): string
	{
		return 'local_' . $declaration->token_index;
	}
}
