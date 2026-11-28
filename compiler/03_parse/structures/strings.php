<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Quoted source spelling is preserved; semantic preparation owns byte decoding.
 *
 * String literal syntax:
 * ```text
 * echo "line\n";
 * ```
 * The expression retains quoted source spelling; preparation decodes the newline.
 */
class ast_string_expression extends ast_expression
{
	/** Prepared fact: decoded bytes; null means unprepared, empty string is valid. */
	protected ?string $decoded_bytes = null;

	public function settle_bytes(string $bytes): void
	{
		if ($this->decoded_bytes !== null) {
			throw new \LogicException('String is already decoded');
		}
		$this->settle_value(type_registry::string(), null);
		$this->decoded_bytes = $bytes;
	}

	public function bytes(): string
	{
		return $this->decoded_bytes ?? throw new \LogicException('String is not decoded');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->decoded_bytes = null;
	}
}

/** Unquoted brace property name whose spelling supplies literal string bytes. */
final class ast_property_name_string extends ast_string_expression {
}

/** A subrange of a single quoted token, measured in source bytes. */
trait ast_string_part_range
{
	public readonly int $offset /** uint32 */;
	public readonly int $length /** uint32 */;

	public function byte_start(): int /** uint32 */
	{
		return parent::byte_start() + $this->offset;
	}

	public function byte_end(): int /** uint32 */
	{
		return $this->byte_start() + $this->length;
	}

	public function source_text(): string
	{
		return substr($this->source()->content, $this->byte_start(), $this->length);
	}
}

/** String expression spanning part of an enclosing token. */
abstract class ast_string_token_slice extends ast_string_expression
{
	use ast_string_part_range;

	public function __construct(int $token /** uint32 */, int $offset /** uint32 */, int $length /** uint32 */)
	{
		parent::__construct($token, $token + 1);
		$this->offset = $offset;
		$this->length = $length;
	}
}

/** Literal bytes between insertion markers. */
final class ast_interpolation_text extends ast_string_token_slice {
}

/** Quoted key decoded by ordinary single-quoted literal rules. */
final class ast_interpolation_key extends ast_string_token_slice {
}

/** Prepared formatting shared by named, indexed and field insertions. */
trait ast_interpolation_conversion
{
	/** Prepared fact: selected target for an implicit PHP text consumer. */
	public runtime_function|ast_function_declaration|null $text_formatter = null;
	/** Prepared fact: scalar-to-string operation, cleared for incremental re-preparation. */
	private ?int $conversion /** uint16|null */ = null;

	/** Select scalar formatting, including runtime kind selection for a PHP mixed operand. */
	public function prepare_text(type_definition $string): void
	{
		if ($this->text_formatter !== null) {
			$this->conversion = value_conversion::stringable_text;
			return;
		}
		if ($this->type_def() instanceof carrier_type_definition) {
			throw new diagnostic($this->source(), $this->byte_start(), 'Interpolation requires a scalar local or parameter');
		}
		if ($this->source()->flavor === flavor::php && $this->type_def() instanceof type_mixed) {
			$this->conversion = $string->prepare_conversion($this, conversion_context::explicit_cast);
			return;
		}
		$this->conversion = $string->prepare_scalar_conversion($this, conversion_context::explicit_cast);
	}

	public function conversion(): int /** uint16 */
	{
		return $this->conversion ?? throw new \LogicException('Interpolation is not prepared');
	}

	public function reset_preparation(): void
	{
		parent::reset_preparation();
		$this->conversion = null;
		$this->text_formatter = null;
	}
}

/** Local or parameter insertion; its name span points inside the quoted token. */
final class ast_interpolation_name extends ast_name_expression
{
	use ast_string_part_range;
	use ast_interpolation_conversion;

	public function __construct(string $name, int $token /** uint32 */, int $offset /** uint32 */)
	{
		parent::__construct($name, $token, $token + 1);
		$this->offset = $offset;
		$this->length = strlen($name);
	}
}

/** A braced PHP insertion whose value comes from an ordinary indexed read. */
final class ast_interpolation_index extends ast_index_expression
{
	use ast_interpolation_conversion;

	public function byte_start(): int /** uint32 */
	{
		return $this->receiver->byte_start() - 1; // Include the dollar before the name.
	}

	public function byte_end(): int /** uint32 */
	{
		return $this->index->byte_end() + 1; // Include the closing bracket.
	}
}

/** PHP "{$row->name}" formats one declared field using ordinary member access. */
final class ast_interpolation_field extends ast_field_expression
{
	use ast_interpolation_conversion;

	public function byte_start(): int /** uint32 */
	{
		return $this->receiver->byte_start() - 1;
	}

	public function byte_end(): int /** uint32 */
	{
		return $this->receiver->byte_end() + 2 + strlen($this->field);
	}
}

/** Ordered text and local references that produce one owned string, e.g. "Hello $name". */
final class ast_interpolated_string extends ast_expression
{
	/** @var (ast_interpolation_text|ast_interpolation_name|ast_interpolation_index|ast_interpolation_field)[] */
	public readonly array $parts /** list<ast_interpolation_text|ast_interpolation_name|ast_interpolation_index|ast_interpolation_field> */;

	/** @param (ast_interpolation_text|ast_interpolation_name|ast_interpolation_index|ast_interpolation_field)[] $parts */
	public function __construct(int $token /** uint32 */, array $parts /** list<ast_interpolation_text|ast_interpolation_name|ast_interpolation_index|ast_interpolation_field> */)
	{
		parent::__construct($token, $token + 1);
		$this->parts = $parts;
		foreach ($parts as $part) {
			$part->attach($this);
		}
	}

	public function settle_string(type_definition $string): void
	{
		$this->settle_value($string, null);
	}
}
