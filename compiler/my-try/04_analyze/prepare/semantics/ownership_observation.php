<?php

/* One typed observation path for weak derivation and explicit strong acquisition. */
namespace scpp\compiler;

final class Ownership_Observation_Preparation
{
	/** Preserve the exact payload identity; neither operation copies or allocates a pointee. */
	public static function prepare(ownership_observation_node $syntax,
		preparation_context $context): prepared_ownership_observation
	{
		$input_family = ownership_kind::weak;
		$result_name = 'shared';
		$diagnostic = 'S2S lock requires weak ownership';
		if ($syntax->operation === ownership_observation_kind::derive_weak) {
			$input_family = ownership_kind::shared;
			$result_name = 'weak';
			$diagnostic = 'S2S weak derivation requires shared ownership';
		}
		$value = Expression_Preparation::prepare($syntax->operand, $context);
		$role = Ownership_Conversions::role($value->type);
		if ($role === null) {
			throw new \RuntimeException($diagnostic);
		}
		if ($role->ownership_family() !== $input_family) {
			throw new \RuntimeException($diagnostic);
		}
		$source_application = object_cast(Type_Queries::canonical($value->type), applied_template_type::class);
		$payload = $role->target_type($source_application);
		$definition = object_cast(Model::$type_catalog->definition($result_name), abstract_template_type_definition::class);
		$registry = Model::$type_catalog->registry();
		$arguments /** vector<canonical_type_use> */ = [$payload];
		$result_application = $registry->intern_application($definition, $arguments, new Capability_Preparation($context));
		$facts = new prepared_ownership_observation();
		$facts->type = $registry->use($result_application->type_id());
		$facts->operation = $syntax->operation;
		return $facts;
	}
}
