<?php
namespace scpp\compiler;

/** A provider property recipe; selected storage types belong to prepared applications. */
final class runtime_property_definition
{
	public bool $static_member = false;
	public storage_access $access = storage_access::writable;
	public function __construct(public string $identity, public string $source_name,
		public semantic_type_definition $owner, public runtime_type_pattern $type_pattern) {}
}

/** Type-scoped exports index settled identities; free constants remain in language scope. */
final class Runtime_Members
{
	private Keyed_Storage $constant_names /** Keyed_Storage<registered_constant> */;
	private Keyed_Storage $property_names /** Keyed_Storage<runtime_property_definition> */;
	private Keyed_Storage $property_ids /** Keyed_Storage<runtime_property_definition> */;
	private Keyed_Storage $property_storage /** Keyed_Storage<registered_property> */;

	public function __construct()
	{
		$this->constant_names = new Keyed_Storage /** Keyed_Storage<registered_constant> */();
		$this->property_names = new Keyed_Storage /** Keyed_Storage<runtime_property_definition> */();
		$this->property_ids = new Keyed_Storage /** Keyed_Storage<runtime_property_definition> */();
		$this->property_storage = new Keyed_Storage /** Keyed_Storage<registered_property> */();
	}

	public function stored_property(canonical_type_use $owner, runtime_property_definition $definition): ?registered_property
	{
		$items /** Keyed_Storage<registered_property> */ = $this->property_storage;
		$key = $owner->type_id() . ':' . $definition->identity;
		if (!isset($items[$key])) { return null; }
		return $items[$key];
	}

	public function retain_property(registered_property $property): void
	{
		$items /** Keyed_Storage<registered_property> */ = $this->property_storage;
		$items->add($property->owner_type->type_id() . ':' . $property->definition->identity, $property);
	}

	public function register_property(runtime_property_definition $property): void
	{
		$names /** Keyed_Storage<runtime_property_definition> */ = $this->property_names;
		$ids /** Keyed_Storage<runtime_property_definition> */ = $this->property_ids;
		$key = $property->owner->definition_id() . ':' . $property->source_name;
		if (isset($names[$key]) || isset($ids[$property->identity])) {
			throw new \RuntimeException('Duplicate runtime property: ' . $property->source_name);
		}
		$names->add($key, $property);
		$ids->add($property->identity, $property);
	}

	public function property(canonical_type_use $owner, string $name): ?runtime_property_definition
	{
		$names /** Keyed_Storage<runtime_property_definition> */ = $this->property_names;
		$key = Type_Queries::canonical($owner)->definition()->definition_id() . ':' . $name;
		if (!isset($names[$key])) { return null; }
		return $names[$key];
	}

	public function property_identity(string $identity): runtime_property_definition
	{
		$ids /** Keyed_Storage<runtime_property_definition> */ = $this->property_ids;
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
		if (!isset($constants[$key])) { return null; }
		return $constants[$key];
	}
}
