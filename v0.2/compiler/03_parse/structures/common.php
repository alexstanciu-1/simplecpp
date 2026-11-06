<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Analysis selects the default type and checks range; parsing retains exact decimal spelling.
 *
 * Literal syntax:
 * ```text
 * return 0008;
 * ```
 * The node retains the token spelling 0008; preparation later checks and normalizes it to 8.
 */
final class ast_integer_expression extends ast_expression
{
	public function settle_integer(int $value /** int64 */): void
	{
		$this->settle_value(type_registry::integer(), $value);
	}

	public function integer(): int /** int64 */
	{
		return $this->constant() ?? throw new \LogicException('Integer value is not prepared');
	}
}

/**
 * Decimal floating syntax retains its exact source tokens; preparation owns the rounded binary64 payload.
 * Example: $amount float = -1.25e-3; spans the sign and literal, without an independent unary operator node.
 */
final class ast_float_expression extends ast_expression
{
	public function settle_floating(float $value /** float64 */): void
	{
		$this->settle_value(type_registry::floating(), $value);
	}

	public function floating(): float /** float64 */
	{
		$value = $this->constant();
		return is_float($value) ? $value : throw new \LogicException('Floating value is not prepared');
	}
}

/**
 * A core keyword literal, such as true, false or null; metadata supplies its semantic identity and value.
 * Example: $ready bool = false; stores false as syntax, then prepares a known boolean payload.
 */
final class ast_keyword_expression extends ast_expression
{
	public readonly string $word;

	/** $start and $end are token boundaries, with exclusive end. */
	public function __construct(string $word, int $start /** uint32 */, int $end /** uint32 */)
	{
		parent::__construct($start, $end);
		$this->word = $word;
	}

	public function settle_scalar(type_definition $type, bool|null $value): void
	{
		$this->settle_value($type, $value, true);
	}
}

/**
 * A value-name read; preparation selects local/global storage or an immutable constant through its normalized name.
 *
 * PHP-flavor reference:
 * ```text
 * echo $value;
 * ```
 * The expression stores value as its name; source tokens retain the leading dollar.
 */
final class ast_name_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly string $name;
	/** JS dotted value/member ambiguity; rooted paths bypass local values. */
	public readonly bool $member_syntax;
	/** PHP bare names select constants; dollar-prefixed syntax selects variable storage. */
	public readonly bool $constant_only;

	/** PREP FACTS */
	/**
	 * Prepared fact: local AST identity or stable global/constant symbol; null before preparation or after reset.
	 * Native non-owning; locals belong to body syntax, parameters to signatures, stable entries to the compilation scope.
	 */
	protected ast_local_binding|symbol_entry|null $binding = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive.
	 */
	public function __construct(string $name, int $start /** uint32 */, int $end /** uint32 */, bool $member_syntax = false, bool $constant_only = false)
	{
		$this->name = $name;
		$this->member_syntax = $member_syntax;
		$this->constant_only = $constant_only;
		parent::__construct($start, $end);
	}

	/** METHODS */
	/** Snapshot the latest write or initializer; unknown suppresses stale facts after borrowing or through aliases. */
	public function settle_local(ast_local_binding $local, ?ast_assignment_statement $write = null, bool $unknown = false): void
	{
		if (($write !== null) && ($write->local_def() !== $local)) {
			throw new \LogicException('Read and latest write must refer to the same local');
		}
		$current = $write ?? $local;
		$this->settle_value($local->type_def(), $unknown ? null : $current->constant(), !$unknown && $current->constant_known());
		$this->binding = $local;
	}

	/** Global storage is mutable in intent; its initializer is not a constant fact at a read occurrence. */
	public function settle_global(symbol_entry $symbol): void
	{
		$global = $symbol->declaration();
		if (!($global instanceof ast_global_declaration)) {
			throw new \LogicException('Expected a global variable symbol');
		}
		$this->settle_value($global->type_def(), null);
		$this->binding = $symbol;
	}

	/** Constant reads retain their live symbol and occurrence value fact; strings stay on the initializer AST. */
	public function settle_constant(symbol_entry $symbol): void
	{
		$constant = $symbol->declaration();
		if (!($constant instanceof ast_constant_declaration)) {
			throw new \LogicException('Expected a constant symbol');
		}
		$this->settle_value($constant->type_def(), $constant->constant(), $constant->constant_known());
		$this->binding = $symbol;
	}

	/** Resolve a published declaration through its live identity; direct local links stay within the owning body. */
	public function declaration(): ast_local_binding|ast_global_declaration|ast_constant_declaration
	{
		if ($this->binding instanceof ast_local_binding) {
			return $this->binding;
		}
		$declaration = $this->binding?->declaration();
		if (!($declaration instanceof ast_literal_declaration)) {
			throw new \LogicException('Name reference is not settled or was removed');
		}
		return $declaration;
	}

	public function local_def(): ast_local_binding
	{
		return $this->binding instanceof ast_local_binding
			? $this->binding : throw new \LogicException('Local reference is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->binding = null;
	}
}

