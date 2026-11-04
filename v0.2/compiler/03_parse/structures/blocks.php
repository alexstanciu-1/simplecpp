<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Common owning statement sequence and lexical scope; function bodies add completion and dependency state. */
abstract class ast_statement_block extends ast_node
{
	/** Native owning; body declaration scope. */
	public readonly scope $scope;
	/** @var ast_statement[] Native owning syntax children. */
	public readonly array $statements /** list<ast_statement> */;
	/** @var ast_local_declaration[] Keyed by body statement position; native non-owning statement index. */
	public readonly array $locals /** array<uint32,ast_local_declaration> */;
	/** @var ast_name_expression[] Native non-owning occurrence index. */
	public readonly array $references /** list<ast_name_expression> */;

	/**
	 * Initialize syntax identity and attach supplied children to their owning node.
	 *
	 * $start: token position.
	 * $end: exclusive token position.
	 *
	 * @param ast_statement[] $statements
	 * @param ast_local_declaration[] $locals
	 * @param ast_name_expression[] $references
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		array $statements /** list<ast_statement> */,
		array $locals /** array<uint32,ast_local_declaration> */, array $references /** list<ast_name_expression> */,
		scope $scope)
	{
		parent::__construct($start, $end);
		foreach ($statements as $statement) {
			$statement->attach($this);
		}
		$this->statements = $statements;
		$this->locals = $locals;
		$this->references = $references;
		$this->scope = $scope;
	}

}

/** A nested braced lexical body: if (flag) { int value = 8; }. */
final class ast_block extends ast_statement_block
{
}

/** One owned conditional arm; condition is absent only for the final else arm. */
final class ast_if_arm extends ast_node
{
	public readonly ?ast_expression $condition;
	public readonly ast_block $body;

	/** $start/$end: token positions. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ?ast_expression $condition, ast_block $body)
	{
		parent::__construct($start, $end);
		$this->condition = $condition;
		$this->body = $body;
		$condition?->attach($this);
		$body->attach($this);
	}
}

/** Owns a valid ordered sequence of alternatives: if (...) {...} elseif (...) {...} else {...}. */
final class ast_if_statement extends ast_statement
{
	/** @var ast_if_arm[] Native owning syntax; multiple arms are valid. */
	public readonly array $arms /** list<ast_if_arm> */;

	/** $start/$end: token positions. @param ast_if_arm[] $arms */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, array $arms /** list<ast_if_arm> */)
	{
		parent::__construct($start, $end);
		$this->arms = $arms;
		foreach ($arms as $arm) {
			$arm->attach($this);
		}
	}
}
