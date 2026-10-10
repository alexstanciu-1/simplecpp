<?php
namespace scpp\compiler;

/** Resolve startup recipes into existing protocol contracts, never source-stage facts. */
final class Runtime_Protocol_Registration
{
	/** Protocols currently admit a closed type or one owner parameter, not nested open recipes. */
	public static function reference(runtime_type_spec $spec, semantic_type_definition $owner,
		registered_type_catalog $types): contract_type_reference
	{
		if ($spec->parameter !== '') {
			$positions = Runtime_Catalog_Patterns::owner_positions($owner);
			if (!isset($positions[$spec->parameter])) {
				throw new \RuntimeException('Unknown runtime protocol parameter: ' . $spec->parameter);
			}
			return contract_type_reference::argument($positions[$spec->parameter]);
		}
		$type = Runtime_Catalog_Registration::type($spec, $types);
		if (!$types->registry()->has_capability($type, generic_contract::value_storable)) {
			throw new \RuntimeException('Runtime protocol requires a storable type');
		}
		return contract_type_reference::concrete($type);
	}

	public static function indexing(runtime_catalog_input $input, registered_type_catalog $types,
		Storage $declarations /** Storage<indexing_declaration> */): void
	{
		foreach ($input->modules as $module) {
			foreach ($module->types as $definition_spec) {
				if ($definition_spec->indexing === null) { continue; }
				$spec /** runtime_indexing_spec */ = $definition_spec->indexing;
				$owner = $types->definition($definition_spec->name);
				$path /** ?contract_type_reference */ = null;
				if ($spec->path !== null) {
					$path_spec /** runtime_type_spec */ = $spec->path;
					$path = self::reference($path_spec, $owner, $types);
				}
				$write /** ?operator_operation */ = null;
				if ($spec->write) { $write = operator_operation::index_store; }
				$declarations->append(new indexing_declaration($owner,
					self::reference($spec->key, $owner, $types), self::reference($spec->value, $owner, $types),
					$spec->read_operation, $write, $spec->append, self::reference($spec->nested, $owner, $types),
					$path, $spec->probes, $spec->removal));
			}
		}
	}

	public static function iteration(runtime_catalog_input $input, registered_type_catalog $types,
		Storage $declarations /** Storage<iteration_declaration> */): void
	{
		foreach ($input->modules as $module) {
			foreach ($module->types as $definition_spec) {
				if ($definition_spec->iteration === null) { continue; }
				$spec /** runtime_iteration_spec */ = $definition_spec->iteration;
				$owner = $types->definition($definition_spec->name);
				$declarations->append(new iteration_declaration($owner,
					self::reference($spec->key, $owner, $types), self::reference($spec->value, $owner, $types)));
			}
		}
	}
}
