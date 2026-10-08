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
		if (Type_Queries::is_nominal_handle($receiver->type)) {
			$receiver->type = Model::$type_catalog->registry()->use($type->type_id(), true);
		}
		return $receiver;
	}

	/** Ordinary parameter/return preparation remains owned by Declaration_Preparation. */
	public static function signature(method_node $syntax, prepared_source_callable $facts, preparation_context $context): void
	{
		$method = object_cast($syntax->occurrence(), collected_method::class);
		$interface_method = $method->declaring_type() instanceof collected_interface;
		$destructor = $syntax->name === '__destruct';
		if (string_byte_starts_with($syntax->name, '__') && !$destructor) {
			throw new \RuntimeException('Special methods are deferred');
		}
		$ordinary /** vector<declaration_modifier> */ = [];
		foreach ($syntax->modifiers as $modifier) {
			if ($modifier === declaration_modifier::abstract_declaration) { $facts->abstract_method = true; }
			elseif ($modifier === declaration_modifier::final_declaration) { $facts->final_method = true; }
			elseif ($modifier === declaration_modifier::override_declaration) { $facts->override_assertion = true; }
			else { $ordinary[] = $modifier; }
		}
		Member_Preparation::validate_modifiers($ordinary, true);
		$facts->visibility = Member_Preparation::visibility($syntax->modifiers);
		$facts->static_member = Member_Preparation::is_static($syntax->modifiers);
		if ($interface_method) {
			if ($syntax->has_parsed_body() || $facts->static_member || $facts->final_method
				|| ($facts->visibility !== member_visibility::public_access) || $destructor) {
				throw new \RuntimeException('Interface methods require public bodyless instance contracts');
			}
			$facts->abstract_method = true;
		}
		if ($facts->abstract_method === $syntax->has_parsed_body()) {
			throw new \RuntimeException('Abstract methods require no body; concrete methods require a body');
		}
		if ($facts->abstract_method && ($facts->static_member || $facts->final_method || ($facts->visibility === member_visibility::private_access))) {
			throw new \RuntimeException('Abstract methods must be non-final public/protected instance methods');
		}
		if ($destructor && ($facts->abstract_method || $facts->final_method || $facts->override_assertion)) {
			throw new \RuntimeException('Destructor abstract/final/override modifiers are deferred');
		}
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
		$context->member_owner = $method->declaring_type();
		if ($interface_method) {
			$context->worker->require_relationships($method->declaring_type(), $context);
			return;
		}
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
			if ($syntax->owner_type->name === 'parent') {
				return self::select($syntax, $static_record, true, $context, true);
			}
			return self::select($syntax, $static_record, true, $context);
		}
		$base /** expression_node */ = $syntax->receiver;
		$value = self::base($base, $context);
		$storage = Type_Queries::record_storage($value->type);
		$payload = Type_Queries::record_payload($value->type);
		$declaration = Type_Queries::source_declaration($payload);
		if (!($declaration instanceof collected_member_owner)) { throw new \RuntimeException('Instance call requires a class, struct or interface receiver'); }
		$record = object_cast($declaration, collected_member_owner::class);
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

	private static function select(call_node $syntax, collected_member_owner $record, bool $static_call, preparation_context $context,
		bool $parent_call = false): prepared_call
	{
		if ($record instanceof collected_record) { $context->worker->require_declaration($context->owner, $record); }
		else { $context->worker->require_type_contract($record, $context); }
		$matches = Inherited_Lookup::members($record, $syntax->name, preparation_lookup_kind::method_name, $context);
		if ((q_count($matches) === 0) && !$static_call && !$parent_call) {
			// Call contracts are not implementations: keep this fallback out of ordinary inherited lookup.
			$contracts = $context->worker->require_type_contract($record, $context);
			foreach ($contracts->interface_requirements as $requirement) {
				if ($requirement->name === $syntax->name) {
					$matches[] = new inherited_member_candidate($requirement->declaring_type(), $requirement);
				}
			}
		}
		if ((q_count($matches) !== 1) || ($syntax->name === '__construct') || ($syntax->name === '__destruct')) {
			throw new \RuntimeException('Member call requires one ordinary method: ' . $syntax->name);
		}
		$method = object_cast($matches[0]->member, collected_method::class);
		Call_Preparation::require_signature($method, $context);
		if ($parent_call && $method->callable_syntax()->require_preparation()->abstract_method) {
			throw new \RuntimeException('Cannot explicitly call an abstract parent method');
		}
		if ($parent_call) { $static_call = $method->callable_syntax()->require_preparation()->static_member; }
		if ($method->callable_syntax()->require_preparation()->static_member !== $static_call) {
			throw new \RuntimeException('Static methods require :: and instance methods require ->');
		}
		if (!Member_Preparation::accessible($method->callable_syntax()->require_preparation()->visibility, $method->declaring_type(), $context)) {
			if ($method->callable_syntax()->require_preparation()->visibility === member_visibility::protected_access) {
				throw new \RuntimeException('Protected method is not accessible here: ' . $syntax->name);
			}
			throw new \RuntimeException('Private method is not accessible here: ' . $syntax->name);
		}
		if (!$static_call) {
			if ($parent_call) {
				if ($context->receiver === null) { throw new \RuntimeException('Parent instance call requires an initialized $this'); }
				$record = object_cast($context->member_owner, collected_record::class);
			}
			Member_Preparation::require_instance_access($method->callable_syntax()->require_preparation()->visibility,
				$method->declaring_type(), $record, $context);
		}
		// Select an implementation only after lexical access and dispatch admission.
		if ($static_call) {
			$called = object_cast($record, collected_record::class);
			if (($syntax->owner_type !== null) && ($context->called_type !== null)) {
				if (($syntax->owner_type->name === 'self') || ($syntax->owner_type->name === 'parent')) {
					$called = $context->called_type;
				}
			}
			$method = $context->worker->require_callable_instance($context->owner, $method, $called);
		}
		$facts = Call_Preparation::prepare($method, $syntax->arguments, $context);
		if ($parent_call && !$static_call) {
			$receiver = new prepared_method_receiver($context->receiver->type, method_receiver_lifetime::current_instance);
			$receiver->dispatch_type = $syntax->owner_type->require_preparation();
			$facts->receiver = $receiver;
		}
		return $facts;
	}
}
