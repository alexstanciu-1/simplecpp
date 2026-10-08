<?php

/* Shared checked access: validate relationships once, retain only backend-neutral facts. */
namespace scpp\compiler;

final class Object_Type_Preparation
{
	public static function prepare(object_type_node $syntax, preparation_context $context): prepared_object_type
	{
		$value = Expression_Preparation::prepare($syntax->operand, $context);
		$target = Type_Preparation::type($syntax->target_type, $context);
		$target_owner = Type_Queries::source_declaration($target);
		if (!($target_owner instanceof collected_record) && !($target_owner instanceof collected_interface)) {
			throw new \RuntimeException('Object type target must be a source class, struct or interface');
		}
		$role = Ownership_Conversions::role($value->type);
		if ($role === null) { throw new \RuntimeException('Object type operation requires a shared handle'); }
		if ($role->ownership_family() !== ownership_kind::shared) {
			throw new \RuntimeException('Object type operation requires a shared handle');
		}
		$source = Ownership_Conversions::payload($value->type);
		$relationships = new Conversion_Relationships($context);
		if (!$relationships->is_subtype($source, $target)) {
			if (!($target_owner instanceof collected_record) || !$relationships->is_subtype($target, $source)) {
				throw new \RuntimeException('Object type operation requires related ancestry; cross-casts are deferred');
			}
			$source_owner = Type_Queries::source_declaration($source);
			if (!($source_owner instanceof collected_member_owner)) {
				throw new \RuntimeException('Object narrowing requires a source record or interface');
			}
			$contract = $context->worker->require_type_contract(object_cast($source_owner, collected_member_owner::class), $context);
			if (!$contract->polymorphic) { throw new \RuntimeException('Object narrowing requires a polymorphic source'); }
		}
		$facts = new prepared_object_type();
		$facts->operation = $syntax->operation;
		$facts->target_payload = $target;
		$facts->type = $target;
		if ($syntax->operation === object_type_operation::type_test) { $facts->type = $context->boolean; }
		elseif (!Type_Queries::is_nominal_handle($target)) {
			$definition = object_cast(Model::$type_catalog->definition('shared'), abstract_template_type_definition::class);
			$registry = Model::$type_catalog->registry();
			$arguments /** vector<canonical_type_use> */ = [$target];
			$application = $registry->intern_application($definition, $arguments, new Capability_Preparation($context));
			$facts->type = $registry->use($application->type_id());
		}
		return $facts;
	}
}
