<?php

/* Resolve type syntax and apply the supported semantic value/storage boundaries. */
namespace scpp\compiler;

final class Type_Preparation
{
	/** Named syntax resolves through the existing lexical/publication scope chain. */
	public static function type(type_node $node, preparation_context $context): canonical_type_use
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

		$definition = $types[0];
		if (!($definition instanceof concrete_type_definition_i)) {
			throw new \RuntimeException('S2S template type requires explicit arguments');
		}
		$concrete = object_cast($definition, concrete_type_definition_i::class);
		if ($definition->origin() === type_definition_origin::source) {
			$declaration = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
			$context->worker->require_declaration($context->owner, $declaration);
		}
		$type = Model::$type_catalog->registry()->canonical($concrete);
		$node->set_preparation(Model::$type_catalog->registry()->use($type->type_id()));
	}

	/** Integer aliases with the same representation designate compatible reference storage. */
	public static function same_storage_type(canonical_type_use $left, canonical_type_use $right): bool
	{
		if ($left->matches($right)) {
			return true;
		}
		$left_type = self::canonical($left);
		$right_type = self::canonical($right);
		if (($left_type->family() !== type_family::integer) || ($right_type->family() !== type_family::integer)) {
			return false;
		}
		$left_definition = object_cast($left_type->definition(), integer_type_definition::class);
		$right_definition = object_cast($right_type->definition(), integer_type_definition::class);
		return ($left_definition->bit_width() === $right_definition->bit_width())
			&& ($left_definition->signed() === $right_definition->signed());
	}

	public static function require_value_type(canonical_type_use $type): void
	{
		if (self::canonical($type)->family() === type_family::no_value) {
			throw new \RuntimeException('S2S void is not a storage type');
		}
	}

	/** Native main currently accepts only wrapper values with a defined integer exit conversion. */
	public static function entry_return_type(canonical_type_use $type): bool
	{
		$family = self::canonical($type)->family();
		return ($family === type_family::integer) || ($family === type_family::boolean)
			|| ($family === type_family::floating);
	}

	/** Integer destinations use the existing runtime conversion; other values keep exact identity. */
	public static function require_assignable(canonical_type_use $destination, canonical_type_use $source): void
	{
		self::require_value_type($destination);
		if ($destination->matches($source)) {
			return;
		}
		if ((self::canonical($destination)->family() === type_family::integer)
			&& (self::canonical($source)->family() === type_family::integer)) {
			return;
		}

		throw new \RuntimeException('S2S value boundary requires matching types or an integer conversion');
	}

	public static function canonical(canonical_type_use $type): canonical_type_i
	{
		return Model::$type_catalog->registry()->type($type->type_id());
	}

	public static function source_record(canonical_type_use $type): ?collected_struct
	{
		$canonical = self::canonical($type);
		$definition = $canonical->definition();
		if ($definition->origin() !== type_definition_origin::source) {
			return null;
		}
		return Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
	}
}
