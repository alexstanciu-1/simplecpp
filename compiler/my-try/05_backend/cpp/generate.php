<?php

/* First C++ vertical slice: resolved straight-line scalar locals in a program entry. */
namespace scpp\compiler;

final class CPP_Generator
{
	/** Emit only after preparation succeeds; generated names cannot collide with C++ keywords. */
	public function generate(prepared_file $prepared): cpp_module
	{
		$context = new cpp_generation_context();
		$body = "\nint main()\n{\n";
		$child = $prepared->source->root->first_child();
		while ($child !== null) {
			$node /** ast_node */ = $child;
			$body .= $node->payload()->generate_cpp_statement($node, $context);
			$child = $node->next();
		}
		$body .= "\treturn 0;\n}\n";
		$text = '';
		foreach ($context->headers as $header => $used) {
			$text .= '#include "' . $header . '"' . "\n";
		}
		$text .= $body;
		$result = new cpp_module();
		$result->file_name = 'main.cpp';
		$result->text = $text;
		return $result;
	}

	/** Emit a prepared binding; only its initializer participates in expression generation. */
	public static function generate_binding(binding_structure $syntax, cpp_generation_context $context): string
	{
		$binding = $syntax->require_preparation();
		$initializer = object_cast($syntax->value, ast_node::class);
		$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
		$prefix = $binding->resolved_kind === binding_kind::declaration ? 'auto ' : '';
		return "\t" . $prefix . self::local_name($declaration) . ' = ' . $initializer->payload()->generate_cpp_expression($initializer, $context) . ";\n";
	}

	/** Program-entry returns use the native scalar value; a bare return exits successfully. */
	public static function generate_return(return_structure $syntax, cpp_generation_context $context): string
	{
		if ($syntax->expression === null) {
			return "\treturn 0;\n";
		}
		$expression /** ast_node */ = $syntax->expression;
		return "\treturn static_cast<int>((" . $expression->payload()->generate_cpp_expression($expression, $context) . ").native_value());\n";
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

	private static function local_name(collected_name $declaration): string
	{
		return 'local_' . $declaration->token_index;
	}
}
