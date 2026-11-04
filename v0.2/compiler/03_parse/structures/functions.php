<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * A no-argument invocation in statement position; preparation selects the callable identity.
 *
 * Call syntax:
 * ```text
 * ping();
 * ```
 * function_def() resolves the stable ping symbol to its current settled declaration; its body is not read.
 */
final class ast_call_statement extends ast_statement
{
	/** PROPERTIES */
	/** Canonical reference spelling; a leading backslash marks an explicitly rooted path. */
	public readonly string $name;
	/** Dotted value-or-namespace syntax; preparation must respect visible local bindings. */
	public readonly bool $member_syntax;

	/** PREP FACTS */
	/**
	 * Prepared cache: stable symbol identity, resolved through its current declaration; null before
	 * body preparation or after reset.
	 * Native non-owning; identity retained by compilation scope.
	 */
	protected ?symbol_entry $function_symbol = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		string $name, bool $member_syntax = false)
	{
		$this->name = $name;
		$this->member_syntax = $member_syntax;
		parent::__construct($start, $end);
	}

	/** METHODS */
	/** Canonical lookup key; preparation attaches its stable symbol entry. */
	public function symbol_key(): string
	{
		return $this->root()->function_key($this->name);
	}

	/** Attach the stable symbol only after its current function contract is available. */
	public function settle_function(symbol_entry $symbol): void
	{
		if ($this->function_symbol !== null) {
			throw new \LogicException('Call target is already settled');
		}
		/** @var ast_function_declaration $function */
		$function = $symbol->declaration() ?? throw new \LogicException('Cannot settle a removed function');
		$function->return_type_def();
		$this->function_symbol = $symbol;
	}

	public function function_def(): ast_function_declaration
	{
		if ($this->parent instanceof ast_function_body) {
			$this->parent->require_prepared();
		}
		return $this->function_symbol?->declaration() ?? throw new \LogicException('Call target is not settled or was removed');
	}

	public function reset_preparation(): void
	{
		$this->function_symbol = null;
	}

}

/**
 * Source-owned body syntax and local collection are published together after its closing brace.
 *
 * PHP-flavor body:
 * ```text
 * function ping(): void { echo 8; return; }
 * ```
 * The body owns the echo and return statements, local collection and completed body facts.
 */
final class ast_function_body extends ast_node implements dependency_watcher
{
	/** PROPERTIES */
	/** Native owning; body declaration scope. */
	public readonly scope $scope;
	/** @var ast_statement[] Native owning syntax children. */
	public readonly array $statements /** list<ast_statement> */;
	/** @var ast_local_declaration[] Keyed by body statement position; native non-owning statement index. */
	public readonly array $locals /** array<uint32,ast_local_declaration> */;
	/** @var ast_name_expression[] Native non-owning occurrence index. */
	public readonly array $references /** list<ast_name_expression> */;

	/** PREP FACTS */
	/** Preparation state: true only after successful whole-body preparation. */
	private bool $prepared = false;
	/** Prepared cache: entry-target context; null for ordinary void bodies. */
	private ?int $exit_digits /** uint8 */ = null;
	/** Preparation statistics: statements visited by the last successful body preparation; zero before completion. */
	public int $statement_visits /** uint32 */ = 0;
	/** Preparation statistics: expressions visited by the last successful body preparation; zero before completion. */
	public int $expression_visits /** uint32 */ = 0;
	/** Preparation statistics: name lookups performed by the last successful body preparation; zero before completion. */
	public int $name_lookups /** uint32 */ = 0;

	/** CONSTRUCTOR */
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

	/** METHODS */
	/** Invalidate completion only; observers never rebuild or traverse cached statements here. */
	public function invalidate_dependencies(): void
	{
		$this->prepared = false;
		$this->root()->begin_preparation();
	}

	public function is_prepared(?entry_target $target = null): bool
	{
		return ($this->prepared) && ($this->exit_digits === $target?->digits);
	}

	public function reset_preparation(): void
	{
		$this->prepared = false;
		$this->statement_visits = $this->expression_visits = $this->name_lookups = 0;
	}

	/** Publish body completion and the target context only after all statement facts settle. */
	public function settle_preparation(int $statements /** uint32 */, int $expressions /** uint32 */, int $lookups /** uint32 */,
		?entry_target $target = null): void
	{
		if ($this->prepared) {
			throw new \LogicException('Function body is already prepared');
		}
		$this->statement_visits = $statements;
		$this->expression_visits = $expressions;
		$this->name_lookups = $lookups;
		$this->exit_digits = $target?->digits;
		$this->prepared = true;
	}

