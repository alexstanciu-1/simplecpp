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

	public function settle_boolean(type_definition $type, bool $value): void
	{
		$this->settle_value($type, $value, true);
	}

	/** Null and explicit carrier sentinels are known by their literal type alone. */
	public function settle_keyword(type_definition $type): void
	{
		$this->settle_value($type, null, true);
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
class ast_name_expression extends ast_expression
{
	/** PROPERTIES */
	public readonly string $name;
	/** JS dotted value/member ambiguity; rooted paths bypass local values. */
	public readonly bool $member_syntax;
	/** PHP bare names select constants; dollar-prefixed syntax selects variable storage. */
	public readonly bool $constant_only;

	/** PREP FACTS */
	/**
	 * Prepared fact: local AST identity, stable source symbol or runtime constant; null until prepared.
	 * Native non-owning; locals belong to body syntax, parameters to signatures, stable entries to the compilation scope.
	 */
	protected ast_local_binding|ast_object_access_expression|symbol_entry|runtime_integer_constant|ast_enum_case|null $binding = null;

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
	public function settle_local(ast_local_binding $local, ?ast_assignment_statement $write = null, bool $unknown = false, ?type_definition $view = null): void
	{
		if (($write !== null) && ($write->local_def() !== $local)) {
			throw new \LogicException('Read and latest write must refer to the same local');
		}
		$current = $write ?? $local;
		$this->settle_value($view ?? $local->type_def(), $unknown ? null : $current->constant(), !$unknown && $current->constant_known());
		$this->binding = $local;
	}

	/** Bind this read through its dominating test; a selected union arm supplies the occurrence type. */
	public function settle_guard(ast_object_access_expression $guard, bool $narrow = false): void
	{
		if ($narrow) $this->settle_value($guard->target_type(), null);
		$this->binding = $guard;
		$guard->used_as_guard = true;
	}

	public function guard(): ?ast_object_access_expression
	{
		return $this->binding instanceof ast_object_access_expression ? $this->binding : null;
	}

	/** Case reads retain only the owned case link; nominal identity is never a scalar constant fact. */
	public function settle_enum_case(ast_enum_case $case): void
	{
		$this->binding = $case;
		$this->settle_value($case->parent->definition, null);
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
		if ($this instanceof ast_qualified_data) $this->settle_value($constant->type_def(), null);
		else $this->settle_value($constant->type_def(), $constant->constant(), $constant->constant_known());
		$this->binding = $symbol;
	}

	/** Module constants provide the value after ordinary source lookup has been observed. */
	public function settle_runtime_constant(runtime_integer_constant $constant): void
	{
		$this->settle_value(type_int64::instance(), $constant->value);
		$this->binding = $constant;
	}

	/** Resolve a published declaration through its live identity; direct local links stay within the owning body. */
	public function declaration(): name_binding
	{
		if ($this->binding instanceof ast_object_access_expression) return $this->binding->operand->declaration();
		if ($this->binding instanceof ast_local_binding || $this->binding instanceof runtime_integer_constant || $this->binding instanceof ast_enum_case) {
			return $this->binding;
		}
		$declaration = $this->binding?->declaration();
		if (!($declaration instanceof ast_literal_declaration)) {
			throw new \LogicException('Name reference is not settled or was removed');
		}
		return $declaration;
	}

	public function refers_to(ast_local_binding $local): bool
	{
		return $this->binding === $local || ($this->binding instanceof ast_object_access_expression && $this->binding->operand->refers_to($local));
	}

	/** Return local storage for this occurrence, following a narrowing guard to its original variable. */
	public function local_binding(): ?ast_local_binding
	{
		if ($this->binding instanceof ast_object_access_expression) return $this->binding->operand->local_binding();
		return $this->binding instanceof ast_local_binding ? $this->binding : null;
	}

	public function local_def(): ast_local_binding
	{
		return $this->local_binding() ?? throw new \LogicException('Local reference is not settled');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->binding = null;
	}
}

/** A static data access whose qualifier must run, e.g. produce()::$count or (p)::LIMIT. */
final class ast_qualified_data extends ast_name_expression
{
	/** Native owning qualifier expression. */
	public readonly ast_expression $receiver;

	public function __construct(ast_expression $receiver, string $name, int $end /** uint32 */, bool $constant_only)
	{
		parent::__construct($name, $receiver->start, $end, constant_only: $constant_only);
		$this->receiver = $receiver;
		$receiver->attach($this);
	}
}

/** Data selected from the current called type, e.g. static::$count or Static::STEP. */
final class ast_called_type_name extends ast_name_expression
{
}

/** A lexical ancestor data name, e.g. parent::$count or super.STEP. */
final class ast_ancestor_name extends ast_name_expression
{
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
class ast_local_declaration extends ast_statement implements ast_local_binding
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
	protected ?int $initialization /** uint16 */ = null;

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
	public function duplicate_category(): declaration_duplicate_category
	{
		return declaration_duplicate_category::local_binding;
	}

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
	public function settle_type(type_definition $type, int|float|bool|null|constant_evaluation $constant /** int64|float64|bool|null|constant_evaluation */,
		bool $known = false, int $conversion /** uint16 */ = value_conversion::identity): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Local type is already settled');
		}
		$this->type_def = $type;
		$this->constant = $constant === constant_evaluation::unavailable ? null : $constant;
		$this->constant_known = $constant !== constant_evaluation::unavailable && ($known || ($constant !== null));
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

	public function initialization(): int /** uint16 */
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

/** Lexical alias declaration. Preparation publishes the original binding under this name. */
final class ast_local_alias extends ast_local_declaration
{
}

/** A single whole-slot write. Both operations assign a prepared type's native default. */
abstract class ast_reset_statement extends ast_storage_write
{
	abstract public function prepare_reset(ast_local_binding $local): void;
}

/** Resets the value held in initialized local storage. */
final class ast_clean_statement extends ast_reset_statement
{
	public const string OPERATION = 'clean';

	/** Validate the target storage before recording the reset binding. */
	public function prepare_reset(ast_local_binding $local): void
	{
		$type = $local->type_def();
		if (!(type_registry::is_numeric_scalar($type) || $type === type_bool::instance() || $type === type_string::instance()
			|| $type instanceof vector_type_definition || $type instanceof hash_type_definition || $type instanceof boxed_type_definition || $type instanceof owned_callable_type)) {
			throw new diagnostic($this->source(), $this->byte_start(), 'Cannot clean storage of type ' . $type->name);
		}
		$this->settle_binding($local);
	}
}

/** Reset the existing slot to its type's absent state. */
final class ast_unset_statement extends ast_reset_statement
{
	public const string OPERATION = 'unset';

	/** Validate the target storage before recording the reset binding. */
	public function prepare_reset(ast_local_binding $local): void
	{
		// Aliases always introduce a new name and resolve to their original slot.
		if (($local instanceof ast_closure_capture && $local->by_reference) || $this->name !== $local->declaration_key()
			|| ($local instanceof ast_parameter_declaration && $local->mode === passing_mode::reference)) {
			throw new diagnostic($this->source(), $this->byte_start(), 'Cannot unset a native alias or reference parameter');
		}
		$type = $local->type_def();
		if (!(($type instanceof carrier_type_definition && $type->accepts_empty())
			|| ($type instanceof shared_record_type_definition && !$type->required)
			|| $type instanceof weak_record_type_definition || $type instanceof owned_callable_type)) {
			throw new diagnostic($this->source(), $this->byte_start(), 'Cannot unset storage of type ' . $type->name);
		}
		$this->settle_binding($local);
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
class ast_assignment_statement extends ast_storage_write
{
	/** PROPERTIES */
	/** Native owning; assignment expression child. */
	public readonly ast_expression $value;
	public readonly assignment_intent $intent;
	/** Null is plain assignment; otherwise retain the underlying operation without a synthetic binary tree. */
	public readonly ?binary_operator $operator;

	/** PREP FACTS */
	/** Prepared fact: approved RHS conversion for assignment or compound; null before settlement or after reset. */
	protected ?int $conversion /** uint16 */ = null;
	/** Prepared fact: non-owning calculation type; null until compound settlement or after reset. */
	protected ?type_definition $execution_type = null;
	/** Prepared fact: conversion of the saved destination into the calculation type. */
	protected int $left_conversion /** uint16 */ = value_conversion::identity;
	/** Prepared fact: conversion of the calculated result back into the destination type. */
	protected int $write_back /** uint16 */ = value_conversion::identity;

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
	public function settle_assignment(ast_local_binding $local, int $conversion /** uint16 */): void
	{
		$this->settle_binding($local);
		$this->conversion = $conversion;
	}

	/** Conditional writes select a branch; only eager compounds have calculation facts. */
	public function calculates_value(): bool
	{
		return $this->operator !== null && $this->operator !== binary_operator::coalesce;
	}

	/** Settle the calculation before publishing the local binding. */
	public function prepare_local_compound(ast_local_binding $local): void
	{
		$local->type_def()->prepare_compound($this);
		$this->settle_binding($local);
	}

	/** Attach calculation and write-back decisions without constructing synthetic expression nodes. */
	public function settle_compound(type_definition $execution,
		int $left /** uint16 */, int $right /** uint16 */, int $write_back /** uint16 */): void
	{
		if ($this->operator === null) {
			throw new \LogicException('Compound facts require an operator');
		}
		$this->conversion = $right;
		$this->execution_type = $execution;
		$this->left_conversion = $left;
		$this->write_back = $write_back;
	}

	public function execution_type(): type_definition
	{
		return $this->execution_type ?? throw new \LogicException('Compound execution type is not settled');
	}

	public function left_conversion(): int /** uint16 */
	{
		$this->execution_type();
		return $this->left_conversion;
	}

	public function write_back(): int /** uint16 */
	{
		$this->execution_type();
		return $this->write_back;
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Assignment conversion is not settled');
	}

	/** Plain writes derive the converted payload; compound results remain unknown without arithmetic folding. */
	public function constant(): int|float|bool|null /** int64|float64|bool|null */
	{
		$type = $this->local_def()->type_def();
		$value = $this->operator === null ? $type->evaluate_constant($this->conversion(), $this->value) : constant_evaluation::unavailable;
		return $value === constant_evaluation::unavailable ? null : $value;
	}

	public function constant_known(): bool
	{
		$this->local_def();
		return $this->operator === null
			&& $this->local_def()->type_def()->evaluate_constant($this->conversion(), $this->value) !== constant_evaluation::unavailable;
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

/** Property compound, e.g. $p->x += 1; the target retains the selected field or static property. */
final class ast_property_compound extends ast_assignment_statement
{
	public readonly ast_name_expression|ast_field_expression $target;

	public function __construct(ast_name_expression|ast_field_expression $target, ast_expression $value, binary_operator $operator, int $end /** uint32 */)
	{
		parent::__construct($target->start, $end, $target instanceof ast_field_expression ? $target->field : $target->name, $value, assignment_intent::existing_binding, $operator);
		$this->target = $target;
		$target->attach($this);
	}

}

/** Discarded-value update, e.g. ++i or i--; no operand copy or synthetic literal is retained. */
class ast_update_statement extends ast_storage_write
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

	public function require_update_type(type_definition $type): void
	{
		if (!$type->supports_update($this->operator)) {
			throw new diagnostic($this->source(), $this->byte_start(), 'Unsupported update type: ' . $type->name);
		}
	}

	public function settle_update(ast_local_binding $local): void
	{
		$this->settle_binding($local);
	}
}

/** A discarded numeric update with a writable property target, e.g. ++$p->x. */
final class ast_property_update extends ast_update_statement
{
	public readonly ast_field_expression|ast_name_expression $target;

	public function __construct(int $start /** uint32 */, int $end /** uint32 */, ast_field_expression|ast_name_expression $target,
		update_operator $operator, bool $postfix)
	{
		parent::__construct($start, $end, $target instanceof ast_field_expression ? $target->field : $target->name, $operator, $postfix);
		$this->target = $target;
		$target->attach($this);
	}
}
