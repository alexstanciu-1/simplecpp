<?php

/* Relationship facts do not confer layout, construction or dispatch capabilities. */
namespace scpp\compiler;

final class prepared_type_relationships
{
	public ?collected_record $base /** weak<collected_record> */ = null;
	/** Original interface identities; no copied member inventory. */
	public array $interfaces /** vector<collected_interface> */ = [];
	public bool $final_type = false;
}

/** Lookup selects syntax identity only; access and signature compatibility follow separately. */
final class inherited_member_candidate
{
	public function __construct(public collected_member_owner $declaring_type, public collected_declaration $member)
	{
	}
}
