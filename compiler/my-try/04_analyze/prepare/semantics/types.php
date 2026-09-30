<?php

/* Resolve type syntax and apply the supported semantic value/storage boundaries. */
namespace scpp\compiler;

final class Type_Preparation
{
	/** Named syntax resolves through the existing lexical/publication scope chain. */
	public static function type(type_node $node, preparation_context $context): type_definition
	{
		$node->prepare($context);
		return $node->require_preparation();
	}

	public static function prepare_array_type(array_type_node $node, preparation_context $context): void
	{
		throw new \RuntimeException('S2S constructed types are not supported yet');
	}

	/** Named types resolve against the collected lexical scope and record dependencies. */
	public static function prepare_named_type(named_type_node $node, preparation_context $context): void
	{
		$entry = $node->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$types = Scope_Lookup::types($lexical_scope, $entry->name, $context);
		if (q_count($types) !== 1) {
			throw new \RuntimeException('S2S needs one resolved type for ' . $entry->name);
		}

		$type = $types[0];
		if ($type->declaration !== null) {
			$declaration /** collected_struct */ = $type->declaration;
			$context->worker->require_declaration($context->owner, $declaration);
		}
		$node->set_preparation($type);
	}

	/** Integer aliases with the same representation designate compatible reference storage. */
	public static function same_storage_type(type_definition $left, type_definition $right): bool
	{
		if ($left === $right) {
			return true;
		}

		return ($left->kind === type_kind::integer) && ($right->kind === type_kind::integer)
		&& ($left->value_bits === $right->value_bits) && ($left->signed === $right->signed);
	}

	public static function require_value_type(type_definition $type): void
	{
		if ($type->kind === type_kind::void_type) {
			throw new \RuntimeException('S2S void is not a storage type');
		}
	}

	/** Native main currently accepts only wrapper values with a defined integer exit conversion. */
	public static function entry_return_type(type_definition $type): bool
	{
		return ($type->kind === type_kind::integer) || ($type->kind === type_kind::boolean)
			|| ($type->kind === type_kind::floating);
	}

	/** Integer destinations use the existing runtime conversion; other values keep exact identity. */
	public static function require_assignable(type_definition $destination, type_definition $source): void
	{
		self::require_value_type($destination);
		if ($destination === $source) {
			return;
		}
		if (($destination->kind === type_kind::integer) && ($source->kind === type_kind::integer)) {
			return;
		}

		throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
	}
}
