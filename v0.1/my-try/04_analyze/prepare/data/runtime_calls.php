<?php
namespace scpp\compiler;

/** Selection-local type bindings shared by callable and property recipes; never retained in the provider. */
final class runtime_type_bindings
{
	public array $bindings /** hash<canonical_type_use, int> */ = [];
}
