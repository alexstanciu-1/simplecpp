<?php
namespace scpp\compiler;

/** Type-scoped exports index settled identities; free constants remain in language scope. */
final class Runtime_Members
{
	private Keyed_Storage $constant_names /** Keyed_Storage<registered_constant> */;
	private Keyed_Storage $property_names /** Keyed_Storage<registered_property> */;
	private Keyed_Storage $property_ids /** Keyed_Storage<registered_property> */;

	public function __construct()
	{
		$this->constant_names = new Keyed_Storage();
		$this->property_names = new Keyed_Storage();
		$this->property_ids = new Keyed_Storage();
	}

	public function register_property(registered_property $property): void
	{
		$names /** Keyed_Storage<registered_property> */ = $this->property_names;
		$ids /** Keyed_Storage<registered_property> */ = $this->property_ids;
		$key = $property->owner_type->type_id() . ':' . $property->source_name;
		if (isset($names[$key]) || isset($ids[$property->identity])) {
			throw new \RuntimeException('Duplicate runtime property: ' . $property->source_name);
		}
		$names->add($key, $property);
		$ids->add($property->identity, $property);
	}

	public function property(canonical_type_use $owner, string $name): ?registered_property
	{
		$names /** Keyed_Storage<registered_property> */ = $this->property_names;
		$key = $owner->type_id() . ':' . $name;
		return isset($names[$key]) ? $names[$key] : null;
	}

	public function property_identity(string $identity): registered_property
	{
		$ids /** Keyed_Storage<registered_property> */ = $this->property_ids;
		return $ids[$identity];
	}

	/** Registration validates collisions before the index can be published. */
	public function register_constant(registered_constant $constant): void
	{
		$optional_owner = $constant->owner();
		if ($optional_owner === null) {
			throw new \InvalidArgumentException('Member constant requires a type owner');
		}
		$owner /** semantic_type_definition */ = $optional_owner;
		$constants /** Keyed_Storage<registered_constant> */ = $this->constant_names;
		$constants->add($owner->definition_id() . ':' . $constant->constant_name(), $constant);
	}

	public function constant(canonical_type_use $owner, string $name): ?registered_constant
	{
		$constants /** Keyed_Storage<registered_constant> */ = $this->constant_names;
		$key = Type_Queries::canonical($owner)->definition()->definition_id() . ':' . $name;
		return isset($constants[$key]) ? $constants[$key] : null;
	}
}
