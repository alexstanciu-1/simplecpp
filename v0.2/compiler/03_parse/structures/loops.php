<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Any repeating statement owns a lexical body and can be the target of a loop transfer. */
abstract class ast_repeating_statement extends ast_statement
{
	public readonly ast_block $body;

	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_block $body)
	{
		parent::__construct($start, $end);
		$this->body = $body;
		$body->attach($this);
	}
}

/** Pre-test and post-test loops share the body and transfer target. */
abstract class ast_loop_statement extends ast_repeating_statement
{
}

/** While and do-while each own exactly one required condition. */
abstract class ast_tested_loop extends ast_loop_statement
{
	public readonly ast_expression $condition;

	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_expression $condition, ast_block $body)
	{
		parent::__construct($start, $end, $body);
		$this->condition = $condition;
		$condition->attach($this);
	}
}

/** Test before every iteration: while (running) { running = false; }. */
final class ast_while_statement extends ast_tested_loop
{
}

/** Execute the body before testing: do { echo "once"; } while (false); */
final class ast_do_while_statement extends ast_tested_loop
{
}

/** Each for clause owns its ordered syntax. An empty test list repeats until control leaves the loop. */
final class ast_for_statement extends ast_loop_statement
{
	/** @param ast_statement[] $initializers
	 * @param ast_expression[] $conditions
	 * @param ast_statement[] $updates
	 */
	public function __construct(int $start /** uint32 */, public readonly array $initializers /** list<ast_statement> */,
		public readonly array $conditions /** list<ast_expression> */, public readonly array $updates /** list<ast_statement> */,
		ast_block $body, public readonly scope $scope)
	{
		parent::__construct($start, $body->end, $body);
		foreach ($initializers as $item) $item->attach($this);
		foreach ($conditions as $item) $item->attach($this);
		foreach ($updates as $item) $item->attach($this);
	}
}

/** Unlabelled transfer resolved to the innermost lexical repeating statement. */
abstract class ast_loop_transfer extends ast_statement
{
	/** Prepared fact: native non-owning target; null until resolved or after invalidation. */
	public ast_switch_statement|ast_repeating_statement|null $target = null;
}

/** Bare break; exits the resolved loop without running its update or test. */
final class ast_break_statement extends ast_loop_transfer
{
}

/** Bare continue; reaches the nearest loop's next test, including a do-while trailing condition. */
final class ast_continue_statement extends ast_loop_transfer
{
}

/** foreach (items as key => value) owns its source, produced bindings and braced body. */
final class ast_foreach_statement extends ast_repeating_statement
{
	/** PROPERTIES */
	public readonly ast_expression $source_value;
	public readonly ?ast_produced_binding $key;
	public readonly ast_produced_binding $value;
	public readonly scope $scope;

	/** PREP FACTS */
	/** Prepared fact: non-owning existing local/parameter or this header's value; null until prepared. */
	public ?ast_local_binding $destination = null;
	/** Prepared fact: approved per-element assignment; null until prepared. */
	public ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $start: foreach token position. Children are owned syntax; scope indexes those children. */
	public function __construct(int $start /** uint32 */, ast_expression $source, ?ast_produced_binding $key,
		ast_produced_binding $value, ast_block $body, scope $scope)
	{
		parent::__construct($start, $body->end, $body);
		$this->source_value = $source;
		$this->key = $key;
		$this->value = $value;
		$this->scope = $scope;
		$source->attach($this);
		$key?->attach($this);
		$value->attach($this);
	}
}
