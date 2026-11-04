<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Shared owning loop syntax; iteration order is represented by the concrete node, not prepared copies. */
abstract class ast_loop_statement extends ast_statement
{
	/** Native owning test expression, evaluated outside body-local scope. */
	public readonly ast_expression $condition;
	/** Native owning braced lexical scope. */
	public readonly ast_block $body;

	/** $start/$end: token positions, including the trailing semicolon for do-while. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $condition, ast_block $body)
	{
		parent::__construct($start, $end);
		$this->condition = $condition;
		$this->body = $body;
		$condition->attach($this);
		$body->attach($this);
	}
}

/** Test before every iteration: while (running) { running = false; }. */
final class ast_while_statement extends ast_loop_statement
{
}

/** Execute the body before testing: do { echo "once"; } while (false); */
final class ast_do_while_statement extends ast_loop_statement
{
}
