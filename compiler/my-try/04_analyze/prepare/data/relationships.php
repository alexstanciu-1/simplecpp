<?php

/* Relationship facts do not confer layout, construction or dispatch capabilities. */
namespace scpp\compiler;

final class prepared_type_relationships
{
	public ?collected_record $base /** weak<collected_record> */ = null;
	/** Original interface identities; no copied member inventory. */
	public array $interfaces /** vector<collected_interface> */ = [];
	public bool $final_type = false;
	public bool $abstract_type = false;
}

/** Local dispatch decisions and unresolved obligations, not a flattened member inventory. */
final class prepared_type_contract
{
	public bool $abstract_type = false;
	public bool $polymorphic = false;
	public Keyed_Storage $methods /** Keyed_Storage<prepared_method_contract> */;
	public array $obligations /** vector<collected_method> */ = [];
	/** Original interface declarations remain requirements after implementation, for descendant validation. */
	public array $interface_requirements /** vector<collected_method> */ = [];
	/** Consumer-owned trait signatures, retained independently of selected methods. */
	public array $trait_requirements /** vector<collected_method> */ = [];
	/** Lowering bridges observe inherited implementations; never synthetic source methods. */
	public Keyed_Storage $forwarders /** Keyed_Storage<prepared_method_contract> */;
	public function __construct()
	{
		$this->methods = new Keyed_Storage /** Keyed_Storage<prepared_method_contract> */();
		$this->forwarders = new Keyed_Storage /** Keyed_Storage<prepared_method_contract> */();
	}
}

final class prepared_method_contract
{
	public collected_method $method /** weak<collected_method> */;
	public ?collected_method $overridden /** weak<collected_method> */ = null;
	public ?collected_record $forwarding_owner /** weak<collected_record> */ = null;
	public bool $virtual_method = false;
	public bool $abstract_method = false;
	public bool $final_method = false;
}

/** Lookup selects syntax identity only; access and signature compatibility follow separately. */
final class inherited_member_candidate
{
	public function __construct(public collected_member_owner $declaring_type, public collected_declaration $member)
	{
	}
}
