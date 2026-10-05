<?php

/* Emit prepared signatures, value layouts and sequenced call boundaries. */
namespace scpp\compiler;

final class CPP_Declarations
{
	/** Type spelling also records the narrow runtime include needed by the output unit. */
	public static function type(canonical_type_use $type, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		CPP_Types::record_headers($mapping, $context);

		return $mapping->spelling;
	}

	/** Render only the operation selected by shared conversion preparation. */
	public static function conversion(string $expression, conversion_decision $decision, cpp_generation_context $context): string
	{
		if ($decision->operation === conversion_operation::identity) {
			return $expression;
		}
		if ($decision->operation === conversion_operation::text_conversion) {
			$context->headers['lang/php/support/php_string.hpp'] = true;
			return 'scpp::php::to_string(' . $expression . ')';
		}
		if ($decision->operation === conversion_operation::nullable_empty) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if (($decision->operation === conversion_operation::nullable_wrap)
			|| ($decision->operation === conversion_operation::boxed_wrap)) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if (($decision->operation === conversion_operation::result_wrap)
			|| ($decision->operation === conversion_operation::result_false)
			|| ($decision->operation === conversion_operation::result_true)) {
			return self::type($decision->target_type, $context) . '{' . $expression . '}';
		}
		if ($decision->operation === conversion_operation::nullable_extract) {
			return self::type($decision->target_type, $context) . '((' . $expression
				. ').require_value("nullable required-value extraction"))';
		}
		if ($decision->operation === conversion_operation::result_extract) {
			return self::type($decision->target_type, $context) . '((' . $expression
				. ').require_value("Result required-value extraction"))';
		}
		if ($decision->operation === conversion_operation::integer_value_cast) {
			return 'static_cast<' . self::type($decision->target_type, $context)
				. '>((' . $expression . ').native_value())';
		}
		if ($decision->operation === conversion_operation::boxed_extract) {
			$context->headers['scpp/cast.hpp'] = true;
			return 'scpp::required_cast<' . self::type($decision->target_type, $context)
				. '>(' . $expression . ')';
		}
		if ($decision->operation === conversion_operation::explicit_runtime_cast) {
			$context->headers['scpp/cast.hpp'] = true;
			return 'scpp::cast<' . self::type($decision->target_type, $context)
				. '>(' . $expression . ')';
		}

		throw new \RuntimeException('C++ conversion operation is not implemented');
	}

	/** Render the prepared condition operation using the established runtime authority. */
	public static function truthiness(string $expression, truthiness_decision $decision, cpp_generation_context $context): string
	{
		if ($decision->operation === truthiness_operation::boolean_identity) {
			return '(' . $expression . ').native_value()';
		}
		if (($decision->operation === truthiness_operation::scalar_condition)
			|| ($decision->operation === truthiness_operation::boxed_condition)
			|| ($decision->operation === truthiness_operation::error_condition)
			|| ($decision->operation === truthiness_operation::result_condition)
			|| ($decision->operation === truthiness_operation::nullable_condition)) {
			if ($decision->operation === truthiness_operation::nullable_condition) {
				$context->headers['scpp/cast.hpp'] = true;
			}
			$context->headers['lang/php/operators/conditional/condition_truthiness.hpp'] = true;
			return 'scpp::php::condition_truthy(' . $expression . ').native_value()';
		}
		throw new \LogicException('C++ truthiness operation is not implemented');
	}

	/** All signatures precede all bodies, including mutually recursive declarations. */
	public static function signature(function_node $syntax, cpp_generation_context $context, bool $include_defaults): string
	{
		$facts = $syntax->require_preparation();
		$parts = '';
		$separator = '';
		$parameters /** Storage<prepared_parameter> */ = $facts->parameters;
		foreach ($parameters as $storage) {
			$entry = object_cast(weakref_get($storage->declaration), collected_name::class);
			$parameter = $storage->contract;
			$reference = $parameter->mode === passing_mode::reference ? '&' : '';
			$parts .= $separator . self::type($parameter->type, $context) . $reference . ' ' . CPP_Generator::local_name($entry);
			if ($include_defaults && ($parameter->default_value !== null)) {
				$parts .= ' = ' . self::parameter_default($parameter->default_value, $context);
			}
			elseif ($include_defaults && ($parameter->element_type !== null)) {
				// An empty tail is implicit in source; this also permits preceding C++ defaults.
				$parts .= ' = ' . self::type($parameter->type, $context) . '{}';
			}
			$separator = ', ';
		}

		return self::type($facts->signature->return_type, $context) . ' ' . CPP_Callables::source_reference($syntax->occurrence()) . '(' . $parts . ')';
	}

	/** Declaration hooks accumulate output outside main while leaving executable traversal unchanged. */
	public static function generate_function(function_node $syntax, cpp_generation_context $context): string
	{
		$signature = self::signature($syntax, $context, false);
		$context->prototypes .= self::signature($syntax, $context, true) . ";\n";

		// The parser permits functions only at file scope; entry return handling resumes below.
		$context->return_type = $syntax->require_preparation()->signature->return_type;
		$body = CPP_Generator::generate_statements($syntax->body, $context);
		$context->return_type = null;

		$context->functions .= $signature . "\n{\n" . $body . "}\n\n";
		return '';
	}

