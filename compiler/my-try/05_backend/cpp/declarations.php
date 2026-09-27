<?php

/* Emit prepared signatures, value layouts and sequenced call boundaries. */
namespace scpp\compiler;

final class CPP_Declarations
{
	/** Type spelling also records the narrow runtime include needed by the output unit. */
	public static function type(type_definition $type, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		if ($mapping->header !== '') {
			$context->headers[$mapping->header] = true;
		}
		return $mapping->spelling;
	}

	/** Integer wrapper conversions are explicit; struct and other scalar copies keep their type. */
	public static function value(string $expression, type_definition $destination, cpp_generation_context $context): string
	{
		if ($destination->kind === type_kind::integer) {
			return 'static_cast<' . self::type($destination, $context) . '>((' . $expression . ').native_value())';
		}
		return $expression;
	}

	/** All signatures precede all bodies, including mutually recursive declarations. */
	public static function signature(function_structure $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_preparation();
		$parts = '';
		$separator = '';
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		foreach ($parameters as $parameter) {
			$entry = object_cast(weakref_get($parameter->declaration), collected_name::class);
			$reference = $parameter->mode === passing_mode::reference ? '&' : '';
			$parts .= $separator . self::type($parameter->type, $context) . $reference . ' ' . CPP_Generator::local_name($entry);
			$separator = ', ';
		}
		return self::type($facts->return_type, $context) . ' function_' . $syntax->occurrence()->token_index . '(' . $parts . ')';
	}

	/** Declaration hooks accumulate output outside main while leaving executable traversal unchanged. */
	public static function generate_function(function_structure $syntax, cpp_generation_context $context): string
	{
		$signature = self::signature($syntax, $context);
		$context->prototypes .= $signature . ";\n";
		$context->return_type = $syntax->require_preparation()->return_type;
		$body = CPP_Generator::generate_statements($syntax->body, $context);
		$context->return_type = null;
		$context->functions .= $signature . "\n{\n" . $body . "}\n\n";
		return '';
	}

	/** Complete nested value layouts before their users; reject a recursive by-value layout. */
	public static function generate_struct(struct_structure $syntax, cpp_generation_context $context): string
	{
		$key = '' . $syntax->occurrence()->token_index;
		if (isset($context->record_states[$key])) {
			if ($context->record_states[$key] === cpp_record_state::visiting) {
				throw new \RuntimeException('S2S recursive by-value struct layout is unsupported');
			}
			return '';
		}
		$context->record_states[$key] = cpp_record_state::visiting;
		$text = 'struct record_' . $key . "\n{\n";
		$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
		foreach ($fields->items() as $field)
		{
			$type = $field->type;
			if ($type->kind === type_kind::record) {
				$entry = object_cast($type->declaration, collected_name::class);
				self::generate_struct(Syntax_Nodes::struct_data($entry->node), $context);
			}
			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$text .= "\t" . self::type($type, $context) . ' field_' . $entry->token_index . ";\n";
		}
		$context->records .= $text . "};\n\n";
		$context->record_states[$key] = cpp_record_state::complete;
		return '';
	}

	/** Locals snapshot value arguments left-to-right; reference arguments keep the original place. */
	public static function generate_call(call_structure $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_preparation();
		$entry = object_cast(weakref_get($facts->declaration), collected_name::class);
		$parameters /** Storage<prepared_parameter> */ = $facts->signature->parameters;
		$arguments /** Storage<ast_node> */ = $syntax->arguments;
		$text = '([&]() -> ' . self::type($facts->type, $context) . " {\n";
		$names = '';
		$separator = '';
		foreach ($arguments as $index => $argument)
		{
			$parameter = $parameters[$index];
			// Generate nested calls before assigning the outer temporary name.
			$value = $argument->payload()->generate_cpp_expression($argument, $context);
			$name = 'argument_' . $context->next_temporary;
			$context->next_temporary++;
			$reference = $parameter->mode === passing_mode::reference ? '&' : '';
			if ($parameter->mode === passing_mode::value) {
				$value = self::value($value, $parameter->type, $context);
			}
			$text .= "\t" . self::type($parameter->type, $context) . $reference . ' ' . $name . ' = ' . $value . ";\n";
			$names .= $separator . $name;
			$separator = ', ';
		}
		$text .= "\treturn function_" . $entry->token_index . '(' . $names . ");\n}())";
		return $text;
	}

	/** Field identity comes from shared preparation, never a backend name lookup. */
	public static function generate_field_access(field_access_structure $syntax, cpp_generation_context $context): string
	{
		$entry = object_cast(weakref_get($syntax->require_preparation()->field->declaration), collected_name::class);
		$base = $syntax->base;
		return '(' . $base->payload()->generate_cpp_expression($base, $context) . ').field_' . $entry->token_index;
	}
}
