<?php

/* Instance context and member selection extend the ordinary callable boundary. */
namespace scpp\compiler;

final class Method_Preparation
{
	public static function receiver(collected_method $method, preparation_context $context): prepared_receiver
	{
		$record = object_cast($method->declaring_type(), collected_record::class);
		$receiver = new prepared_receiver();
		$receiver->declaration = weak($method);
		$scope = object_cast(weakref_get($record->scope), scope::class);
		$receiver->type = Type_Preparation::resolve_name($scope, $record->name, $context, false);
		if (Type_Queries::is_nominal_handle($receiver->type)) {
			$receiver->type = Model::$type_catalog->registry()->use($receiver->type->type_id(), true);
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
			elseif ($modifier === declaration_modifier::virtual_declaration) {
				if ($facts->explicit_virtual) { throw new \RuntimeException('Duplicate Virtual attribute'); }
				$facts->explicit_virtual = true;
			}
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
		if ($facts->explicit_virtual && ($facts->abstract_method || $facts->static_member || $facts->final_method
			|| $facts->override_assertion || $destructor || ($facts->visibility === member_visibility::private_access))) {
			throw new \RuntimeException('Virtual requires a new non-final public/protected concrete instance method');
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
		$parameters /** Storage<parameter_node> */ = $syntax->signature_syntax->parameters;
		if ($destructor) {
			if (!$parameters->is_empty() || ($syntax->signature_syntax->return_type !== null) || $syntax->signature_syntax->returns_reference
				|| $facts->static_member) {
				throw new \RuntimeException('Destructor requires instance access, no parameters and no declared return type/reference');
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
		if (!$facts->static_member) { $facts->receiver = self::receiver($method, $context); }
	}

	/** Member receiver access borrows the original instance without making a value copy. */
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
				$reference->operation = variable_reference_operation::receiver;
				$reference->type = $receiver->type;
				$reference->declaration = $receiver->declaration;
				$reference->place = new prepared_place($receiver, null, true);
				$variable->set_preparation($reference);
				return $reference;
			}
		}
		return Expression_Preparation::prepare($syntax, $context);
	}

	/** Struct self is a copyable place; class self acquires ownership as an ordinary value. */
	public static function self_value(preparation_context $context): prepared_variable_reference
	{
		$receiver = $context->receiver;
		if ($receiver === null) { throw new \RuntimeException('$this requires an instance body'); }
		$value_type = Model::$type_catalog->registry()->use($receiver->type->type_id());
		if (Type_Queries::is_nominal_handle($value_type)) {
			$method = object_cast(weakref_get($receiver->declaration), collected_method::class);
			if (($method->name === '__construct') || ($method->name === '__destruct')) {
				throw new \RuntimeException('Shared self acquisition is forbidden during construction or destruction');
			}
			$reference = new prepared_variable_reference();
			$reference->operation = variable_reference_operation::shared_self;
			$reference->type = $value_type;
			$reference->declaration = $receiver->declaration;
			return $reference;
		}
		$reference = new prepared_variable_reference();
		$reference->operation = variable_reference_operation::receiver_value;
		$reference->type = $value_type;
		$reference->declaration = $receiver->declaration;
		$reference->place = new prepared_place($receiver, null, true);
		return $reference;
	}

	public static function call(call_node $syntax, preparation_context $context): prepared_call
	{
		if ($syntax->owner_type !== null) {
			$type = Member_Preparation::owner($syntax->owner_type, $context);
			if (Type_Queries::canonical($type)->definition()->origin() === type_definition_origin::runtime) {
				return Call_Preparation::prepare_named(Model::$runtime_callables->methods_named($type, $syntax->name, true),
					$syntax->arguments, $context, $type);
			}
			$static_record = Type_Queries::source_record($type);
			if ($static_record === null) { throw new \RuntimeException('Static call requires a source class or struct'); }
			if (Member_Preparation::named_owner_is($syntax->owner_type, 'parent')) {
				return self::select($syntax, $static_record, true, $context, true);
			}
			return self::select($syntax, $static_record, true, $context);
		}
		$base /** expression_node */ = $syntax->receiver;
		$value = self::base($base, $context);
		$payload = Type_Queries::receiver_payload($value->type);
		$declaration = Type_Queries::source_declaration($payload);
		$runtime = Type_Queries::canonical($payload)->definition()->origin() === type_definition_origin::runtime;
		if ($runtime && $syntax->static_qualification) {
			$qualified = Call_Preparation::prepare_named(Model::$runtime_callables->methods_named($payload, $syntax->name, true),
				$syntax->arguments, $context, $payload);
			$qualified->evaluates_qualifier = true;
			return $qualified;
		}
		if ($runtime) {
			$facts = Call_Preparation::prepare_named(Model::$runtime_callables->methods_named($payload, $syntax->name, false),
				$syntax->arguments, $context, $payload);
			$facts->receiver = self::instance_receiver($value, $payload);
			return $facts;
		}
		if (!($declaration instanceof collected_member_owner)) { throw new \RuntimeException('Instance call requires a class, struct or interface receiver'); }
		$record = object_cast($declaration, collected_member_owner::class);
		if ($syntax->static_qualification) {
			$qualified = self::select($syntax, $record, true, $context);
			$qualified->evaluates_qualifier = true;
			return $qualified;
		}
		$receiver = self::instance_receiver($value, $payload);
		$facts = self::select($syntax, $record, false, $context);
		$facts->receiver = $receiver;
		return $facts;
	}

	/** Source and provider methods retain/borrow receivers under the same lifetime rules. */
	private static function instance_receiver(prepared_expression $value, canonical_type_use $payload): prepared_method_receiver
	{
		$storage = Type_Queries::receiver_storage($value->type);
		if ($value->place !== null) { Reference_Preparation::require_mutable_contents($value->place, $value->type); }
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
		return new prepared_method_receiver($payload, $lifetime);
	}

	private static function select(call_node $syntax, collected_member_owner $record, bool $static_call, preparation_context $context,
		bool $parent_call = false): prepared_call
	{
		if ($record instanceof collected_record) { $context->worker->require_declaration($context->owner, $record); }
		else { $context->worker->require_type_contract($record, $context); }
		$matches = Inherited_Lookup::members($record, $syntax->name, preparation_lookup_kind::method_name, $context);
		if ((q_count($matches) === 0) && !$static_call && !$parent_call
			&& $context->worker->require_relationships($record, $context)->exception_type) {
			$root = Language_Types::exception_type(Model::$language_scope);
			$runtime_methods /** vector<callable_definition_i> */ = Model::$runtime_callables->methods_named($root, $syntax->name, false);
			if (q_count($runtime_methods) !== 0) {
				return Call_Preparation::prepare_named($runtime_methods, $syntax->arguments, $context, $root);
			}
		}
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
				if (Member_Preparation::named_owner_is($syntax->owner_type, 'self')
					|| Member_Preparation::named_owner_is($syntax->owner_type, 'parent')) {
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