	public function require_prepared(): void
	{
		if (!$this->prepared) {
			throw new \LogicException('Function preparation is not complete');
		}
	}

}

/**
 * Stable discovery identity: header fields never change, and only its source owner fills later slots.
 *
 * PHP-flavor function:
 * ```text
 * function ping(): void { return; }
 * ```
 * short_name is ping, return_type_name is void, and return_type_def() is unavailable until declaration preparation.
 */
final class ast_function_declaration extends ast_node implements ast_declaration_interface
{
	use ast_declaration;

	/** PROPERTIES */
	public readonly bool $is_private;
	public readonly bool $is_implicit_entry;
	/** Written return type name; absence is distinct from an unresolved return type. */
	public readonly ?string $return_type_name;
	/** Exclusive header token boundary. */
	public readonly int $header_end /** uint32 */;
	/** Native owning; function syntax child. */
	private ?ast_function_body $body = null;

	/** PREP FACTS */
	/** Prepared fact: canonical return type; settled before any body consumes this declaration. */
	protected ?type_definition $return_type_def = null;

	/** CONSTRUCTOR */
	/**
	 * Initialize syntax identity and attach supplied children to their owning node.
	 *
	 * $start: token position.
	 * $header_end: exclusive token position.
	 */
	public function __construct(string $name, ?string $return_type_name, int $start /** uint32 */,
		int $header_end /** uint32 */,
		bool $is_implicit_entry = false, ?string $namespace = null)
	{
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->is_private = $is_implicit_entry;
		$this->is_implicit_entry = $is_implicit_entry;
		$this->return_type_name = $return_type_name;
		$this->header_end = $header_end;
		parent::__construct($start, $header_end, true);
	}

	/** METHODS */
	/** Canonical global handle; the local declaration key remains usable by source-scope indexes. */
	public function symbol_key(): string
	{
		return 'func:' . ($this->is_implicit_entry ? 'main' : $this->name());
	}

	/** Attach the completed body once and settle the function token boundary. */
	public function complete_body(ast_function_body $body): void
	{
		if (($this->body !== null) || ($body->start < $this->start)) {
			throw new \LogicException('Invalid function body publication');
		}
		$body->attach($this);
		$this->end = $body->end;
		$this->body = $body;
	}

	public function body(): ast_function_body
	{
		return $this->body ?? throw new \LogicException('Function body is not complete');
	}

	/** Start a new sequential analysis run; incremental reader quiescence is not implemented here. */
	public function reset_preparation(): void
	{
		$this->return_type_def = null;
		$this->body()->reset_preparation();
	}

	public function settle_return_type(type_definition $type): void
	{
		if ($this->return_type_def !== null) {
			throw new \LogicException('Function return type is already settled');
		}
		$this->return_type_def = $type;
	}

	public function return_type_ready(): bool
	{
		return $this->return_type_def !== null;
	}

	public function return_type_def(): type_definition
	{
		return $this->return_type_def ?? throw new \LogicException('Function return type is not settled');
	}

	/** Return: token position. */
	public function name_position(): int /** uint32 */
	{
		return $this->is_implicit_entry ? $this->start : $this->start + 1;
	}

	/** Return: token position. */
	public function return_type_position(): int /** uint32 */
	{
		return (($this->is_implicit_entry) || ($this->source()->flavor === flavor::cpp))
			? $this->start : $this->header_end - 1;
	}
}

/**
 * Return syntax retains an optional value; analysis selects the enclosing body's return contract.
 *
 * Return syntax:
 * ```text
 * return 8;
 * return;
 * ```
 * Preparation admits the value form for the entry and the bare form for void functions.
 */
final class ast_return_statement extends ast_statement
{
	/** PROPERTIES */
	/** Native owning; optional return expression child. */
	public readonly ?ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: entry conversion; null before preparation or for a void return. */
	protected ?exit_conversion $conversion = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		?ast_expression $value)
	{
		$this->value = $value;
		$value?->attach($this);
		parent::__construct($start, $end);
	}

	/** METHODS */
	public function settle_conversion(exit_conversion $conversion): void
	{
		if ($this->conversion !== null) {
			throw new \LogicException('Return conversion already settled');
		}
		$this->conversion = $conversion;
	}

	public function conversion(): exit_conversion
	{
		return $this->conversion ?? throw new \LogicException('Entry conversion is not settled');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
	}
}
