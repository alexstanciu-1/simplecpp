<?php

/* Canonical Simple C++ type facts; no backend spelling or fabricated source syntax. */
namespace scpp\compiler;

enum type_kind {
	case integer;
	case record;
}

enum type_origin {
	case language;
	case source;
}

/** Scope owns definitions; prepared expressions and bindings share their identity. */
final class type_definition
{
	public string $name;
	public type_kind $kind;
	public type_origin $origin;
	public int $value_bits /** uint32 */ = 0;
	public bool $signed = false;
	/** Present only for source definitions. @reference.source collected_file.entries */
	public ?collected_name $declaration = null;
}
