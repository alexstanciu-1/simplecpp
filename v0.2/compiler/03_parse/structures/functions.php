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
	public readonly string $name;
	/** Prepared cache: stable symbol identity, resolved through its current declaration; null before body preparation or after reset. */
	protected ?symbol_entry $function_symbol = null;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */, string $name)
	{
		$this->name = $name;
		parent::__construct($start, $end);
	}

	/** Canonical lookup key; preparation attaches its stable symbol entry. */
	public function symbol_key(): string
	{
		return 'func:' . $this->name;
	}

	public function settle_function(symbol_entry $symbol): void
	{
		if ($this->function_symbol !== null) { throw new \LogicException('Call target is already settled'); }
		$function = $symbol->declaration() ?? throw new \LogicException('Cannot settle a removed function');
		$function->return_type_def();
		$this->function_symbol = $symbol;
	}

	public function function_def(): ast_function_declaration
	{
		if ($this->parent instanceof ast_function_body) { $this->parent->require_prepared(); }
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
final class ast_function_body extends ast_node
{
	public readonly scope $scope;
	public readonly array $statements /** list<ast_statement> */;
	public readonly array $locals /** array<uint32,ast_local_declaration>; keyed by body statement position */;
	public readonly array $references /** list<ast_name_expression> */;

	/** Preparation statistics: zero until successful whole-body preparation; completion is checked separately. */
	private bool $prepared = false;
	private ?int $exit_digits /** uint8; cached entry-target context, null for ordinary void bodies */ = null;
	public int $statement_visits /** uint32 */ = 0;
	public int $expression_visits /** uint32 */ = 0;
	public int $name_lookups /** uint32 */ = 0;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */, array $statements /** list<ast_statement> */,
		array $locals /** array<uint32,ast_local_declaration> */, array $references /** list<ast_name_expression> */, scope $scope)
	{
		parent::__construct($start, $end);
		foreach ($statements as $statement) { $statement->attach($this); }
		$this->statements = $statements;
		$this->locals = $locals;
		$this->references = $references;
		$this->scope = $scope;
	}
	/** Invalidate completion only; observers never rebuild or traverse cached statements here. */
	public function invalidate_preparation(): void
	{
		$this->prepared = false;
		$this->root()->begin_preparation();
	}
	public function is_prepared(?entry_target $target = null): bool
	{
		return $this->prepared && ($this->exit_digits === $target?->digits);
	}
	public function reset_preparation(): void
	{
		$this->prepared = false;
		$this->statement_visits = $this->expression_visits = $this->name_lookups = 0;
	}
	public function settle_preparation(int $statements /** uint32 */, int $expressions /** uint32 */, int $lookups /** uint32 */, ?entry_target $target = null): void
	{
		if ($this->prepared) { throw new \LogicException('Function body is already prepared'); }
		$this->statement_visits = $statements;
		$this->expression_visits = $expressions;
		$this->name_lookups = $lookups;
		$this->exit_digits = $target?->digits;
		$this->prepared = true;
	}
	public function require_prepared(): void
	{
		if (!$this->prepared) { throw new \LogicException('Function preparation is not complete'); }
	}

}

/**
 * Stable discovery identity: header fields never change, and only its source owner fills later slots.
 *
 * PHP-flavor function:
 * ```text
 * function ping(): void { return; }
 * ```
 * name is ping, return_type_name is void, and return_type_def() is unavailable until declaration preparation.
 */
final class ast_function_declaration extends ast_node implements ast_declaration_interface
{
	use ast_declaration;
	public readonly bool $is_private;
	public readonly bool $is_implicit_entry;
	/** Written return type name; absence is distinct from an unresolved return type. */
	public readonly ?string $return_type_name;
	public readonly int $header_end /** uint32; exclusive header token boundary */;
	private ?ast_function_body $body = null;
	/** Prepared fact: canonical return type; settled before any body consumes this declaration. */
	protected ?type_definition $return_type_def = null;

	public function __construct(string $name, ?string $return_type_name, int $start /** uint32; token position */, int $header_end /** uint32; exclusive token position */,
		bool $is_implicit_entry = false)
	{
		$this->name = $name;
		$this->is_private = $is_implicit_entry;
		$this->is_implicit_entry = $is_implicit_entry;
		$this->return_type_name = $return_type_name;
		$this->header_end = $header_end;
		parent::__construct($start, $header_end, true);
	}

	/** Canonical global handle; the local declaration key remains usable by source-scope indexes. */
	public function symbol_key(): string
	{
		return 'func:' . $this->declaration_key();
	}

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
		if ($this->return_type_def !== null) { throw new \LogicException('Function return type is already settled'); }
		$this->return_type_def = $type;
	}

	public function return_type_ready(): bool { return $this->return_type_def !== null; }

	public function return_type_def(): type_definition
	{
		return $this->return_type_def ?? throw new \LogicException('Function return type is not settled');
	}

	public function name_position(): int /** uint32; token position */
	{
		return $this->is_implicit_entry ? $this->start : $this->start + 1;
	}

	public function return_type_position(): int /** uint32; token position */
	{
		return ($this->is_implicit_entry || ($this->source()->flavor === flavor::cpp))
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
	/** Prepared fact: entry conversion; null before preparation or for a void return. */
	protected ?exit_conversion $conversion = null;
	public function settle_conversion(exit_conversion $conversion): void
	{
		if ($this->conversion !== null) { throw new \LogicException('Return conversion already settled'); }
		$this->conversion = $conversion;
	}
	public function conversion(): exit_conversion
	{
		return $this->conversion ?? throw new \LogicException('Entry conversion is not settled');
	}
	public function reset_preparation(): void { $this->conversion = null; }

	public readonly ?ast_expression $value;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */,
		?ast_expression $value)
	{
		$this->value = $value;
		$value?->attach($this);
		parent::__construct($start, $end);
	}
}
