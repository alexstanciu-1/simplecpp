<?php

declare(strict_types=1);
namespace scpp\compiler;

// Generated from runtime metadata and validated target facts. Do not edit.
/**
 * Canonical int64 identity and literal preparation; int and int64 facts share this single object.
 * Example: global $count int = 10; attaches this definition to its declaration and checked literal.
 */
final class type_@name@ extends type_definition
{
	/** CONSTANTS */
	public const int MINIMUM /** int64 */ = (-@maximum@ - 1);
	public const int MAXIMUM /** int64 */ = @maximum@;

	/** STATIC PROPERTIES */

	private static ?self $instance = null;

	/** CONSTRUCTOR */
	private function __construct()
	{
		parent::__construct('@name@');
	}

	/** METHODS */
	/** Validate the literal and attach its facts; source traversal remains owned by preparation. */
	public function prepare_literal(ast_expression $syntax): void
	{
		if (!($syntax instanceof ast_integer_expression)) {
			parent::prepare_literal($syntax);
			return;
		}
		$syntax->settle_integer($this->literal_value($syntax));
	}

	/** Return: exact signed int64; reject overflow rather than relying on host casts or negation. */
	private function literal_value(ast_integer_expression $syntax): int /** int64 */
	{
		$tokens = $syntax->root()->tokens;
		$negative = $tokens->tokens[$syntax->start]->kind === token_kind::minus;
		$decimal = ltrim($tokens->text_at($syntax->end - 1), '0');
		$limit = $negative ? '@magnitude@' : '@maximum@';
		if ((strlen($decimal) > strlen($limit))
			|| ((strlen($decimal) === strlen($limit)) && (strcmp($decimal, $limit) > 0))) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Integer literal is outside @name@ range');
		}
		if ($negative && ($decimal === $limit)) {
			return self::MINIMUM;
		}
		$value = $decimal === '' ? 0 : (int) $decimal;
		return $negative ? -$value : $value;
	}

	public function accepts_name(string $name): bool
	{
		return @accept_names@;
	}

	public function cpp_type(): string
	{
		return @representation@;
	}

	/** Consume settled facts; safe minimum spelling is computed during generation from signed width. */
	public function lower_cpp_literal(ast_expression $syntax): string
	{
		if (!($syntax instanceof ast_integer_expression) || ($syntax->type_def() !== $this)) {
			throw new \LogicException('Expected a prepared @name@ literal');
		}
		$value = $syntax->integer();
		if ($value === self::MINIMUM) {
			return @cpp_minimum@;
		}
		return $value . @cpp_suffix@;
	}

	/** Emit target-native literals only at exact overload boundaries. Fold literal conversions after preparation. */
	public function lower_cpp_exact_argument(ast_expression $syntax, int $conversion /** uint16 */, string $operand): string
	{
		if ($syntax instanceof ast_integer_expression || $syntax instanceof ast_float_expression || $syntax instanceof ast_keyword_expression) {
			$value = $this->convert_constant($conversion, $syntax->constant());
			if (is_int($value)) {
				return $value === self::MINIMUM ? @native_minimum@ : $value . @native_suffix@;
			}
		}
		if ($conversion === value_conversion::identity && @native_suffix@ !== @cpp_suffix@
			&& !($syntax instanceof ast_call_expression) && !($syntax instanceof ast_index_expression)
			&& !(($syntax instanceof ast_name_expression) && !($syntax->declaration() instanceof ast_constant_declaration))) {
			// An inlined constant or arithmetic subtree can still carry the ordinary literal suffix.
			return 'static_cast<' . $this->cpp_type() . '>(' . $operand . ')';
		}
		return parent::lower_cpp_exact_argument($syntax, $conversion, $operand);
	}

@conversion_methods@
	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
