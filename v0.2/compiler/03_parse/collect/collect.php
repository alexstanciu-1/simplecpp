<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Collects declaration IDs and name-reference occurrences as statements are parsed, without resolving names or types.
 */
final class Symbol_Collector
{
	private array $locals /** array<uint32,ast_local_declaration>; keyed by statement position */ = [];
	private array $references /** list<ast_name_expression> */ = [];
	/** Record one completed statement during parsing, without lookup, typing or publication outside its source. */
	public function statement(ast_statement $statement, int $position /** uint32 */): void
	{
		if ($statement instanceof ast_call_statement) {
			return;
		}
		if ($statement instanceof ast_local_declaration) {
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

	public function body(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */, array $statements /** list<ast_statement> */): ast_function_body
	{
		return new ast_function_body($start, $end, $statements, $this->locals, $this->references);
	}
}
