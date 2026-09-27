<?php

/* Emit prepared declarations and straight-line bodies using specialization dispatch. */
namespace scpp\compiler;

final class CPP_Generator
{
	/** Emit only after preparation succeeds; generated names cannot collide with C++ keywords. */
	public function generate(prepared_file $prepared): cpp_module
	{
		$context = new cpp_generation_context();
		$body = "\nint main()\n{\n";
		$body .= self::generate_statements($prepared->source->root, $context);
		$body .= "\treturn 0;\n}\n";

		// Emit only headers actually requested by the generated expressions.
		$text = '';
		foreach ($context->headers as $header => $used) {
			$text .= '#include "' . $header . '"' . "\n";
		}
		$text .= $context->records . $context->prototypes . $context->functions . $body;

		$result = new cpp_module();
		$result->file_name = 'main.cpp';
		$result->text = $text;
		return $result;
	}

	/** The worker visits executable children once; declaration hooks collect separate output sections. */
	public static function generate_statements(ast_node $body, cpp_generation_context $context): string
	{
		$text = '';
		$child = $body->first_child();
		while ($child !== null) {
			$node /** ast_node */ = $child;
			$text .= $node->payload()->generate_cpp_statement($node, $context);
			$child = $node->next();
		}
		return $text;
	}

	/** Prepared declarations and member targets share the same typed assignment boundary. */
	public static function generate_binding(binding_structure $syntax, cpp_generation_context $context): string
	{
		$binding = $syntax->require_preparation();
		$prefix = $binding->resolved_kind === binding_kind::declaration ? 'auto ' : '';
		$name = '';
		if ($syntax->target !== null) {
			$target /** ast_node */ = $syntax->target;
			$name = $target->payload()->generate_cpp_expression($target, $context);
		}
		else {
			$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
			$name = self::local_name($declaration);
		}
		if ($syntax->value === null) {
			return "\t" . CPP_Declarations::type($binding->type, $context) . ' ' . $name . ";\n";
		}
		$initializer /** ast_node */ = $syntax->value;
		$value = $initializer->payload()->generate_cpp_expression($initializer, $context);
		if (($syntax->type_syntax !== null) || ($binding->resolved_kind === binding_kind::assignment)) {
			$value = CPP_Declarations::value($value, $binding->type, $context);
		}
		return "\t" . $prefix . $name . ' = ' . $value . ";\n";
	}

	/** Function returns retain their declared value type; entry returns become native exit codes. */
	public static function generate_return(return_structure $syntax, cpp_generation_context $context): string
	{
		if ($syntax->expression === null) {
			return $context->return_type === null ? "\treturn 0;\n" : "\treturn;\n";
		}
		$expression /** ast_node */ = $syntax->expression;
		$value = $expression->payload()->generate_cpp_expression($expression, $context);
		if ($context->return_type !== null) {
			$type /** type_definition */ = $context->return_type;
			return "\treturn " . CPP_Declarations::value($value, $type, $context) . ";\n";
		}
		return "\treturn static_cast<int>((" . $value . ").native_value());\n";
	}

	public static function generate_expression_statement(expression_statement_structure $syntax, cpp_generation_context $context): string
	{
		$expression = $syntax->expression;
		return "\t" . $expression->payload()->generate_cpp_expression($expression, $context) . ";\n";
	}

	/** Emit exact signed integer magnitude using its canonical representation. */
	public static function generate_integer(prepared_integer_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::signed_integer) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}
		return 'static_cast<' . $mapping->spelling . '>(' . $literal->decimal . 'LL)';
	}

	/** Preserve decimal spelling until the target toolchain performs floating conversion. */
	public static function generate_float(prepared_float_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::floating) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}
		return 'static_cast<' . $mapping->spelling . '>(' . $literal->decimal . ')';
	}

	/** Boolean source normalization is already complete before backend spelling. */
	public static function generate_boolean(prepared_boolean_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::boolean) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}
		$spelling = $literal->value ? 'true' : 'false';
		return 'static_cast<' . $mapping->spelling . '>(' . $spelling . ')';
	}

	public static function generate_reference(prepared_variable_reference $reference, cpp_generation_context $context): string
	{
		$target = object_cast(weakref_get($reference->declaration), collected_name::class);
		return self::local_name($target);
	}

	public static function local_name(collected_name $declaration): string
	{
		return 'local_' . $declaration->token_index;
	}
}
