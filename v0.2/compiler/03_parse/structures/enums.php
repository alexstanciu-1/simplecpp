<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Compile-time type operand, e.g. Kind::class; never a runtime string or value. */
final class ast_type_marker extends ast_expression
{
	public readonly string $name;

	public function __construct(string $name, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->name = $name;
	}
}

/** One owned named case; its literal AST owns the checked integer value, e.g. case Number = 300. */
final class ast_enum_case extends ast_node implements ast_declaration_interface
{
	use ast_declaration;

	public readonly ast_integer_expression $initializer;

	public function __construct(string $name, ast_integer_expression $initializer, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = null;
		$this->initializer = $initializer;
		$initializer->attach($this);
	}

	public function symbol_key(): string
	{
		return $this->short_name;
	}
}

/** Nominal integer enum; cases are owned here, never published as namespace constants. */
final class ast_enum_declaration extends ast_node implements ast_type_declaration
{
	use ast_declaration;

	/** PROPERTIES */
	public readonly ?ast_type_reference $backing;
	/** @var ast_enum_case[] Owning name index in source order; duplicate chains belong to this declaration. */
	public readonly array $cases /** array<string,ast_enum_case> */;
	public readonly enum_type_definition $definition;

	/** CONSTRUCTOR */
	/** @param ast_enum_case[] $cases */
	public function __construct(string $name, ?string $namespace, ?ast_type_reference $backing,
		array $cases /** array<string,ast_enum_case> */, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->backing = $backing;
		$backing?->attach($this);
		$this->cases = $cases;
		foreach ($cases as $head) {
			for ($case = $head; $case !== null; $case = $case->duplicate_element()) $case->attach($this);
		}
		$this->definition = new enum_type_definition($this);
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return 'class:' . $this->name();
	}

	public function name_position(): int /** uint32 */
	{
		return $this->start + 1;
	}

	public function type_definition(): type_definition
	{
		return $this->definition;
	}
}
