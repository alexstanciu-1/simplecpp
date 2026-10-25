<?php
namespace scpp\compiler;

/** Select compiler-provided contracts after normal lexical lookup, without parser special cases. */
final class Compiler_Call_Preparation
{
	/** Compile-time type input, not a runtime string or reflection object. */
	private static function type_argument(expression_node $syntax, preparation_context $context): canonical_type_use
	{
		if ($syntax instanceof static_member_reference_node) {
			$marker = object_cast($syntax, static_member_reference_node::class);
			if ($marker->member_name === 'class') {
				return Type_Preparation::type($marker->require_type_owner(), $context);
			}
		}
		throw new \RuntimeException('S2S compiler type argument requires Type::class');
	}

	public static function prepare(compiler_function_definition $definition,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context): prepared_call
	{
		$operation = $definition->kind();
		if (($operation === compiler_function_kind::async_sleep) || ($operation === compiler_function_kind::async_wait)) {
			return Async_Preparation::call($definition, $arguments, $context);
		}
		if (($operation === compiler_function_kind::layout_sizeof) || ($operation === compiler_function_kind::layout_alignof)
			|| ($operation === compiler_function_kind::layout_offsetof) || ($operation === compiler_function_kind::layout_field_sizeof)) {
			return Layout_Query_Preparation::prepare($definition, $arguments, $context);
		}
		$from_value = $operation === compiler_function_kind::enum_from_value;
		$source_index = $from_value ? 1 : 0;
		if ($arguments->count() !== $source_index + 1) {
			throw new \RuntimeException('S2S ' . $definition->name() . ($from_value
				? ' requires Type::class and one integer argument' : ' requires exactly one enum argument'));
		}
		$enum_type /** canonical_type_use */;
		$argument /** prepared_expression */;
		if ($from_value) {
			$enum_type = self::type_argument($arguments[0], $context);
			$argument = Expression_Preparation::prepare_for_consumer($arguments[$source_index], $context, null, expression_consumer::consuming_argument);
		}
		else {
			$argument = Expression_Preparation::prepare_for_consumer($arguments[$source_index], $context, null, expression_consumer::consuming_argument);
			$enum_type = $argument->type;
		}
		if (!Type_Queries::is_enum($enum_type)) {
			throw new \RuntimeException('S2S ' . $definition->name() . ' requires an enum argument or target');
		}
		if ($from_value) {
			if (!(Type_Queries::canonical($argument->type)->definition() instanceof integer_type_definition)) {
				throw new \RuntimeException('S2S enum_from_value requires an integer value');
			}
		}
		$entry = object_cast(Type_Queries::source_declaration($enum_type), collected_enum::class);
		$context->worker->require_declaration($context->owner, $entry);
		$specialization = new prepared_enum_call($operation);
		$specialization->enumeration = $entry->enum_syntax()->require_preparation();
		$specialization->input_type = $argument->type;
		$specialization->result_type = $context->string_type;
		if ($operation === compiler_function_kind::enum_value) {
			$specialization->result_type = $specialization->enumeration->backing_type ?? $context->integer;
		}
		elseif ($from_value) { $specialization->result_type = $enum_type; }
		$signature = new callable_signature();
		$signature->return_type = $specialization->result_type;
		$parameter = new callable_parameter();
		$parameter->type = $argument->type;
		$parameter->mode = passing_mode::value;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$parameters->append($parameter);
		$runtime_arguments /** Storage<expression_node> */ = new Storage();
		$runtime_arguments->append($arguments[$source_index]);
		$facts = Call_Preparation::admit($definition, $signature, $runtime_arguments, $context, true);
		$admitted /** Storage<prepared_call_argument> */ = $facts->arguments;
		$admitted[0]->source_index = $source_index;
		$facts->specialization = $specialization;
		return $facts;
	}
}
