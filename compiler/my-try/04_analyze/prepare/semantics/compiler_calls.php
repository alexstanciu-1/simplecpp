<?php
namespace scpp\compiler;

/** Select compiler-provided contracts after normal lexical lookup, without parser special cases. */
final class Compiler_Call_Preparation
{
	public static function prepare(compiler_function_definition $definition,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context): prepared_call
	{
		if ($definition->kind() !== compiler_function_kind::enum_name) {
			throw new \LogicException('Unsupported compiler function');
		}
		if ($arguments->count() !== 1) {
			throw new \RuntimeException('S2S enum_name requires exactly one enum argument');
		}
		$argument = Expression_Preparation::prepare_for_consumer($arguments[0], $context, null, expression_consumer::consuming_argument);
		if (!Type_Queries::is_enum($argument->type)) {
			throw new \RuntimeException('S2S enum_name requires an enum argument');
		}
		$entry = object_cast(Type_Queries::source_declaration($argument->type), collected_enum::class);
		$context->worker->require_declaration($context->owner, $entry);
		$specialization = new prepared_enum_name_call();
		$specialization->enumeration = $entry->enum_syntax()->require_preparation();
		$specialization->result_type = $context->string_type;
		$signature = new callable_signature();
		$signature->return_type = $specialization->result_type;
		$parameter = new callable_parameter();
		$parameter->type = $argument->type;
		$parameter->mode = passing_mode::value;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$parameters->append($parameter);
		$facts = Call_Preparation::admit($definition, $signature, $arguments, $context, true);
		$facts->specialization = $specialization;
		return $facts;
	}
}
