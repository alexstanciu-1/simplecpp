<?php
namespace scpp\compiler;

/** Call-local inference; never published into the provider or canonical type registry. */
final class runtime_call_inference
{
	public array $bindings /** hash<canonical_type_use, int> */ = [];
}
