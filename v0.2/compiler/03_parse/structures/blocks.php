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

/** One lexical clause; its expression is absent for else or default. */
abstract class ast_branch_arm extends ast_node
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
abstract class ast_branch_statement extends ast_statement
{
	/** @var ast_branch_arm[] Native owning syntax; multiple arms are valid. */
	public readonly array $arms /** list<ast_branch_arm> */;

	/** $start/$end: token positions. @param ast_branch_arm[] $arms */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, array $arms /** list<ast_branch_arm> */)
	{
		parent::__construct($start, $end);
		$this->arms = $arms;
		foreach ($arms as $arm) {
			$arm->attach($this);
		}
	}
}

final class ast_if_arm extends ast_branch_arm
{
}

final class ast_if_statement extends ast_branch_statement
{
}

final class ast_switch_arm extends ast_branch_arm
{
	/** Prepared flow fact for an explicit native fallthrough annotation. */
	public bool $falls_through = false;
}

final class ast_switch_statement extends ast_branch_statement
{
	public function __construct(int $start, int $end, public readonly ast_expression $subject, array $arms)
	{
		parent::__construct($start, $end, $arms);
		$subject->attach($this);
	}
}

/** Lexical storage populated by a foreach or catch header. */
final class ast_produced_binding extends ast_expression implements ast_local_binding
{
	use ast_declaration;

	/** PROPERTIES */
	public readonly int $id /** uint32 */;

	/** CONSTRUCTOR */
	/** $start: name token position; $id: function-local storage ordinal. */
	public function __construct(string $name, int $start /** uint32 */, int $id /** uint32 */)
	{
		parent::__construct($start, $start + 1);
		$this->short_name = $name;
		$this->namespace = null;
		$this->id = $id;
	}

	/** METHODS */
	public function name_position(): int /** uint32 */
	{
		return $this->start;
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

/** A throw operation exits without a value; only a statement or a fallback owns it. */
final class ast_throw_expression extends ast_expression
{
	public readonly ast_expression $operand;

	/** Prepared fact: root view conversion; null until analyzed. No value type is produced. */
	public ?int $conversion /** uint16 */ = null;

	public function __construct(int $start /** uint32 */, ast_expression $operand)
	{
		parent::__construct($start, $operand->end);
		$this->operand = $operand;
		$operand->attach($this);
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversion = null;
	}
}

/** A standalone throw owns the same operation as a fallback plus the statement terminator. */
final class ast_throw_statement extends ast_statement
{
	public readonly ast_throw_expression $operation;

	public function __construct(ast_throw_expression $operation, int $end /** uint32 */)
	{
		parent::__construct($operation->start, $end);
		$this->operation = $operation;
		$operation->attach($this);
	}
}

/** Typed handler owns the binding and its lexical body. */
final class ast_catch_clause extends ast_node
{
	public readonly ast_type_reference $type;
	public readonly ast_produced_binding $binding;
	public readonly ast_block $body;

	/** Attach the written type, its binding and the sibling handler body. */
	public function __construct(int $start /** uint32 */, ast_type_reference $type, ast_produced_binding $binding, ast_block $body)
	{
		parent::__construct($start, $body->end);
		$this->type = $type;
		$this->binding = $binding;
		$this->body = $body;
		$type->attach($this);
		$binding->attach($this);
		$body->attach($this);
	}
}

/** Protected body with a typed catch, a finally block, or both. */
final class ast_try_statement extends ast_statement
{
	/** PROPERTIES */
	public readonly ast_block $body;
	public readonly ?ast_catch_clause $handler;
	public readonly ?ast_block $finalizer;

	/** PREP FACTS */
	/** Prepared fact: exits from the protected operation before the finalizer; null until settled. */
	public ?int $protected_completion /** uint8 */ = null;
	/** Prepared fact: exits remaining after the finalizer; null until settled. */
	public ?int $completion /** uint8 */ = null;
	/** Prepared non-owning destinations of transfers escaping the protected operation. */
	public ast_repeating_statement|ast_switch_statement|null $break_target = null;
	public ?ast_repeating_statement $continue_target = null;

	/** CONSTRUCTOR */
	/** Attach disjoint lexical regions; an empty written finalizer is still a block. */
	public function __construct(int $start /** uint32 */, ast_block $body, ?ast_catch_clause $handler, ?ast_block $finalizer)
	{
		parent::__construct($start, ($finalizer ?? $handler)->end);
		$this->body = $body;
		$this->handler = $handler;
		$this->finalizer = $finalizer;
		$body->attach($this);
		$handler?->attach($this);
		$finalizer?->attach($this);
	}
}
