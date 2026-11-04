<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Collects source-order body indexes; declaration registration/publication is delegated to Scope_Collection. */
final class Body_Collection
{
	/** PROPERTIES */
	/** @var ast_local_declaration[] Keyed by statement position; native non-owning references to body syntax. */
	private array $locals /** array<uint32,ast_local_declaration> */ = [];
	/** Native owning; body-local collection worker. */
	private readonly Scope_Collection $declarations;
	/** @var ast_name_expression[] Native non-owning expression references. */
	private array $references /** list<ast_name_expression> */ = [];

	/** CONSTRUCTOR */
	public function __construct(scope $parent)
	{
		$this->declarations = new Scope_Collection(new scope($parent));
	}

	/** METHODS */
	/** Record one completed statement during parsing, without lookup, typing or publication outside its source. */
	public function statement(ast_statement $statement, int $position /** uint32 */): void
	{
		if ($statement instanceof ast_call_statement) {
			return;
		}
		if ($statement instanceof ast_local_declaration) {
			$this->declarations->declare($statement);
			$statement->identify(count($this->locals));
			$this->locals[$position] = $statement;
			$value = $statement->initializer;
		}
		elseif (($statement instanceof ast_return_statement) || ($statement instanceof ast_assignment_statement)
			|| ($statement instanceof ast_echo_statement)) {
			$value = $statement->value;
		}
		else {
			throw new \LogicException('Unhandled statement collection');
		}
		if ($value instanceof ast_name_expression) {
			$this->references[] = $value;
		}
	}

	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 *
	 * @param ast_statement[] $statements
	 */
	public function body(int $start /** uint32 */, int $end /** uint32 */,
		array $statements /** list<ast_statement> */): ast_function_body
	{
		return new ast_function_body($start, $end, $statements, $this->locals, $this->references, $this->declarations->publish());
	}
}
