<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Statement-position invocation owns the same call expression used in value positions: ping(); */
final class ast_call_statement extends ast_statement
{
	/** Native owning call expression. */
	public readonly ast_call_expression $value;

	/** $start/$end: statement token positions including its semicolon. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_call_expression $value)
	{
		parent::__construct($start, $end);
		$this->value = $value;
		$value->attach($this);
	}

	public function symbol_key(): string
	{
		return $this->value->symbol_key();
	}

	/** Inspection requires completion of the enclosing function, including calls inside nested arms. */
	public function function_def(): ast_function_declaration
	{
		$owner = $this->parent;
		while (!($owner instanceof ast_function_body)) {
			$owner = $owner->parent;
		}
		$owner->require_prepared();
		return $this->value->function_def();
	}
}

/** A named invocation owns arguments and their conversion facts; example: identity("hello"). */
final class ast_call_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly string $name;
	public readonly bool $member_syntax;
	/** @var ast_expression[] Native owning arguments in source order. */
	public readonly array $arguments /** list<ast_expression> */;

	/** PREP FACTS */
	/** Prepared fact: native non-owning stable symbol; null before preparation or after reset. */
	private ?symbol_entry $function_symbol = null;
	/** @var value_conversion[] Prepared fact: approved positional conversions; empty before preparation or after reset. */
	private array $conversions /** list<value_conversion> */ = [];

	/** CONSTRUCTOR */
	/**
	 * $start/$end: token positions.
	 * @param ast_expression[] $arguments
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		bool $member_syntax, array $arguments /** list<ast_expression> */)
	{
		parent::__construct($start, $end);
		$this->name = $name;
		$this->member_syntax = $member_syntax;
		$this->arguments = $arguments;
		foreach ($arguments as $argument) {
			$argument->attach($this);
		}
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return $this->root()->function_key($this->name);
	}

	/** @param value_conversion[] $conversions */
	public function settle_function(symbol_entry $symbol, array $conversions /** list<value_conversion> */): void
	{
		$function = $symbol->declaration();
		if (!($function instanceof ast_function_declaration) || count($conversions) !== count($this->arguments)) {
			throw new \LogicException('Invalid settled call');
		}
		$this->settle_value($function->return_type_def(), null);
		$this->function_symbol = $symbol;
		$this->conversions = $conversions;
	}

	public function function_def(): ast_function_declaration
	{
		return $this->function_symbol?->declaration() ?? throw new \LogicException('Call target is not settled or was removed');
	}

	/** $index: argument ordinal. */
	public function conversion(int $index /** uint32 */): value_conversion
	{
		return $this->conversions[$index] ?? throw new \LogicException('Argument conversion is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->function_symbol = null;
		$this->conversions = [];
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
final class ast_function_body extends ast_statement_block implements dependency_watcher
{
	/** PROPERTIES */
	/** Native non-owning queue reference; null outside incremental ownership or after retirement. */
	private ?preparation_work $preparation_work = null;

	/** PREP FACTS */
	/** Preparation state: true only after successful whole-body preparation. */
	private bool $prepared = false;
	/** Prepared cache: entry-target context; null for ordinary function bodies. */
	private ?int $exit_digits /** uint8 */ = null;
	/** Preparation statistics: statements visited by the last successful body preparation; zero before completion. */
	public int $statement_visits /** uint32 */ = 0;
	/** Preparation statistics: expressions visited by the last successful body preparation; zero before completion. */
	public int $expression_visits /** uint32 */ = 0;
	/** Preparation statistics: name lookups performed by the last successful body preparation; zero before completion. */
	public int $name_lookups /** uint32 */ = 0;

	/** METHODS */
	/** Attach only after successful frontend publication; null retires replaced or removed syntax. */
	public function attach_preparation_work(?preparation_work $work): void
	{
		if ($this->preparation_work === $work) {
			return;
		}
		$this->preparation_work?->retire($this);
		$this->preparation_work = $work;
		$work?->register($this);
	}

	/** Invalidate completion only; observers never rebuild or traverse cached statements here. */
	public function invalidate_dependencies(): void
	{
		$this->prepared = false;
		$this->preparation_work?->enqueue($this);
		$this->root()->begin_preparation();
	}

	public function is_prepared(?entry_target $target = null): bool
	{
		return ($this->prepared) && ($this->exit_digits === $target?->digits);
	}

	public function reset_preparation(): void
	{
		$this->prepared = false;
		$this->preparation_work?->enqueue($this);
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
		$this->preparation_work?->complete($this);
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
	/** @var ast_parameter_declaration[] Native owning signature syntax in positional order. */
	public readonly array $parameters /** list<ast_parameter_declaration> */;
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
	 * @param ast_parameter_declaration[] $parameters
	 */
	public function __construct(string $name, ?string $return_type_name, int $start /** uint32 */,
		int $header_end /** uint32 */,
		bool $is_implicit_entry = false, ?string $namespace = null, array $parameters /** list<ast_parameter_declaration> */ = [])
	{
		$this->short_name = $name;
		$this->namespace = $namespace;
		$this->is_private = $is_implicit_entry;
		$this->is_implicit_entry = $is_implicit_entry;
		$this->return_type_name = $return_type_name;
		$this->header_end = $header_end;
		$this->parameters = $parameters;
		foreach ($parameters as $parameter) {
			$parameter->attach($this);
		}
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
		foreach ($this->parameters as $parameter) {
			$parameter->reset_preparation();
		}
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
 * Preparation checks the owning function return type; the entry retains its separate process-exit conversion.
 */
final class ast_return_statement extends ast_statement
{
	/** PROPERTIES */
	/** Native owning; optional return expression child. */
	public readonly ?ast_expression $value;

	/** PREP FACTS */
	/** Prepared fact: entry or function return conversion; null before preparation or for a void return. */
	protected exit_conversion|value_conversion|null $conversion = null;

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
	public function settle_conversion(exit_conversion|value_conversion $conversion): void
	{
		if ($this->conversion !== null) {
			throw new \LogicException('Return conversion already settled');
		}
		$this->conversion = $conversion;
	}

	public function conversion(): exit_conversion|value_conversion
	{
		return $this->conversion ?? throw new \LogicException('Entry conversion is not settled');
	}

	public function reset_preparation(): void
	{
		$this->conversion = null;
	}
}