	/** Consume normalized default facts; syntax and source tokens have no authority here. */
	private static function parameter_default(prepared_parameter_default $facts, cpp_generation_context $context): string
	{
		$value = $facts->value;
		$text = '';
		if ($value instanceof prepared_integer_literal) {
			$text = CPP_Generator::generate_integer(object_cast($value, prepared_integer_literal::class), $context);
		}
		elseif ($value instanceof prepared_float_literal) {
			$text = CPP_Generator::generate_float(object_cast($value, prepared_float_literal::class), $context);
		}
		elseif ($value instanceof prepared_boolean_literal) {
			$text = CPP_Generator::generate_boolean(object_cast($value, prepared_boolean_literal::class), $context);
		}
		elseif ($value instanceof prepared_string_literal) {
			$text = CPP_Generator::generate_string(object_cast($value, prepared_string_literal::class), $context);
		}
		else { throw new \LogicException('Prepared parameter default is not a scalar literal'); }
		return self::conversion($text, $facts->conversion, $context);
	}

	/** Complete nested value layouts before their users; reject a recursive by-value layout. */
	public static function generate_struct(struct_node $syntax, cpp_generation_context $context): string
	{
		$key = '' . $syntax->occurrence()->name;
		if (isset($context->record_states[$key])) {
			if ($context->record_states[$key] === cpp_record_state::visiting) {
				throw new \RuntimeException('S2S recursive by-value struct layout is unsupported');
			}
			return '';
		}

		// Mark before descending so a by-value cycle fails before a partial layout is emitted.
		$context->record_states[$key] = cpp_record_state::visiting;
		$text = 'struct ' . CPP_Generator::source_name('record', $key) . "\n{\n";

		$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
		foreach ($fields->items() as $field)
		{
			$type = $field->type;
			$source_record = Type_Queries::source_record($type);
			if ($source_record !== null) {
				$record_entry /** collected_struct */ = $source_record;
				if ($context->expand_records) {
					self::generate_struct($record_entry->syntax(), $context);
				}
			}

			$entry = object_cast(weakref_get($field->declaration), collected_name::class);
			$text .= "\t" . self::type($type, $context) . ' ' . CPP_Generator::source_name('field', $entry->name) . ";\n";
		}

		$context->records .= $text . "};\n\n";
		$context->record_states[$key] = cpp_record_state::complete;
		return '';
	}

	/** Locals snapshot value arguments left-to-right; reference arguments keep the original place. */
	public static function generate_call(call_node $syntax, cpp_generation_context $context): string
	{
		return self::selected_call($syntax->require_call_preparation(), $syntax->arguments, $context);
	}

	/** Source syntax and future factory consumers supply the same prepared call and ordered expressions. */
	public static function selected_call(prepared_call $facts, Storage $arguments /** Storage<expression_node> */,
		cpp_generation_context $context): string
	{
		$target = object_cast(weakref_get($facts->target), callable_target_i::class);
		$parameters /** Storage<callable_parameter> */ = $facts->signature->parameters;
		$prepared_arguments /** Storage<prepared_call_argument> */ = $facts->arguments;

		// The immediately invoked lambda sequences arguments and contains their temporaries.
		$text = '([&]() -> ' . self::type($facts->type, $context) . " {\n";
		$names = '';
		$separator = '';
		$packed_names = '';
		$packed_separator = '';
		foreach ($arguments as $index => $argument)
		{
			$parameter_index /** int */ = $facts->fixed_count;
			if ($index < $facts->fixed_count) { $parameter_index = $index; }
			$parameter = $parameters[$parameter_index];
			$argument_type = $parameter->element_type ?? $parameter->type;
			// Generate nested calls before assigning the outer temporary name.
			$value = (new CPP_Syntax($context))->expression($argument);
			$name = 'argument_' . $context->next_temporary;
			$context->next_temporary++;
			$reference = $parameter->mode === passing_mode::reference ? '&' : '';
			if ($parameter->mode === passing_mode::value) {
				$value = self::conversion(
					$value, $prepared_arguments[$index]->require_conversion(), $context);
			}

			$text .= "\t" . self::type($argument_type, $context) . $reference . ' ' . $name . ' = ' . $value . ";\n";
			if ($index < $facts->fixed_count) {
				$names .= $separator . $name;
				$separator = ', ';
			}
			else {
				$packed_names .= $packed_separator . $name;
				$packed_separator = ', ';
			}
		}
		// Semantic defaults do not depend on defaults being repeated in a runtime C++ declaration.
		for ($index = q_count($arguments); $index < $facts->fixed_count; $index++) {
			$default_value /** prepared_parameter_default */ = $parameters[$index]->default_value;
			$names .= $separator . self::parameter_default($default_value, $context);
			$separator = ', ';
		}
		if ($facts->variadic_parameter !== null) {
			$pack /** callable_parameter */ = $facts->variadic_parameter;
			$names .= $separator . self::type($pack->type, $context) . '{' . $packed_names . '}';
		}

		$text .= "\treturn " . CPP_Callables::reference($target, $context) . '(' . $names . ");\n}())";
		return $text;
	}

	/** Field identity comes from shared preparation, never a backend name lookup. */
	public static function generate_field_access(field_access_node $syntax, cpp_generation_context $context): string
	{
		$entry = object_cast(weakref_get($syntax->require_field_access_preparation()->field->declaration), collected_name::class);
		$base = $syntax->base;
		return '(' . (new CPP_Syntax($context))->expression($base) . ').' . CPP_Generator::source_name('field', $entry->name);
	}
}
