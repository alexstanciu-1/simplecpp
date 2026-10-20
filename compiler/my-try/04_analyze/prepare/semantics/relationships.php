<?php

/* Analysis-owned ancestry resolution; never requires record layout or method bodies. */
namespace scpp\compiler;

final class Relationship_Preparation
{
	/** Resolve identity with ordinary observed type lookup, explicitly without layout completion. */
	private static function target(named_type_node $syntax, preparation_context $context): collected_member_owner
	{
		Type_Preparation::prepare_named_type($syntax, $context, false);
		$entry = Type_Queries::source_declaration($syntax->require_preparation());
		if (!($entry instanceof collected_member_owner)) {
			throw new \RuntimeException('Relationship target must be a source class, struct or interface');
		}
		return object_cast($entry, collected_member_owner::class);
	}

	/** Resolve ordered direct edges only; completed dependencies reject indirect cycles. */
	public static function prepare(collected_member_owner $entry, preparation_context $context): prepared_type_relationships
	{
		Declaration_Preparation::type_name($entry, $context);
		if ($entry instanceof collected_trait) {
			throw new \RuntimeException('Traits are composition sources, not runtime types');
		}
		$syntax = $entry->syntax();
		if ($syntax->declaration_kind === type_declaration_kind::union_type) { Union_Preparation::shape($syntax); }
		$facts = new prepared_type_relationships();
		foreach ($syntax->modifiers as $modifier) {
			if ($modifier === declaration_modifier::final_declaration) { $facts->final_type = true; }
			if ($modifier === declaration_modifier::abstract_declaration) { $facts->abstract_type = true; }
		}
		if ($facts->final_type && $facts->abstract_type) { throw new \RuntimeException('A type cannot be both abstract and final'); }
		$extended /** Storage<named_type_node> */ = $syntax->extended_interfaces;
		$implemented /** Storage<named_type_node> */ = $syntax->implemented_interfaces;
		$interfaces /** Storage<named_type_node> */ = $implemented;
		if ($entry instanceof collected_interface) {
			if (($syntax->base_class !== null) || !$implemented->is_empty() || $facts->final_type) {
				throw new \RuntimeException('Interface relationships require extends interfaces, not a record base, implements or final');
			}
			$interfaces = $extended;
		}
		elseif (!$extended->is_empty()) {
			throw new \RuntimeException('Records use implements for interface relationships');
		}
		if ($syntax->base_class !== null) {
			$base = self::target($syntax->base_class, $context);
			if (!($base instanceof collected_record)) {
				throw new \RuntimeException('Record base must be a class or struct');
			}
			if ($base->syntax()->declaration_kind !== $syntax->declaration_kind) {
				throw new \RuntimeException('Class/struct inheritance requires a same-kind base');
			}
			$base_facts = $context->worker->require_relationships($base, $context);
			if ($base_facts->final_type) { throw new \RuntimeException('Cannot extend a final type'); }
			$facts->base = object_cast($base, collected_record::class);
		}
		foreach ($interfaces as $interface_syntax) {
			$target = self::target($interface_syntax, $context);
			if (!($target instanceof collected_interface)) {
				throw new \RuntimeException('Interface relationship requires an interface target');
			}
			foreach ($facts->interfaces as $previous) {
				if ($previous === $target) { throw new \RuntimeException('Duplicate direct interface relationship'); }
			}
			$context->worker->require_relationships($target, $context);
			$facts->interfaces[] = object_cast($target, collected_interface::class);
		}
		return $facts;
	}

	/** Identity/order equality preserves settled facts across no-op parsing. */
	public static function same(?prepared_type_relationships $old, prepared_type_relationships $current): bool
	{
		if ($old === null) { return false; }
		if ((weakref_get($old->base) !== weakref_get($current->base)) || ($old->final_type !== $current->final_type) || ($old->abstract_type !== $current->abstract_type)
			|| (q_count($old->interfaces) !== q_count($current->interfaces))) { return false; }
		foreach ($old->interfaces as $index => $target) {
			if ($target !== $current->interfaces[$index]) { return false; }
		}
		return true;
	}
}
