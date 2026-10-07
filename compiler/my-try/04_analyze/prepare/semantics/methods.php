<?php

/* Instance context and member selection extend the ordinary callable boundary. */
namespace scpp\compiler;

final class Method_Preparation
{
	public static function receiver(collected_method $method): prepared_receiver
	{
		$record = object_cast($method->declaring_type(), collected_record::class);
		$receiver = new prepared_receiver();
		$receiver->declaration = $method;
		$type = Model::$type_catalog->registry()->canonical(Source_Types::definition($record));
		$receiver->type = Model::$type_catalog->registry()->use($type->type_id());
		if (Type_Queries::is_class_handle($receiver->type)) {
			$receiver->type = Model::$type_catalog->registry()->use($type->type_id(), true);
		}
		return $receiver;
	}

	/** Ordinary parameter/return preparation remains owned by Declaration_Preparation. */
	public static function signature(method_node $syntax, prepared_source_callable $facts, preparation_context $context): void
	{
		$destructor = $syntax->name === '__destruct';
		if (string_byte_starts_with($syntax->name, '__') && !$destructor) {
			throw new \RuntimeException('Special methods are deferred');
		}
		Member_Preparation::validate_modifiers($syntax->modifiers, true);
		$facts->visibility = Member_Preparation::visibility($syntax->modifiers);
		$facts->static_member = Member_Preparation::is_static($syntax->modifiers);
		$parameters /** Storage<parameter_node> */ = $syntax->parameters;
		if ($destructor) {
			if (!$parameters->is_empty() || ($syntax->return_type !== null) || $syntax->returns_reference
				|| $facts->static_member || ($facts->visibility !== member_visibility::public_access)) {
				throw new \RuntimeException('Destructor requires public instance access, no parameters and no declared return type/reference');
			}
		}
		foreach ($parameters as $parameter)
		{
			if ($parameter->property_backed || ($parameter->name === 'this')) {
				throw new \RuntimeException('Ordinary methods do not accept set: parameters or a parameter named $this');
			}
		}
		$method = object_cast($syntax->occurrence(), collected_method::class);
		$context->member_owner = $method->declaring_type();
		$context->worker->require_declaration($context->owner, $method->declaring_type());
		if (!$facts->static_member) { $facts->receiver = self::receiver($method); }
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
		if ($syntax->owner_type !== null) {
			$type = Member_Preparation::owner($syntax->owner_type, $context);
			$static_record = Type_Queries::source_record($type);
			if ($static_record === null) { throw new \RuntimeException('Static call requires a source class or struct'); }
			return self::select($syntax, $static_record, true, $context);
		}
		$base /** expression_node */ = $syntax->receiver;
		$value = self::base($base, $context);
		$storage = Type_Queries::record_storage($value->type);
		$payload = Type_Queries::record_payload($value->type);
		$record = Type_Queries::source_record($payload);
		if ($record === null) { throw new \RuntimeException('Instance call requires a class or struct receiver'); }
		if ($syntax->static_qualification) {
			$qualified = self::select($syntax, $record, true, $context);
			$qualified->evaluates_qualifier = true;
			return $qualified;
		}
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
		$facts = self::select($syntax, $record, false, $context);
		$facts->receiver = new prepared_method_receiver($payload, $lifetime);
		return $facts;
	}

	private static function select(call_node $syntax, collected_record $record, bool $static_call, preparation_context $context): prepared_call
	{
		$context->worker->require_declaration($context->owner, $record);
		$methods /** Key_Storage_List<collected_method> */ = $record->syntax()->require_preparation()->methods;
		$matches /** vector<collected_method> */ = $methods->named($syntax->name);
		if ((q_count($matches) !== 1) || ($syntax->name === '__construct') || ($syntax->name === '__destruct')) {
			throw new \RuntimeException('Member call requires one ordinary method: ' . $syntax->name);
		}
		$method = $matches[0];
		Call_Preparation::require_signature($method, $context);
		if ($method->callable_syntax()->require_preparation()->static_member !== $static_call) {
			throw new \RuntimeException('Static methods require :: and instance methods require ->');
		}
		if (!Member_Preparation::accessible($method->callable_syntax()->require_preparation()->visibility, $method->declaring_type(), $context)) {
			throw new \RuntimeException('Private method is not accessible here: ' . $syntax->name);
		}
		$facts = Call_Preparation::prepare($method, $syntax->arguments, $context);
		return $facts;
	}
}
