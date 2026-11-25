<?php
namespace scpp\compiler;

/** Typed source protocol; neither runtime-container registration nor synthetic source members. */
final class Iterator_Preparation
{
	public static function method_name(string $name): bool
	{
		return ($name === 'current') || ($name === 'key') || ($name === 'valid') || ($name === 'next') || ($name === 'rewind');
	}
	private static function method(collected_member_owner $owner, string $name, preparation_context $context): collected_method
	{
		$matches = Inherited_Lookup::members($owner, $name, preparation_lookup_kind::method_name, $context);
		if (q_count($matches) !== 1) { throw new \RuntimeException('Iterator requires one public instance method: ' . $name); }
		$method = object_cast($matches[0]->member, collected_method::class);
		$signature = Call_Preparation::require_signature($method, $context);
		$facts = $method->callable_syntax()->require_preparation();
		$parameters /** Storage<callable_parameter> */ = $signature->parameters;
		if ($facts->static_member || ($facts->visibility !== member_visibility::public_access)
			|| !$parameters->is_empty() || ($signature->reference_return !== null)) {
			throw new \RuntimeException('Iterator methods require public zero-argument instance value signatures: ' . $name);
		}
		return $method;
	}

	public static function protocol(collected_member_owner $owner, preparation_context $context): prepared_iterator_protocol
	{
		$facts = new prepared_iterator_protocol();
		$facts->current = weak(self::method($owner, 'current', $context));
		$facts->key = weak(self::method($owner, 'key', $context));
		$facts->valid = weak(self::method($owner, 'valid', $context));
		$facts->next = weak(self::method($owner, 'next', $context));
		$facts->rewind = weak(self::method($owner, 'rewind', $context));
		$facts->key_type = weakref_get($facts->key)->signature()->return_type;
		$facts->value_type = weakref_get($facts->current)->signature()->return_type;
		if (!weakref_get($facts->valid)->signature()->return_type->matches($context->boolean)
			|| (Type_Queries::canonical(weakref_get($facts->next)->signature()->return_type)->family() !== type_family::no_value)
			|| (Type_Queries::canonical(weakref_get($facts->rewind)->signature()->return_type)->family() !== type_family::no_value)) {
			throw new \RuntimeException('Iterator requires valid(): bool, next(): void and rewind(): void');
		}
		Type_Preparation::require_value_type($facts->key_type);
		Type_Preparation::require_value_type($facts->value_type);
		Capability_Preparation::require_copy($facts->key_type, $context);
		Capability_Preparation::require_copy($facts->value_type, $context);
		return $facts;
	}

	private static function call(collected_method $method, canonical_type_use $receiver, preparation_context $context): prepared_call
	{
		$arguments /** Storage<expression_node> */ = new Storage();
		$call = Call_Preparation::prepare($method, $arguments, $context);
		$call->receiver = new prepared_method_receiver($receiver, method_receiver_lifetime::retain_shared);
		return $call;
	}

	public static function calls(prepared_iterator_protocol $protocol, canonical_type_use $receiver, preparation_context $context): prepared_iterator_calls
	{
		$facts = new prepared_iterator_calls();
		$facts->current = self::call(weakref_get($protocol->current), $receiver, $context);
		$facts->key = self::call(weakref_get($protocol->key), $receiver, $context);
		$facts->valid = self::call(weakref_get($protocol->valid), $receiver, $context);
		$facts->next = self::call(weakref_get($protocol->next), $receiver, $context);
		$facts->rewind = self::call(weakref_get($protocol->rewind), $receiver, $context);
		return $facts;
	}

	public static function same(?prepared_iterator_protocol $old, ?prepared_iterator_protocol $current): bool
	{
		if ($old === null) { return $current === null; }
		if ($current === null) { return false; }
		return $old->key_type->matches($current->key_type) && $old->value_type->matches($current->value_type)
			&& (weakref_get($old->current) === weakref_get($current->current))
			&& (weakref_get($old->key) === weakref_get($current->key))
			&& (weakref_get($old->valid) === weakref_get($current->valid))
			&& (weakref_get($old->next) === weakref_get($current->next))
			&& (weakref_get($old->rewind) === weakref_get($current->rewind));
	}
}
