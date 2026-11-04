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
 * A variable-name read, storing its normalized name directly; preparation resolves the declaration in the enclosing body.
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

	/** PREP FACTS */
	/**
	 * Prepared fact: local AST identity or stable global symbol; null before preparation or after reset.
	 * Native non-owning; locals belong to body syntax, stable entries to the compilation scope.
	 */
	protected ast_local_declaration|symbol_entry|null $binding = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive.
	 */
	public function __construct(string $name, int $start /** uint32 */, int $end /** uint32 */, bool $member_syntax = false)
	{
		$this->name = $name;
		$this->member_syntax = $member_syntax;
		parent::__construct($start, $end);
	}

	/** METHODS */
	public function settle_local(ast_local_declaration $local): void
	{
		$this->settle_value($local->type_def(), $local->constant());
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

	/** Resolve a global through its live identity; direct local links stay within the owning body. */
	public function declaration(): ast_local_declaration|ast_global_declaration
	{
		if ($this->binding instanceof ast_local_declaration) {
			return $this->binding;
		}
		$declaration = $this->binding?->declaration();
		if (!($declaration instanceof ast_global_declaration)) {
			throw new \LogicException('Variable reference is not settled or was removed');
		}
		return $declaration;
	}

	public function local_def(): ast_local_declaration
	{
		return $this->binding instanceof ast_local_declaration
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
 * short_name is value, type_name is int, and the initializer is an integer expression.
 */
final class ast_local_declaration extends ast_statement implements ast_declaration_interface
{
	use ast_declaration;

	/** PROPERTIES */
	/** Written type name; null when omitted, independently of later resolution. */
	public readonly ?string $type_name;
	/** Body-local declaration ordinal, assigned by collection. */
	public readonly int $id /** uint32 */;
	/** Native owning; initializer syntax child. */
	public readonly ast_expression $initializer;

	/** PREP FACTS */
	/** Prepared fact: canonical local type; null until successful declaration preparation or after reset. */
	protected ?type_definition $type_def = null;
	/**
	 * Prepared fact: initializer constant; null means unknown after type settlement, or unprepared
	 * before it.
	 * When known.
	 */
	protected ?int $constant /** int64 */ = null;

	/** CONSTRUCTOR */
	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		string $name,
		?string $type_name, ast_expression $initializer)
	{
		$this->short_name = $name;
		$this->namespace = null;
		$this->type_name = $type_name;
		$this->initializer = $initializer;
		$initializer->attach($this);

		parent::__construct($start, $end);
	}

	/** METHODS */
	public function symbol_key(): string
	{
		return 'var:' . $this->declaration_key();
	}

	public function identify(int $id /** uint32 */): void
	{
		$this->id = $id;
	}

	public function reset_preparation(): void
	{
		$this->type_def = null;
		$this->constant = null;
	}

	public function settle_type(type_definition $type, ?int $constant /** int64 */): void
	{
		if ($this->type_def !== null) {
			throw new \LogicException('Local type is already settled');
		}
		$this->type_def = $type;
		$this->constant = $constant;
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Local type is not settled');
	}

	/** Return: when known. */
	public function constant(): ?int /** int64 */
	{
		$this->type_def();
		return $this->constant;
	}

	/**
	 * Recover the name position from the existing flavor grammar, without a retained wrapper/index.
	 *
	 * Return: token position.
	 */
	public function name_position(): int /** uint32 */
	{
		return $this->start + ($this->source()->flavor === flavor::php ? 0 : 1);
	}

	/**
	 * Locate the authored type nominator for diagnostics without storing another span.
	 *
	 * Return: token position; requires a written type.
	 */
	public function type_position(): int /** uint32 */
	{
		if ($this->type_name === null) {
			throw new \LogicException('No written local type');
		}
		return $this->start + match ($this->source()->flavor) {
			flavor::cpp => 0, flavor::php => 1, flavor::js => 3,
		};
	}

}

/**
 * An authored write to a named binding, retaining flavor intent; assignment preparation is outside the current executable slice.
 *
 * PHP-flavor write syntax:
 * ```text
 * $value = 3;
 * ```
 * Parsing records declare-or-assign intent; this does not enable assignment or inference in preparation.
 */
final class ast_assignment_statement extends ast_statement
{
	public readonly string $name;
	/** Native owning; assignment expression child. */
	public readonly ast_expression $value;
	public readonly assignment_intent $intent;

	/**
	 * $start: token position.
	 * $end: exclusive token position.
	 */
	public function __construct(int $start /** uint32 */, int $end /** uint32 */,
		string $name,
		ast_expression $value, assignment_intent $intent)
	{
		$this->name = $name;
		$this->value = $value;
		$this->intent = $intent;
		$value->attach($this);

		parent::__construct($start, $end);
	}
}
