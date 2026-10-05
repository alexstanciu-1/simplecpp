<?php

/* Resolve type syntax and apply the supported semantic value/storage boundaries. */
namespace scpp\compiler;

final class Type_Preparation
{
	/** Implicit packs use the canonical runtime vector, independently of source shadowing. */
	public static function vector_of(canonical_type_use $element, preparation_context $context): canonical_type_use
	{
		$definition = object_cast(Model::$type_catalog->definition('vector'), abstract_template_type_definition::class);
		$registry = Model::$type_catalog->registry();
		try {
			$arguments /** vector<canonical_type_use> */ = [$element];
			$application = $registry->intern_application($definition, $arguments, new Capability_Preparation($context));
			return $registry->use($application->type_id());
		}
		catch (\InvalidArgumentException $error) { throw new \RuntimeException($error->getMessage()); }
	}
	/** Named syntax resolves through the existing lexical/publication scope chain. */
	public static function type(type_node $node, preparation_context $context): canonical_type_use
	{
		if ($node instanceof named_type_node) {
			self::prepare_named_type(object_cast($node, named_type_node::class), $context);
		}
		elseif ($node instanceof nullable_type_node) {
			self::prepare_nullable_type(object_cast($node, nullable_type_node::class), $context);
		}
		elseif ($node instanceof array_type_node) {
			self::prepare_array_type(object_cast($node, array_type_node::class), $context);
		}
		elseif ($node instanceof template_application_type_node) {
			self::prepare_template_application_type(object_cast($node, template_application_type_node::class), $context);
		}
		elseif ($node instanceof type_use_modifier_node) {
			self::prepare_type_use_modifier(object_cast($node, type_use_modifier_node::class), $context);
		}
		else { throw new \RuntimeException('Preparation is not supported for this node'); }
		return $node->require_preparation();
	}

	public static function prepare_array_type(array_type_node $node, preparation_context $context): void
	{
		throw new \RuntimeException('S2S fixed array types are not supported yet');
	}

	public static function prepare_nullable_type(nullable_type_node $node, preparation_context $context): void
	{
		$payload = self::type($node->payload, $context);
		$definition = object_cast(Model::$type_catalog->definition('nullable'), abstract_template_type_definition::class);
		$arguments /** vector<canonical_type_use> */ = [$payload];
		try {
			$registry = Model::$type_catalog->registry();
			$application = $registry->intern_application($definition, $arguments, new Capability_Preparation($context));
			$node->set_preparation($registry->use($application->type_id()));
		}
		catch (\InvalidArgumentException $error) { throw new \RuntimeException($error->getMessage()); }
	}

	/** Resolve a source modifier through registry storage policy without inventing a type identity. */
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
		$definitions = Observed_Lookup::types($lexical_scope, $entry->name, $context);
		if (q_count($definitions) !== 1) {
			throw new \RuntimeException('S2S needs one resolved template type for ' . $entry->name);
		}
		$definition = $definitions[0];
		if (!($definition instanceof abstract_template_type_definition)) {
			throw new \RuntimeException('S2S type arguments require a template definition');
		}
		$template_definition = object_cast($definition, abstract_template_type_definition::class);
		$arguments /** vector<canonical_type_use> */ = [];
		$type_arguments /** Storage<type_node> */ = $node->arguments;
		$registry = Model::$type_catalog->registry();
		$registry->validate_application_arity($template_definition, q_count($type_arguments));
		foreach ($type_arguments as $argument) {
			$arguments[] = self::type($argument, $context);
		}

		try {
			$application = $registry->intern_application($template_definition, $arguments,
				new Capability_Preparation($context));
			$node->set_preparation($registry->use($application->type_id()));
		}
		catch (\InvalidArgumentException $error) {
			// Source contract failures participate in normal declaration/body retry scheduling.
			throw new \RuntimeException($error->getMessage());
		}
	}

	/** Named types resolve against the collected lexical scope and record dependencies. */
	public static function prepare_named_type(named_type_node $node, preparation_context $context): void
	{
		$entry = $node->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$types = Observed_Lookup::types($lexical_scope, $entry->name, $context);
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

	/** Admit storage separately from internal sentinel identities and generic type formation. */
	public static function require_value_type(canonical_type_use $type): void
	{
		if (Result_Conversions::is_boolean_sentinel($type)) {
			throw new \RuntimeException('S2S boolean sentinel requires an explicit Result destination');
		}
		$result_role = Result_Conversions::role($type);
		if ($result_role !== null) {
			Result_Conversions::require_scalar_payload($type, $result_role);
		}
		if (self::canonical($type)->family() === type_family::null_sentinel) {
			throw new \RuntimeException('S2S null requires an explicit nullable, mixed or legacy Result destination');
		}
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
