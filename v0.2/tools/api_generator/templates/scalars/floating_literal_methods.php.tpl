	/** Round once in the validated PHP binary64 host, retaining source spelling on the AST tokens. */
	public function prepare_literal(ast_expression $syntax): void
	{
		if (!($syntax instanceof ast_float_expression)) {
			parent::prepare_literal($syntax);
			return;
		}
		$tokens = $syntax->root()->tokens;
		$negative = $tokens->tokens[$syntax->start]->kind === token_kind::minus;
		$decimal = ($negative ? '-' : '') . $tokens->text_at($syntax->end - 1);
		$value = (float) $decimal;
		if (!is_finite($value)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Floating literal is outside float64 finite range');
		}
		$syntax->settle_floating($value);
	}

	public function lower_cpp_literal(ast_expression $syntax): string
	{
		if (!($syntax instanceof ast_float_expression) || ($syntax->type_def() !== $this)) {
			throw new \LogicException('Expected a prepared float64 literal');
		}
		return $this->cpp_constant($syntax->floating());
	}

	/** Emit exact binary64 bits as a C++ hexadecimal literal; preserve subnormals and signed zero. */
	public function cpp_constant(float $value /** float64 */): string
	{
		if (!is_finite($value)) {
			throw new \LogicException('Nonfinite floating constant has no admitted source literal');
		}
		$hex = bin2hex(pack('E', $value));
		$head = hexdec(substr($hex, 0, 3));
		$exponent = $head & 0x7ff;
		$sign = ($head & 0x800) === 0 ? '' : '-';
		$fraction = substr($hex, 3);
		$leading = $exponent === 0 ? '0' : '1';
		$power = $exponent === 0 ? @minimum_exponent@ : $exponent - @exponent_bias@;
		return $sign . '0x' . $leading . '.' . $fraction . 'p' . $power;
	}
