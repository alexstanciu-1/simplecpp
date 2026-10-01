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
		throw new \RuntimeException('S2S fixed array types are not supported yet');
	}

	/** Apply one registered representation modifier without inventing a canonical type identity. */
	public static function prepare_type_use_modifier(type_use_modifier_node $node, preparation_context $context): void
	{
		$type = self::type($node->operand, $context);
		self::require_value_type($type);
		if ($node->modifier !== type_use_modifier_kind::by_value) {
			throw new \RuntimeException('S2S type-use modifier is not supported');
		}
		if ($type->by_value()) {
			throw new \RuntimeException('S2S value type modifier cannot be repeated');
		}
		$node->set_preparation(Model::$type_catalog->registry()->use($type->type_id(), true));
	}

	/** Resolve, validate and intern one source template application without eager combinations. */
	public static function prepare_template_application_type(template_application_type_node $node,
		preparation_context $context): void
	{
		$name_node = $node->definition;
		$entry = $name_node->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$definitions = Scope_Lookup::types($lexical_scope, $entry->name, $context);
		if (q_count($definitions) !== 1) {
			throw new \RuntimeException('S2S needs one resolved template type for ' . $entry->name);
		}
		$definition = $definitions[0];
		if (!($definition instanceof template_type_definition)) {
			throw new \RuntimeException('S2S type arguments require a template definition');
		}
		$template_definition = object_cast($definition, template_type_definition::class);
		$arguments /** vector<canonical_type_use> */ = [];
		$type_arguments /** Storage<type_node> */ = $node->arguments;
		$registry = Model::$type_catalog->registry();
		$registry->validate_application_arity($template_definition, q_count($type_arguments));
		foreach ($type_arguments as $argument) {
			$arguments[] = self::type($argument, $context);
		}

		$application = $registry->intern_application($template_definition, $arguments);
		$node->set_preparation($registry->use($application->type_id()));
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
		if ($left->by_value() !== $right->by_value()) {
			return false;
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
