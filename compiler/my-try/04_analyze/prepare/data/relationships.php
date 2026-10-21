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
	/** Protocol membership is separate from source-interface ancestry and object storage. */
	public bool $typed_iterator = false;
}

/** Local dispatch decisions and unresolved obligations, not a flattened member inventory. */
final class prepared_type_contract
{
	public ?prepared_iterator_protocol $iterator_protocol = null;
	public bool $abstract_type = false;
	public bool $polymorphic = false;
	public Keyed_Storage $methods /** Keyed_Storage<prepared_method_contract> */;
	public array $obligations /** vector<collected_method> */ = [];
	/** Original interface declarations remain requirements after implementation, for descendant validation. */
	public array $interface_requirements /** vector<collected_method> */ = [];
	/** Consumer-owned trait signatures, retained independently of selected methods. */
	public array $trait_requirements /** vector<collected_method> */ = [];
	/** Locally introduced dispatch for unchanged inherited implementations. */
	public Keyed_Storage $forwarders /** Keyed_Storage<prepared_method_contract> */;
	/** Required contracts routed to selected implementations; no synthetic source members. */
	public Storage $bindings /** Storage<prepared_dispatch_binding> */;
	public function __construct()
	{
		$this->methods = new Keyed_Storage /** Keyed_Storage<prepared_method_contract> */();
		$this->forwarders = new Keyed_Storage /** Keyed_Storage<prepared_method_contract> */();
		$this->bindings = new Storage /** Storage<prepared_dispatch_binding> */();
	}
}

/** Original selected methods; the type-contract owner observes signatures and inheritance. */
final class prepared_iterator_protocol
{
	public collected_method $current /** weak<collected_method> */;
	public collected_method $key /** weak<collected_method> */;
	public collected_method $valid /** weak<collected_method> */;
	public collected_method $next /** weak<collected_method> */;
	public collected_method $rewind /** weak<collected_method> */;
	public canonical_type_use $key_type;
	public canonical_type_use $value_type;
}

final class prepared_method_contract
{
	/** A descendant can introduce dispatch for an unchanged inherited declaration. */
	public collected_member_owner $dispatch_owner /** weak<collected_member_owner> */;
	public collected_method $method /** weak<collected_method> */;
	public ?collected_method $overridden /** weak<collected_method> */ = null;
	public bool $virtual_method = false;
	public bool $abstract_method = false;
	public bool $final_method = false;
}

final class prepared_dispatch_binding
{
	public collected_method $required /** weak<collected_method> */;
	public collected_member_owner $required_owner /** weak<collected_member_owner> */;
	public collected_method $implementation /** weak<collected_method> */;
	public collected_member_owner $implementation_owner /** weak<collected_member_owner> */;
	public prepared_callable_adaptation $adaptation;
	/** Trait requirements route semantic calls, but are not separate runtime bases. */
	public bool $virtual_dispatch = true;
}

/** Lookup selects syntax identity only; access and signature compatibility follow separately. */
final class inherited_member_candidate
{
	public function __construct(public collected_member_owner $declaring_type, public collected_declaration $member)
	{
	}
}
