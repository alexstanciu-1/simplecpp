<?php

/* Resolve type syntax and apply the supported semantic value/storage boundaries. */
namespace scpp\compiler;

final class Type_Preparation
{
	/** Only direct local construction may complete a bare storage request. */
	public static function local_type(type_node $syntax, ?expression_node $initializer,
		preparation_context $context): canonical_type_use
	{
		if (!($syntax instanceof named_type_node)) { return self::type($syntax, $context); }
		$named = object_cast($syntax, named_type_node::class);
		$entry = $named->occurrence();
		$modifier = Model::$type_catalog->source()->modifier_or_null($entry->name);
		if ($modifier !== null) {
			if ($modifier->kind() !== type_use_modifier_kind::by_value) { return self::type($syntax, $context); }
		}
		$definition /** nullable<abstract_template_type_definition> */ = null;
		if ($modifier === null) {
			$lookup_scope = object_cast(weakref_get($entry->scope), scope::class);
			$matches = Observed_Lookup::types($lookup_scope, $entry->name, $context);
			if (q_count($matches) !== 1) { return self::type($syntax, $context); }
			$selected = $matches[0];
			if (($selected !== Model::$type_catalog->definition('shared'))
				&& ($selected !== Model::$type_catalog->definition('unique'))) {
				return self::type($syntax, $context);
			}
			$definition = object_cast($selected, abstract_template_type_definition::class);
		}
		if (!($initializer instanceof object_construction_node)) {
			throw new \RuntimeException('Bare storage modifier requires a direct new initializer');
		}
		$construction = object_cast($initializer, object_construction_node::class);
		$payload = self::type($construction->target_type, $context);
		if ($payload->by_value() || (Type_Queries::source_record($payload) === null)) {
			throw new \RuntimeException('Bare storage modifier requires a concrete class or struct constructor');
		}
		try {
			$result /** canonical_type_use */;
			if ($modifier !== null) {
				$result = Model::$type_catalog->registry()->use($payload->type_id(), true);
			}
			else {
				$template_definition /** abstract_template_type_definition */ = $definition;
				$arguments /** vector<canonical_type_use> */ = [$payload];
				$result = self::application($template_definition, $arguments, $context);
			}
			$named->set_preparation($result);
			return $result;
		}
		catch (\InvalidArgumentException $error) { throw new \RuntimeException($error->getMessage()); }
	}

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
		if ($node instanceof callable_type_node) {
			$callable = object_cast($node, callable_type_node::class);
			$result = self::type($callable->result_type, $context);
			$inputs /** vector<callable_parameter> */ = [];
			$signature = new callable_signature();
			$signature->return_type = $result;
			$contracts /** Storage<callable_parameter> */ = $signature->parameters;
			$parameters /** Storage<callable_input_type_node> */ = $callable->inputs;
			foreach ($parameters as $parameter) {
				$input = Declaration_Preparation::parameter_contract(self::type($parameter->type_syntax, $context),
					$parameter->mode, $parameter->access, $parameter->variadic, $context);
				$inputs[] = $input;
				$contracts->append($input);
			}
			if ($callable->returns_reference) { Reference_Preparation::prepare_return_origin($signature); }
			Callable_Contracts::validate($signature);
			if (!$callable->returns_reference) { Capability_Preparation::require_concrete_value($result, $context); }
			$registry = Model::$type_catalog->registry();
			$definition = $registry->define_callable($result, $inputs, $signature->reference_return);
			$callable->set_preparation($registry->use($registry->canonical($definition)->type_id()));
		}
		elseif ($node instanceof named_type_node) {
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
			$node->set_preparation(self::application($definition, $arguments, $context));
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
		try {
			$node->set_preparation(Model::$type_catalog->registry()->use($type->type_id(), true));
		}
		catch (\InvalidArgumentException $error) {
			throw new \RuntimeException($error->getMessage());
		}
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
		try { $registry->validate_application_arity($template_definition, q_count($type_arguments)); }
		catch (\InvalidArgumentException $error) { throw new \RuntimeException($error->getMessage()); }
		foreach ($type_arguments as $argument) {
			$arguments[] = self::type($argument, $context);
		}

		try {
			$node->set_preparation(self::application($template_definition, $arguments, $context));
		}
		catch (\InvalidArgumentException $error) {
			// Source contract failures participate in normal declaration/body retry scheduling.
			throw new \RuntimeException($error->getMessage());
		}
	}