/**
 * Owns a local name, optional written type and initializer; collection assigns its ID and preparation settles its type and known value.
 *
 * PHP-flavor declaration:
 * ```text
 * $value int = 3;
 * ```
 * short_name is value, type_reference names int, and the initializer is an integer expression.
 */
final class ast_local_declaration extends ast_statement implements ast_local_binding
{
	use ast_declaration;

	/** PROPERTIES */
	/** Native owning written type syntax; null when omitted, independently of later resolution. */
	public readonly ?ast_type_reference $type_reference;
	/** Body-local declaration ordinal, assigned by collection. */
	public readonly int $id /** uint32 */;
	/** Native owning; initializer syntax child. */
	public readonly ast_expression $initializer;

	/** PREP FACTS */
	/** Prepared fact: canonical local type; null until successful declaration preparation or after reset. */
	protected ?type_definition $type_def = null;
	/** Prepared fact: initializer payload; known null is distinguished by constant_known. */
	protected int|float|bool|null $constant /** int64|float64|bool|null */ = null;
	/** Prepared fact: whether the initializer payload is known; false before settlement or after reset. */
	protected bool $constant_known = false;
	/** Prepared fact: destination-bound conversion; null until initialization is approved. */
	protected ?value_conversion $initialization = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		string $name,
		?ast_type_reference $type_reference, ast_expression $initializer)
	{
		$this->short_name = $name;
		$this->namespace = null;
		$this->type_reference = $type_reference;
		$type_reference?->attach($this);
		$this->initializer = $initializer;
		$initializer->attach($this);

		parent::__construct($start, $end);
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return 'var:' . $this->declaration_key();
	}

	public function storage_id(): int /** uint32 */
	{
		return $this->id;
	}

	public function identify(int $id /** uint32 */): void
	{
		$this->id = $id;
	}

	public function reset_preparation(): void
	{
		$this->type_def = null;
		$this->constant = null;
		$this->constant_known = false;
		$this->initialization = null;
	}

	/** Attach the approved initialization facts after source preparation and conversion checks succeed. */
	public function settle_type(type_definition $type, int|float|bool|null $constant /** int64|float64|bool|null */,
		bool $known = false, value_conversion $conversion = value_conversion::identity): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Local type is already settled');
		}
		$this->type_def = $type;
		$this->constant = $constant;
		$this->constant_known = $known || ($constant !== null);
		$this->initialization = $conversion;
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Local type is not settled');
	}

	/** Return: when known. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$this->type_def();
		return $this->constant;
	}

	public function constant_known(): bool
	{
		$this->type_def();
		return $this->constant_known;
	}

	public function initialization(): value_conversion
	{
		return $this->initialization ?? throw new \LogicException('Initialization is not prepared');
	}

	/**
	 * Recover the name position from the existing flavor grammar, without a retained wrapper/index.
	 *
	 * Return: token position.
	 */
	public function name_position(): int /** uint32 */
	{
		return $this->source()->flavor === flavor::cpp && $this->type_reference !== null
			? $this->type_reference->end : $this->start + ($this->source()->flavor === flavor::php ? 0 : 1);
	}

	/**
	 * Locate the authored type nominator for diagnostics without storing another span.
	 *
	 * Return: token position; requires a written type.
	 */
	public function type_position(): int /** uint32 */
	{
		if ($this->type_reference === null) {
			throw new \LogicException('No written local type');
		}
		return $this->type_reference->start;
	}

}

