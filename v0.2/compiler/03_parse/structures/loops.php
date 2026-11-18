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

/** Bounded for (int i = 0; i < 10; i = i + 1) { ... }; header scope owns the initializer binding. */
final class ast_for_statement extends ast_loop_statement
{
	/** Native owning initializer; executes once, before the first test. */
	public readonly ast_local_declaration|ast_storage_write $initializer;
	/** Native owning update; executes after normal body completion or continue. */
	public readonly ast_storage_write $update;
	/** Native owning header scope; the body scope is its child. */
	public readonly scope $scope;

	/** $start: for token position; the body supplies the exclusive end. */
	public function __construct(int $start /** uint32 */, ast_local_declaration|ast_storage_write $initializer,
		ast_expression $condition, ast_storage_write $update, ast_block $body, scope $scope)
	{
		parent::__construct($start, $body->end, $condition, $body);
		$this->initializer = $initializer;
		$this->update = $update;
		$this->scope = $scope;
		$initializer->attach($this);
		$update->attach($this);
	}
}

/** Bare break; exits the nearest enclosing loop. No separate target fact is needed for current loop-only syntax. */
final class ast_break_statement extends ast_statement
{
}

/** Bare continue; reaches the nearest loop's next test, including a do-while trailing condition. */
final class ast_continue_statement extends ast_statement
{
}

/** Produced value in foreach (items as value); its fact drives conversion and it supplies new loop-local storage. */
final class ast_iteration_binding extends ast_expression implements ast_local_binding
{
	use ast_declaration;

	public readonly int $id /** uint32 */;

	/** $start: name token position; $id: function-local storage ordinal. */
	public function __construct(string $name, int $start /** uint32 */, int $id /** uint32 */)
	{
		parent::__construct($start, $start + 1);
		$this->short_name = $name;
		$this->namespace = null;
		$this->id = $id;
	}

	public function storage_id(): int /** uint32 */
	{
		return $this->id;
	}

	public function symbol_key(): string
	{
		return 'var:' . $this->declaration_key();
	}

	public function settle_type(type_definition $type): void
	{
		$this->settle_value($type, null);
	}
}

/** foreach (items as key => value) owns its source, produced bindings and braced body. */
final class ast_foreach_statement extends ast_statement
{
	/** PROPERTIES */
	public readonly ast_expression $source_value;
	public readonly ?ast_iteration_binding $key;
	public readonly ast_iteration_binding $value;
	public readonly ast_block $body;
	public readonly scope $scope;

	/** PREP FACTS */
	/** Prepared fact: non-owning existing local/parameter or this header's value; null until prepared. */
	public ?ast_local_binding $destination = null;
	/** Prepared fact: approved per-element assignment; null until prepared. */
	public ?int $conversion /** uint16 */ = null;

	/** CONSTRUCTOR */
	/** $start: foreach token position. Children are owned syntax; scope indexes those children. */
	public function __construct(int $start /** uint32 */, ast_expression $source, ?ast_iteration_binding $key,
		ast_iteration_binding $value, ast_block $body, scope $scope)
	{
		parent::__construct($start, $body->end);
		$this->source_value = $source;
		$this->key = $key;
		$this->value = $value;
		$this->body = $body;
		$this->scope = $scope;
		$source->attach($this);
		$key?->attach($this);
		$value->attach($this);
		$body->attach($this);
	}
}
