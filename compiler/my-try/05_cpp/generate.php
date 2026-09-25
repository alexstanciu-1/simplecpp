<?php

/* First C++ vertical slice: resolved straight-line integer locals in a program entry. */
namespace scpp\compiler;

final class CPP_Generator
{
	/** Emit only after preparation succeeds; generated names cannot collide with C++ keywords. */
	public function generate(prepared_file $prepared): cpp_module
	{
		$headers /** hash<bool> */ = [];
		foreach ($prepared->expressions as $expression) {
			$mapping = CPP_Types::representation($expression->type);
			$headers[$mapping->header] = true;
		}
		$text = '';
		foreach ($headers as $header => $used) {
			$text .= '#include "' . $header . '"' . "\n";
		}
		$text .= "\nint main()\n{\n";
		$children /** Storage<ast_node> */ = Syntax_Nodes::block_data($prepared->source->root)->children;
		foreach ($children as $node) {
			$text .= $this->statement($prepared, $node);
		}
		$text .= "\treturn 0;\n}\n";
		$result = new cpp_module();
		$result->file_name = 'main.cpp';
		$result->text = $text;
		return $result;
	}

	/** Binding classification and declaration identity come from preparation, not C++ heuristics. */
	private function statement(prepared_file $prepared, ast_node $node): string
	{
		if ($node->kind === node_kind::variable_binding_statement) {
			$binding = $prepared->bindings[(int) $node->token_index];
			$prefix = $binding->classification === binding_kind::declaration ? 'auto ' : '';
			return "\t" . $prefix . self::local_name($binding->declaration) . ' = ' . $this->expression($binding->initializer) . ";\n";
		}
		if ($node->kind === node_kind::return_statement)
		{
			$return_node = Syntax_Nodes::return_data($node);
			if ($return_node->expression === null) {
				return "\treturn 0;\n";
			}
			$syntax = object_cast($return_node->expression, ast_node::class);
			$expression = $prepared->expressions[(int) $syntax->token_index];
			return "\treturn static_cast<int>((" . $this->expression($expression) . ").native_value());\n";
		}
		throw new \RuntimeException('C++ statement emission is not implemented for this form');
	}

	/** Integer spelling supplies an exact native carrier before constructing the runtime value. */
	private function expression(prepared_expression $expression): string
	{
		if ($expression->syntax->kind === node_kind::integer_literal) {
			$mapping = CPP_Types::representation($expression->type);
			if ($mapping->literal !== cpp_literal_kind::signed_integer) {
				throw new \RuntimeException('C++ literal emission is not implemented for this type');
			}
			return 'static_cast<' . $mapping->spelling . '>(' . $expression->literal . 'LL)';
		}
		if ($expression->declaration !== null) {
			return self::local_name(object_cast($expression->declaration, collected_name::class));
		}
		throw new \RuntimeException('C++ expression lacks prepared lowering facts');
	}

	private static function local_name(collected_name $declaration): string
	{
		return 'local_' . $declaration->token_index;
	}
}
