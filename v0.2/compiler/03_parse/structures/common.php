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
final class ast_integer_expression extends ast_expression {}

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
	public readonly string $name;

	public function __construct(string $name, int $start /** uint32; token position */, int $end /** uint32; exclusive */)
	{
		$this->name = $name;
		parent::__construct($start, $end);
	}
}

/**
 * Owns a local name, optional written type and initializer; collection assigns its ID and preparation settles its type and known value.
 *
 * PHP-flavor declaration:
 * ```text
 * $value int = 3;
 * ```
 * name is value, type_name is int, and the initializer is an integer expression.
 */
final class ast_local_declaration extends ast_statement implements ast_declaration_interface
{
	use ast_declaration;
	/** Written type name; null when omitted, independently of later resolution. */
	public readonly ?string $type_name;
	/** Prepared fact: canonical local type; null until successful declaration preparation or after reset. */
	protected ?type_definition $type_def = null;
	public readonly int $id /** uint32; body-local declaration ordinal, assigned by collection */;
	protected ?int $constant /** int64 when known */ = null;
	public readonly ast_expression $initializer;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */,
		string $name,
		?string $type_name, ast_expression $initializer)
	{
		$this->name = $name;
		$this->type_name = $type_name;
		$this->initializer = $initializer;
		$initializer->attach($this);

		parent::__construct($start, $end);
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
		if ($this->type_def !== null) { throw new \LogicException('Local type is already settled'); }
		$this->type_def = $type;
		$this->constant = $constant;
	}

	public function type_def(): type_definition
	{
		return $this->type_def ?? throw new \LogicException('Local type is not settled');
	}

	public function constant(): ?int /** int64 when known */
	{
		$this->type_def();
		return $this->constant;
	}

	/** Recover the name position from the existing flavor grammar, without a retained wrapper/index. */
	public function name_position(): int /** uint32; token position */
	{
		return $this->start + ($this->source()->flavor === flavor::php ? 0 : 1);
	}

	public function type_position(): int /** uint32; token position; requires a written type */
	{
		if ($this->type_name === null) { throw new \LogicException('No written local type'); }
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
	public readonly ast_expression $value;
	public readonly assignment_intent $intent;

	public function __construct(int $start /** uint32; token position */, int $end /** uint32; exclusive token position */,
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
