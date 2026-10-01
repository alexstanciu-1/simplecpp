<?php

/* Canonical language constants; source occurrences retain references, never copies of definitions. */
namespace scpp\compiler;

/** Scope-owned immutable semantic definition, independent of backend spelling. */
abstract class constant_definition implements preparation_lookup_candidate_i
{
	public string $name;
	public canonical_type_use $type;
}

/** Exact signed integer value retained as decimal text to avoid host-width conversion. */
final class integer_constant_definition extends constant_definition
{
	public string $decimal;
}
