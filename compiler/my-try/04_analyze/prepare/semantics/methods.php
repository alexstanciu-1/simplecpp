<?php

/* Instance context and member selection extend the ordinary callable boundary. */
namespace scpp\compiler;

final class Method_Preparation
{
	public static function receiver(collected_method $method): prepared_receiver
	{
		$receiver = new prepared_receiver();
		$receiver->declaration = $method;
		$type = Model::$type_catalog->registry()->canonical(Source_Types::definition($method->declaring_record()));
		$receiver->type = Model::$type_catalog->registry()->use($type->type_id());
		if (Type_Queries::is_class_handle($receiver->type)) {
			$receiver->type = Model::$type_catalog->registry()->use($type->type_id(), true);
		}
		return $receiver;
	}

	/** Ordinary parameter/return preparation remains owned by Declaration_Preparation. */
	public static function signature(method_node $syntax, prepared_source_callable $facts, preparation_context $context): void
	{
		if (string_byte_starts_with($syntax->name, '__') || $syntax->returns_reference) {
			throw new \RuntimeException('Special methods and reference-return methods are deferred');
		}
		foreach ($syntax->modifiers as $modifier)
		{
			if (($modifier !== declaration_modifier::public_access) && ($modifier !== declaration_modifier::private_access)) {
				throw new \RuntimeException('Instance methods require public or private visibility');
			}
			if ($modifier === declaration_modifier::private_access) { $facts->public_access = false; }
		}
		$parameters /** Storage<parameter_node> */ = $syntax->parameters;
		foreach ($parameters as $parameter)
		{
			if ($parameter->property_backed || $parameter->variadic || ($parameter->mode !== passing_mode::value)
				|| ($parameter->name === 'this')) {
				throw new \RuntimeException('Instance methods require ordinary fixed by-value parameters, not set: or $this');
			}
		}
		$method = object_cast($syntax->occurrence(), collected_method::class);
		$context->worker->require_declaration($context->owner, $method->declaring_record());
		$facts->receiver = self::receiver($method);
	}

	/** $this is usable only as a fixed member receiver, never as an escaping ordinary value. */
	public static function base(expression_node $syntax, preparation_context $context): prepared_expression
	{
		if ($syntax instanceof variable_reference_node)
		{
			$variable = object_cast($syntax, variable_reference_node::class);
			if ($variable->name === 'this')
			{
				$receiver = $context->receiver;
				if ($receiver === null) { throw new \RuntimeException('$this requires an instance body'); }
				$reference = new prepared_variable_reference();
				$reference->type = $receiver->type;
				$reference->declaration = $receiver->declaration;
				$reference->place = new prepared_place($receiver, null, true);
				$variable->set_preparation($reference);
				return $reference;
			}
		}
		return Expression_Preparation::prepare($syntax, $context);
	}

	public static function call(call_node $syntax, preparation_context $context): prepared_call
	{
		$base /** expression_node */ = $syntax->receiver;
		$value = self::base($base, $context);
		$storage = Type_Queries::record_storage($value->type);
		$payload = Type_Queries::record_payload($value->type);
		$record = Type_Queries::source_record($payload);
		if ($record === null) { throw new \RuntimeException('Instance call requires a class or struct receiver'); }
		if ($value->place !== null) { Reference_Preparation::require_writable($value->place); }
		$lifetime = method_receiver_lifetime::retain_shared;
		if ($storage !== record_storage_kind::shared)
		{
			if ($storage !== record_storage_kind::inline_value) {
				throw new \RuntimeException('Unique/weak method receivers require lifetime integration or an explicit shared lock');
			}
			if ($value->place === null) { throw new \RuntimeException('Inline method receiver requires stable whole storage'); }
			$place /** prepared_place */ = $value->place;
			if ($place->storage() instanceof prepared_receiver) { $lifetime = method_receiver_lifetime::current_instance; }
			else
			{
				if (($place->base() !== null) || !$place->borrowable()) {
					throw new \RuntimeException('Projected method receiver lifetime is deferred');
				}
				$lifetime = method_receiver_lifetime::borrow_inline;
			}
		}
		$context->worker->require_declaration($context->owner, $record);
		$methods /** Key_Storage_List<collected_method> */ = $record->syntax()->require_preparation()->methods;
		$matches /** vector<collected_method> */ = $methods->named($syntax->name);
		if ((q_count($matches) !== 1) || ($syntax->name === '__construct')) {
			throw new \RuntimeException('Instance call requires one ordinary method: ' . $syntax->name);
		}
		$method = $matches[0];
		Call_Preparation::require_signature($method, $context);
		if (!$method->callable_syntax()->require_preparation()->public_access && ($context->receiver_record !== $record)) {
			throw new \RuntimeException('Private method is not accessible here: ' . $syntax->name);
		}
		$facts = Call_Preparation::prepare($method, $syntax->arguments, $context);
		$facts->receiver = new prepared_method_receiver($payload, $lifetime);
		return $facts;
	}
}