	/** Both nullable spellings normalize handle absence before interning a wrapper. */
	private static function application(abstract_template_type_definition $definition,
		array $arguments /** vector<canonical_type_use> */, preparation_context $context): canonical_type_use
	{
		foreach ($arguments as $argument) {
			if (Type_Queries::canonical($argument)->definition() instanceof callable_type_definition) {
				// Collections and Results carry owned erased values; other wrappers stay gated.
				if (($definition !== Model::$type_catalog->definition('vector'))
					&& ($definition !== Model::$type_catalog->definition('hash'))
					&& !($definition instanceof result_semantics)) {
					throw new \RuntimeException('Callable wrapper storage is deferred');
				}
			}
		}
		$registry = Model::$type_catalog->registry();
		$registry->validate_application_arity($definition, q_count($arguments));
		if ($definition instanceof nullable_semantics) {
			$payload = $arguments[0];
			if (Type_Queries::is_nominal_handle($payload)) { return $payload; }
		}
		$application = $registry->intern_application($definition, $arguments, new Capability_Preparation($context));
		return $registry->use($application->type_id());
	}

	/** Named types resolve against the collected lexical scope and record dependencies. */
	public static function prepare_named_type(named_type_node $node, preparation_context $context, bool $complete = true): void
	{
		$entry = $node->occurrence();
		$lexical_scope = object_cast(weakref_get($entry->scope), scope::class);
		$node->set_preparation(self::resolve_name($lexical_scope, $entry->name, $context, $complete));
	}

	/** Shared lexical type lookup for syntax types and compile-time type arguments. */
	public static function resolve_name(scope $lexical_scope, string $name,
		preparation_context $context, bool $complete = true): canonical_type_use
	{
		$types = Observed_Lookup::types($lexical_scope, $name, $context);
		if (q_count($types) !== 1) {
			throw new \RuntimeException('S2S needs one resolved type for ' . $name);
		}

		$definition = $types[0];
		if (!($definition instanceof concrete_type_definition_i)) {
			throw new \RuntimeException('S2S template type requires explicit arguments');
		}
		$concrete = object_cast($definition, concrete_type_definition_i::class);
		$type = Model::$type_catalog->registry()->canonical($concrete);
		$type_use = Model::$type_catalog->registry()->use($type->type_id());
		if ($complete && ($definition->origin() === type_definition_origin::source) && !Type_Queries::is_nominal_handle($type_use)) {
			$declaration = Model::$type_catalog->source_declarations()->declaration($definition->definition_id());
			$context->worker->require_declaration($context->owner, $declaration);
		}
		return $type_use;
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
		$left_type = Type_Queries::canonical($left);
		$right_type = Type_Queries::canonical($right);
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
		if (Type_Queries::canonical($type)->definition() === Model::$type_catalog->definition('Iterator')) {
			throw new \RuntimeException('Iterator is a protocol marker; use a typed source interface for storage');
		}
		if (Result_Conversions::is_boolean_sentinel($type)) {
			throw new \RuntimeException('S2S boolean sentinel requires an explicit Result destination');
		}
		$result_role = Result_Conversions::role($type);
		if ($result_role !== null) {
			Result_Conversions::require_payload($type, $result_role);
		}
		if (Type_Queries::canonical($type)->family() === type_family::null_sentinel) {
			throw new \RuntimeException('S2S null requires an explicit nullable, mixed or legacy Result destination');
		}
		if (Type_Queries::canonical($type)->family() === type_family::no_value) {
			throw new \RuntimeException('S2S void is not a storage type');
		}
	}

	/** Native main currently accepts only wrapper values with a defined integer exit conversion. */
	public static function entry_return_type(canonical_type_use $type): bool
	{
		$family = Type_Queries::canonical($type)->family();
		return ($family === type_family::integer) || ($family === type_family::boolean)
			|| ($family === type_family::floating);
	}
}
