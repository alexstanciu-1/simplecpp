<?php

/* Resolve one default object construction independently of its eventual C++ spelling. */
namespace scpp\compiler;

final class Object_Construction_Preparation
{
	/** Direct typed locals select storage; otherwise the declaration supplies its default. */
	public static function prepare(object_construction_node $node, preparation_context $context,
		?canonical_type_use $destination): prepared_object_construction
	{
		// Construct the concrete payload; the enclosing value boundary owns optional wrapping.
		if ($destination !== null) {
			$nullable_payload = Nullable_Conversions::payload($destination);
			if ($nullable_payload !== null) {
				Nullable_Conversions::require_value_payload($nullable_payload);
				$destination = $nullable_payload;
			}
		}
		$late_static = false;
		if ($node->target_type instanceof named_type_node) {
			$late_static = object_cast($node->target_type, named_type_node::class)->name === 'static';
		}
		$target /** canonical_type_use */;
		if ($late_static) {
			$target = Member_Preparation::owner(object_cast($node->target_type, named_type_node::class), $context);
			if (!Type_Queries::is_nominal_handle($target)) { throw new \RuntimeException('new static requires shared class storage'); }
			if ($destination !== null) {
				if (Type_Queries::record_storage($destination) !== record_storage_kind::shared) {
					throw new \RuntimeException('new static requires shared class storage');
				}
			}
		}
		else { $target = Type_Preparation::type($node->target_type, $context); }
		$source_arguments /** Storage<expression_node> */ = $node->arguments;
		$factory = Model::$type_catalog->registry()->runtime_construction(Type_Queries::canonical($target)->definition());
		if ($factory !== '') {
			return self::runtime_factory($target, $factory, $source_arguments, $context);
		}
		if ($target->by_value() || (Type_Queries::source_record($target) === null)) {
			throw new \RuntimeException('S2S new requires a source struct or class');
		}
		$record /** collected_record */ = Type_Queries::source_record($target);
		if ($context->worker->require_relationships($record, $context)->abstract_type) {
			throw new \RuntimeException('Cannot construct an abstract type');
		}
		$context->worker->require_declaration($context->owner, $record);
		$constructor = weakref_get($record->syntax()->require_preparation()->constructor);
		$call /** nullable<prepared_call> */ = null;
		if ($constructor !== null) {
			Constructor_Preparation::require_access($constructor, $context);
			$call = Call_Preparation::prepare($constructor, $source_arguments, $context);
		}
		else {
			if ($source_arguments->count() !== 0) { throw new \RuntimeException('S2S object constructor arguments require an authored constructor'); }
			if (!self::record_default($target, $context)) {
				throw new \RuntimeException('S2S object construction requires default-constructible fields');
			}
		}
		$result = $target;
		if ($destination !== null)
		{
			if (Type_Queries::record_storage($destination) === record_storage_kind::weak) {
				throw new \RuntimeException('S2S weak handles cannot allocate');
			}
			if (!Type_Queries::record_payload($destination)->matches($target)) {
				// Construct a class in its own shared storage; the enclosing boundary proves any upcast.
				if (!Type_Queries::is_nominal_handle($target)
					|| (Type_Queries::record_storage($destination) !== record_storage_kind::shared)) {
					throw new \RuntimeException('S2S construction target must exactly match destination');
				}
			}
			else { $result = $destination; }
		}
		Type_Lifecycle_Preparation::require_construction($result, $context);
		$facts = new prepared_object_construction();
		$facts->value_origin = expression_value_origin::fresh_construction;
		$facts->constructed_type = $target;
		$facts->type = $result;
		$facts->storage = Type_Queries::record_storage($result);
		$facts->constructor_call = $call;
		return $facts;
	}

	/** Shared source-new and registered empty initialization factory admission. */
	public static function runtime_factory(canonical_type_use $target, string $factory,
		Storage $source_arguments /** Storage<expression_node> */, preparation_context $context): prepared_object_construction
	{
		$call = Runtime_Call_Preparation::prepare(Model::$runtime_callables->methods_named($target, $factory, true), $source_arguments, $context, $target, true);
		if (!$call->type->matches($target)) { throw new \RuntimeException('Runtime construction factory must return the exact owner type'); }
		$facts = new prepared_object_construction();
		$facts->value_origin = expression_value_origin::fresh_construction;
		$facts->constructed_type = $target;
		$facts->type = $target;
		$facts->storage = record_storage_kind::inline_value;
		$facts->runtime_factory = $call;
		return $facts;
	}

	/** Field initialization includes temporary empty storage, unlike required value defaults. */
	public static function supports_field_storage(prepared_field $field, preparation_context $context): bool
	{
		if (($field->default_value !== null) || $field->empty_initial_storage) { return true; }
		return self::supports_default($field->type, $context);
	}

	/** Bounded default-construction proof; record dependencies include nested value fields. */
	public static function supports_default(canonical_type_use $type, preparation_context $context, bool $base_subobject = false): bool
	{
		// Empty backing storage is not a valid default value for a required object.
		if (Type_Queries::is_nominal_handle($type)) { return false; }
		if (Ownership_Conversions::role($type) !== null) {
			return true;
		}
		$record = Type_Queries::source_record($type);
		if ($record !== null) {
			if (!$base_subobject) {
				if ($context->worker->require_relationships($record, $context)->abstract_type) { return false; }
			}
			$context->worker->require_declaration($context->owner, $record);
			$constructor = weakref_get($record->syntax()->require_preparation()->constructor);
			if ($constructor !== null) {
				if ($constructor->declaring_type() !== $record) {
					$signature = Call_Preparation::require_signature($constructor, $context);
					Constructor_Preparation::require_access($constructor, $context);
					if ($constructor->callable_syntax()->require_preparation()->visibility !== member_visibility::public_access) { return false; }
					$parameters /** Storage<callable_parameter> */ = $signature->parameters;
					// Preserve the pre-forwarding implicit derived default constructor.
					if ($parameters->is_empty()) { return self::record_default($type, $context); }
				}
				// Authored calls currently require an explicit new/data consumer, which sequences defaults.
				return false;
			}
			return self::record_default($type, $context);
		}
		if ($type->by_value()) {
			return false;
		}
		if (Conversion_Preparation::is_scalar($type) || Boxed_Conversions::is_boxed($type)
			|| (Nullable_Conversions::payload($type) !== null)) {
			return true;
		}
		$definition = Type_Queries::canonical($type)->definition();
		return Model::$type_catalog->registry()->runtime_default_constructible($definition)
			|| ($definition instanceof callable_type_definition)
			|| $type->matches(Language_Types::error_type(Model::$language_scope));
	}

	/** Construction initializes the payload; default handle fields do not construct their pointees. */
	private static function record_default(canonical_type_use $type, preparation_context $context): bool
	{
		$record /** collected_record */ = Type_Queries::source_record($type);
		$context->worker->require_declaration($context->owner, $record);
		$layout = $record->syntax()->require_preparation();
		if ($record->syntax()->declaration_kind === type_declaration_kind::union_type) { return true; }
		// Synthesized base inputs may be required even when every local property defaults.
		if ($layout->initialization !== null) {
			if ($layout->initialization->base_contract !== null) {
				if (!self::supports_default($layout->base_type, $context, true)) { return false; }
			}
		}
		$fields /** Key_Storage_List<prepared_field> */ = $layout->fields;
		foreach ($fields->items() as $field) {
			if (!self::supports_field_storage($field, $context)) {
				return false;
			}
		}
		return true;
	}
}
