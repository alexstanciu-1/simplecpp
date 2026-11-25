<?php

/* Transient bound contract; not a second canonical type or registry cache. */
namespace scpp\compiler;

final class indexing_signature
{
	public canonical_type_use $key;
	public canonical_type_use $value;
	public canonical_type_use $nested;
	public ?canonical_type_use $path_element = null;
}
