<?php
namespace scpp\compiler;

/** Eligibility belongs to analysis; materialization alone never grants semantics. */
final class Trait_Preparation
{
	public static function validate(collected_record $consumer): void
	{
		foreach (Member_Inventory::contributions($consumer) as $member) {
			$syntax = $member->working->syntax();
			if ($syntax instanceof property_node) {
				$property = object_cast($syntax, property_node::class);
				if (($property->type_syntax === null) || Member_Preparation::is_static($property->modifiers)) {
					throw new \RuntimeException('Trait properties require explicit types and instance storage');
				}
			}
			elseif ($syntax instanceof method_node) {
				$method = object_cast($syntax, method_node::class);
				if (string_byte_starts_with($method->name, '__') || Member_Preparation::is_static($method->modifiers)
					|| !$method->has_parsed_body()) {
					throw new \RuntimeException('Trait methods require ordinary concrete instance bodies');
				}
			}
		}
	}
}