/**
 * An authored write to an existing local; preparation attaches its binding and approved conversion.
 *
 * PHP-flavor write syntax:
 * ```text
 * $value = 3;
 * $value += 2;
 * ```
 * PHP first writes become declarations during parsing; this node represents a write to an existing local.
 */
final class ast_assignment_statement extends ast_storage_write
{
	/** PROPERTIES */
	/** Native owning; assignment expression child. */
	public readonly ast_expression $value;
	public readonly assignment_intent $intent;
	/** Null is plain assignment; otherwise retain the underlying operation without a synthetic binary tree. */
	public readonly ?binary_operator $operator;

	/** PREP FACTS */
	/** Prepared fact: approved RHS conversion for assignment or compound; null before settlement or after reset. */
	protected ?value_conversion $conversion = null;
	/** Prepared fact: non-owning calculation type; null until compound settlement or after reset. */
	protected ?type_definition $execution_type = null;
	/** Prepared fact: conversion of the saved destination into the calculation type. */
	protected value_conversion $left_conversion = value_conversion::identity;
	/** Prepared fact: conversion of the calculated result back into the destination type. */
	protected value_conversion $write_back = value_conversion::identity;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		string $name,
		ast_expression $value, assignment_intent $intent, ?binary_operator $operator = null)
	{
		$this->value = $value;
		$this->intent = $intent;
		$this->operator = $operator;
		$value->attach($this);

		parent::__construct($start, $end, $name);
	}

	/** METHODS */
	public function settle_assignment(ast_local_binding $local, value_conversion $conversion): void
	{
		$this->settle_binding($local);
		$this->conversion = $conversion;
	}

	/** Attach calculation and write-back decisions without constructing synthetic expression nodes. */
	public function settle_compound(ast_local_binding $local, type_definition $execution,
		value_conversion $left, value_conversion $right, value_conversion $write_back): void
	{
		if ($this->operator === null) {
			throw new \LogicException('Compound facts require an operator');
		}
		$this->settle_assignment($local, $right);
		$this->execution_type = $execution;
		$this->left_conversion = $left;
		$this->write_back = $write_back;
	}

	public function execution_type(): type_definition
	{
		return $this->execution_type ?? throw new \LogicException('Compound execution type is not settled');
	}

	public function left_conversion(): value_conversion
	{
		$this->execution_type();
		return $this->left_conversion;
	}

	public function write_back(): value_conversion
	{
		$this->execution_type();
		return $this->write_back;
	}

	public function conversion(): value_conversion
	{
		return $this->conversion ?? throw new \LogicException('Assignment conversion is not settled');
	}

	/** Plain writes derive the converted payload; compound results remain unknown without arithmetic folding. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$type = $this->local_def()->type_def();
		return $this->operator === null ? $type->convert_constant($this->conversion(), $this->value->constant()) : null;
	}

	public function constant_known(): bool
	{
		$this->local_def();
		return ($this->operator === null) && $this->value->constant_known();
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversion = null;
		$this->execution_type = null;
		$this->left_conversion = value_conversion::identity;
		$this->write_back = value_conversion::identity;
	}
}

/** Discarded-value update, e.g. ++i or i--; no operand copy or synthetic literal is retained. */
final class ast_update_statement extends ast_storage_write
{
	public readonly update_operator $operator;
	public readonly bool $postfix;

	/** $start/$end: token positions, including the statement delimiter where present. */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */, string $name,
		update_operator $operator, bool $postfix)
	{
		parent::__construct($start, $end, $name);
		$this->operator = $operator;
		$this->postfix = $postfix;
	}

	public function settle_update(ast_local_binding $local): void
	{
		$this->settle_binding($local);
	}
}
