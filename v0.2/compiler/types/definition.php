<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Shared semantic type contract; canonical definitions own type-specific behavior, never occurrence facts. */
abstract class type_definition
{
	/** PROPERTIES */
	public readonly string $name;

	/** CONSTRUCTOR */
	protected function __construct(string $name)
	{
		$this->name = $name;
	}

	/** METHODS */
	/** Definitions are canonical shared identities, not copyable per-expression records. */
	final protected function __clone(): void
	{
	}

	public function accepts_name(string $name): bool
	{
		return $name === $this->name;
	}

	/** Unsupported literal kinds fail during preparation; successful overrides attach facts to the AST. */
	public function prepare_literal(ast_expression $syntax): void
	{
		throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported literal for type: ' . $this->name);
	}

	/** Native representation of an already resolved language type. */
	abstract public function cpp_type(): string;

	/** Lower only settled literals; a missing implementation is an internal error, not source admission. */
	public function lower_cpp_literal(ast_expression $syntax): string
	{
		throw new \LogicException('Literal lowering is unavailable for type: ' . $this->name);
	}
}
