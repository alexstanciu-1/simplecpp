<?php

/* Recognized initialization contracts. No syntax, scheduling or backend spelling. */
namespace scpp\compiler;

enum initialization_shape: int
{
	case positional = 1;
	case keyed = 2;
	case empty_brackets = 3;
}

enum initialization_type_source: int
{
	case target = 1;
	case argument = 2;
	case concrete = 3;
}

/** A bounded type substitution, shared by every registered initialization family. */
final class initialization_type_reference
{
	public function __construct(private initialization_type_source $source_kind,
		private int $argument_position, private ?canonical_type_use $concrete_data)
	{
		if (($source_kind === initialization_type_source::concrete) !== ($concrete_data !== null)) {
			throw new \InvalidArgumentException('Initialization concrete type reference mismatch');
		}
		if (($source_kind === initialization_type_source::argument) !== ($argument_position >= 0)) {
			throw new \InvalidArgumentException('Initialization argument reference mismatch');
		}
	}

	public static function target(): initialization_type_reference
	{
		return new initialization_type_reference(initialization_type_source::target, -1, null);
	}

	public static function argument(int $position): initialization_type_reference
	{
		if ($position < 0) { throw new \InvalidArgumentException('Negative initialization type argument'); }
		return new initialization_type_reference(initialization_type_source::argument, $position, null);
	}

	public static function concrete(canonical_type_use $type): initialization_type_reference
	{
		return new initialization_type_reference(initialization_type_source::concrete, -1, $type);
	}

	public function source(): initialization_type_source { return $this->source_kind; }
	public function position(): int { return $this->argument_position; }
	public function concrete_type(): canonical_type_use { return $this->concrete_data; }
}

/** Registry declaration; a missing key means the list role, not a null key type. */
final class initialization_declaration
{
	public function __construct(private type_definition_i $target_definition,
		private initialization_type_reference $value_reference,
		private ?initialization_type_reference $key_reference,
		private initialization_type_reference $nested_reference,
		private array $argument_constraints /** hash<canonical_type_use, int> */,
		private string $factory_identity)
	{
		if ($factory_identity === '') { throw new \InvalidArgumentException('Initialization factory identity must not be empty'); }
		if ($target_definition->origin() === type_definition_origin::source) {
			throw new \InvalidArgumentException('Source initialization belongs to prepared interface declarations');
		}
		$arity = 0;
		if ($target_definition instanceof abstract_template_type_definition) {
			$template_definition = object_cast($target_definition, abstract_template_type_definition::class);
			$arity = $template_definition->arity();
		}
		self::require_reference($value_reference, $arity);
		self::require_reference($nested_reference, $arity);
		if ($key_reference !== null) { self::require_reference($key_reference, $arity); }
		foreach ($argument_constraints as $position => $constraint) {
			if (($position < 0) || ($position >= $arity)) {
				throw new \InvalidArgumentException('Initialization constraint references absent formal');
			}
		}
	}

	private static function require_reference(initialization_type_reference $reference, int $arity): void
	{
		if ($reference->source() === initialization_type_source::argument) {
			if ($reference->position() >= $arity) {
				throw new \InvalidArgumentException('Initialization reference exceeds target arity');
			}
		}
	}

	public function definition(): type_definition_i { return $this->target_definition; }
	public function value(): initialization_type_reference { return $this->value_reference; }
	public function key(): ?initialization_type_reference { return $this->key_reference; }
	public function nested(): initialization_type_reference { return $this->nested_reference; }
	public function constraints(): array /** hash<canonical_type_use, int> */ { return $this->argument_constraints; }
	public function is_keyed(): bool { return $this->key_reference !== null; }
	public function factory_key(): string { return $this->factory_identity; }
}

/** Concrete recognized interface arguments; the callable owns the sole signature. */
final class initialization_binding
{
	public function __construct(private canonical_type_use $target_type,
		private canonical_type_use $value_type, private ?canonical_type_use $key_type,
		private canonical_type_use $nested_type, private callable_target_i $factory_target)
	{
	}

	public function target(): canonical_type_use { return $this->target_type; }
	public function value(): canonical_type_use { return $this->value_type; }
	public function key(): ?canonical_type_use { return $this->key_type; }
	public function nested_target(): canonical_type_use { return $this->nested_type; }
	public function factory(): callable_target_i { return $this->factory_target; }
	public function is_keyed(): bool { return $this->key_type !== null; }
}
