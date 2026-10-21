<?php
namespace scpp\compiler;

/** Cooperative async contracts are separate from thread-backed task transfer. */
final class Async_Preparation
{
	public static function task_type(canonical_type_use $value): canonical_type_use
	{
		$registry = Model::$type_catalog->registry();
		$definition = $registry->define_async_task($value);
		return $registry->use($registry->canonical($definition)->type_id());
	}

	public static function signature(callable_signature_syntax $syntax, callable_signature $signature): void
	{
		if (!$syntax->asynchronous) { return; }
		if ($signature->reference_return !== null) { throw new \RuntimeException('Async functions cannot return references'); }
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		foreach ($parameters as $parameter) {
			if ($parameter->mode !== passing_mode::value) {
				throw new \RuntimeException('Async function parameters must own their values across suspension');
			}
		}
		$signature->async_value_type = $signature->return_type;
		$signature->return_type = self::task_type($signature->return_type);
	}

	public static function await_value(expression_node $operand, preparation_context $context, bool $synchronous = false): prepared_await
	{
		$value = Expression_Preparation::prepare_for_consumer($operand, $context, null, expression_consumer::await_operand);
		$definition = Type_Queries::canonical($value->type)->definition();
		if (!($definition instanceof async_task_type_definition)) { throw new \RuntimeException('Await requires an async task'); }
		$facts = new prepared_await();
		$facts->task_type = $value->type;
		$facts->type = object_cast($definition, async_task_type_definition::class)->value_type;
		$facts->operation = ($context->asynchronous && !$synchronous) ? await_operation::suspend : await_operation::synchronous_wait;
		if ($facts->operation === await_operation::suspend) { $context->suspension_count++; }
		$facts->value_origin = expression_value_origin::owned_call_result;
		return $facts;
	}

	public static function call(compiler_function_definition $definition,
		Storage $arguments /** Storage<expression_node> */, preparation_context $context): prepared_call
	{
		if ($arguments->count() !== 1) { throw new \RuntimeException($definition->name() . ' requires exactly one argument'); }
		$operation = $definition->kind();
		$specialization = new prepared_async_call($operation);
		$signature = new callable_signature();
		if ($operation === compiler_function_kind::async_wait) {
			$specialization->awaited = self::await_value($arguments[0], $context, true);
			$signature->return_type = $specialization->awaited->type;
			// Await consumes its task directly; it has no ordinary copy/conversion boundary.
			$facts = new prepared_call();
			$facts->target = $definition;
			$facts->signature = $signature;
			$facts->type = $signature->return_type;
			$facts->value_origin = expression_value_origin::owned_call_result;
			$facts->specialization = $specialization;
			return $facts;
		}
		if (!$context->asynchronous) { throw new \RuntimeException('async_sleep_ms requires an async function'); }
		$signature->return_type = self::task_type(Language_Types::no_value(Model::$language_scope));
		$parameter = new callable_parameter();
		$parameter->type = $context->integer;
		$parameter->mode = passing_mode::value;
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		$parameters->append($parameter);
		$facts = Call_Preparation::admit($definition, $signature, $arguments, $context, false);
		$facts->specialization = $specialization;
		return $facts;
	}
}
