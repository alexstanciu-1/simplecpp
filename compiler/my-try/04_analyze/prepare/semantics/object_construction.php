<?php

/* Resolve one default object construction independently of its eventual C++ spelling. */
namespace scpp\compiler;

final class Object_Construction_Preparation
{
	/** Direct typed locals select storage; otherwise the declaration supplies its default. */
	public static function prepare(object_construction_node $node, preparation_context $context,
		?canonical_type_use $destination): prepared_object_construction
	{
		$target = Type_Preparation::type($node->target_type, $context);
		$source_arguments /** Storage<expression_node> */ = $node->arguments;
		if ($source_arguments->count() !== 0) {
			throw new \RuntimeException('S2S object constructor arguments are deferred');
		}
		if ($target->by_value() || (Type_Queries::source_record($target) === null)) {
			throw new \RuntimeException('S2S new requires a source struct or class');
		}
		if (!self::record_default($target, $context)) {
			throw new \RuntimeException('S2S object construction requires default-constructible fields');
		}
		$result = $target;
		if ($destination !== null)
		{
			if (Type_Queries::record_storage($destination) === record_storage_kind::weak) {
				throw new \RuntimeException('S2S weak handles cannot allocate');
			}
			if (!Type_Queries::record_payload($destination)->matches($target)) {
				throw new \RuntimeException('S2S construction target must exactly match destination');
			}
			$result = $destination;
		}
		$facts = new prepared_object_construction();
		$facts->value_origin = expression_value_origin::fresh_construction;
		$facts->constructed_type = $target;
		$facts->type = $result;
		$facts->storage = Type_Queries::record_storage($result);
		return $facts;
	}

	/** Bounded default-construction proof; record dependencies include nested value fields. */
	private static function supports_default(canonical_type_use $type, preparation_context $context): bool
	{
		if (Ownership_Conversions::role($type) !== null) {
			return true;
		}
		$record = Type_Queries::source_record($type);
		if ($record !== null) {
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
		return ($definition === Model::$type_catalog->definition('vector'))
			|| ($definition === Model::$type_catalog->definition('hash'))
			|| $type->matches(Language_Types::error_type(Model::$language_scope));
	}

	/** Construction initializes the payload; default handle fields do not construct their pointees. */
	private static function record_default(canonical_type_use $type, preparation_context $context): bool
	{
		$record /** collected_record */ = Type_Queries::source_record($type);
		$context->worker->require_declaration($context->owner, $record);
		$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->fields;
		foreach ($fields->items() as $field) {
			if ($field->default_value !== null) {
				continue;
			}
			if (!self::supports_default($field->type, $context)) {
				return false;
			}
		}
		return true;
	}
}
