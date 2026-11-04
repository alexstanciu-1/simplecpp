<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Collects source-order body indexes; declaration registration/publication is delegated to Scope_Collection. */
final class Body_Collection
{
	/** PROPERTIES */
	/** @var ast_local_declaration[] Keyed by statement position; native non-owning references to body syntax. */
	private array $locals /** array<uint32,ast_local_declaration> */ = [];
	/** @var ast_local_binding[] Prior local heads; native non-owning name index, not another duplicate owner. */
	private array $local_names /** array<string,ast_local_binding> */ = [];
	/** Native owning; body-local collection worker. */
	private readonly Scope_Collection $declarations;
	private int $parameter_count /** uint32 */;
	/** @var ast_name_expression[] Native non-owning expression references. */
	private array $references /** list<ast_name_expression> */ = [];

	/** CONSTRUCTOR */
	/**
	 * Seed body scope with signature-owned parameters before local statements.
	 * @param ast_parameter_declaration[] $parameters
	 */
	public function __construct(scope $parent, array $parameters /** list<ast_parameter_declaration> */ = [])
	{
		$this->declarations = new Scope_Collection(new scope($parent));
		$this->parameter_count = count($parameters);
		foreach ($parameters as $parameter) {
			$this->declarations->declare($parameter);
			$this->local_names[$parameter->declaration_key()] ??= $parameter;
		}
	}

	/** METHODS */
	/** Classify a PHP bare write using only declarations already collected in this body. */
	public function has_local(string $name): bool
	{
		return isset($this->local_names[$name]);
	}

	/** Record one completed statement during parsing, without lookup, typing or publication outside its source. */
	public function statement(ast_statement $statement, int $position /** uint32 */): void
	{
		if ($statement instanceof ast_local_declaration) {
			$this->declarations->declare($statement);
			$this->local_names[$statement->declaration_key()] ??= $statement;
			$statement->identify(count($this->locals) + $this->parameter_count);
			$this->locals[$position] = $statement;
			$value = $statement->initializer;
		}
		elseif (($statement instanceof ast_return_statement) || ($statement instanceof ast_assignment_statement)
			|| ($statement instanceof ast_echo_statement) || ($statement instanceof ast_call_statement)) {
			$value = $statement->value;
		}
		else {
			throw new \LogicException('Unhandled statement collection');
		}
		if ($value !== null) {
			$this->expression($value);
		}
	}

	/** Collect nested argument references without resolving declarations or inspecting foreign bodies. */
	private function expression(ast_expression $value): void
	{
		if ($value instanceof ast_call_expression) {
			foreach ($value->arguments as $argument) {
				$this->expression($argument);
			}
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
