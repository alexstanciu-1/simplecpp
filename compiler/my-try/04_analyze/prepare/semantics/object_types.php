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
		// These operations already test presence: instanceof returns false and the
		// checked cast fails on absence. Interpret ownership without erasing the operand's type.
		$observed = Type_Queries::nullable_handle_payload($value->type) ?? $value->type;
		$role = Ownership_Conversions::role($observed);
		if ($role === null) { throw new \RuntimeException('Object type operation requires a shared handle'); }
		if ($role->ownership_family() !== ownership_kind::shared) {
			throw new \RuntimeException('Object type operation requires a shared handle');
		}
		$source = Ownership_Conversions::payload($observed);
		self::require_relationship($source, $target, $context);
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

	private static function require_relationship(canonical_type_use $source, canonical_type_use $target, preparation_context $context): void
	{
		$relationships = new Conversion_Relationships($context);
		if ($relationships->is_subtype($source, $target)) { return; }
		$source_owner = Type_Queries::source_declaration($source);
		$target_owner = Type_Queries::source_declaration($target);
		if (!($source_owner instanceof collected_member_owner)) {
			throw new \RuntimeException('Object narrowing requires a source record or interface');
		}
		if (!$relationships->is_subtype($target, $source)) {
			if ($source_owner instanceof collected_record && $target_owner instanceof collected_record) {
				throw new \RuntimeException('Object type operation between records requires related ancestry');
			}
			// Interfaces may overlap through a future implementer. Only a final record
			// closes that possibility; observe finality through the preparation worker.
			if ($source_owner instanceof collected_record) {
				$source_relationships = $context->worker->require_relationships(object_cast($source_owner, collected_record::class), $context);
				if ($source_relationships->final_type) { throw new \RuntimeException('Object type operation cannot cross an unimplemented interface and a final record'); }
			}
			if ($target_owner instanceof collected_record) {
				$target_relationships = $context->worker->require_relationships(object_cast($target_owner, collected_record::class), $context);
				if ($target_relationships->final_type) { throw new \RuntimeException('Object type operation cannot cross an unimplemented interface and a final record'); }
			}
		}
		$contract = $context->worker->require_type_contract(object_cast($source_owner, collected_member_owner::class), $context);
		if (!$contract->polymorphic) { throw new \RuntimeException('Object narrowing requires a polymorphic source'); }
	}
}
