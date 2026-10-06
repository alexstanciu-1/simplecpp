<?php

/* Resolve one default object construction independently of its eventual C++ spelling. */
namespace scpp\compiler;

final class Object_Construction_Preparation
{
	/** A direct local may select exact owning storage; other contexts retain default shared ownership. */
	public static function prepare(object_construction_node $node, preparation_context $context,
		?canonical_type_use $destination): prepared_object_construction
	{
		$target = Type_Preparation::type($node->target_type, $context);
		$source_arguments /** Storage<expression_node> */ = $node->arguments;
		if ($source_arguments->count() !== 0) {
			throw new \RuntimeException('S2S object constructor arguments are deferred');
		}
		if ($target->by_value() || (Type_Queries::source_record($target) === null)) {
			throw new \RuntimeException('S2S owned new currently requires a source struct');
		}
		if (!self::supports_default($target, $context)) {
			throw new \RuntimeException('S2S object construction requires default-constructible fields');
		}
		$family = ownership_kind::shared;
		$result /** nullable<canonical_type_use> */ = null;
		if ($destination !== null)
		{
			$role = Ownership_Conversions::role($destination);
			if ($role === null) {
				throw new \RuntimeException('S2S owned new destination requires shared or unique');
			}
			$family = $role->ownership_family();
			if ($family === ownership_kind::weak) {
				throw new \RuntimeException('S2S weak handles cannot allocate');
			}
			$application = object_cast(Type_Queries::canonical($destination), applied_template_type::class);
			if (!$role->target_type($application)->matches($target)) {
				throw new \RuntimeException('S2S construction target must exactly match ownership destination');
			}
			$result = $destination;
		}
		else
		{
			$definition = object_cast(Model::$type_catalog->definition('shared'), abstract_template_type_definition::class);
			$registry = Model::$type_catalog->registry();
			$arguments /** vector<canonical_type_use> */ = [$target];
			$application = $registry->intern_application($definition, $arguments, new Capability_Preparation($context));
			$result = $registry->use($application->type_id());
		}
		$facts = new prepared_object_construction();
		$facts->value_origin = expression_value_origin::fresh_construction;
		$facts->constructed_type = $target;
		$facts->type = $result;
		$facts->ownership = $family;
		return $facts;
	}

	/** Bounded default-construction proof; record dependencies include nested value fields. */
	private static function supports_default(canonical_type_use $type, preparation_context $context): bool
	{
		if ($type->by_value()) {
			return false;
		}
		$record = Type_Queries::source_record($type);
		if ($record !== null)
		{
			$context->worker->require_declaration($context->owner, $record);
			$fields /** Key_Storage_List<prepared_field> */ = $record->syntax()->require_preparation()->fields;
			foreach ($fields->items() as $field) {
				if (!self::supports_default($field->type, $context)) {
					return false;
				}
			}
			return true;
		}
		if (Conversion_Preparation::is_scalar($type) || Boxed_Conversions::is_boxed($type)
			|| (Ownership_Conversions::role($type) !== null) || (Nullable_Conversions::payload($type) !== null)) {
			return true;
		}
		$definition = Type_Queries::canonical($type)->definition();
		return ($definition === Model::$type_catalog->definition('vector'))
			|| ($definition === Model::$type_catalog->definition('hash'))
			|| $type->matches(Language_Types::error_type(Model::$language_scope));
	}
}
