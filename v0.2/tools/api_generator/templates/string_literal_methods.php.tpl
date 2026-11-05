	/** Decode once onto the literal AST; references retain declaration links, not copied bytes. */
	public function prepare_literal(ast_expression $syntax): void
	{
		if (!($syntax instanceof ast_string_expression)) {
			parent::prepare_literal($syntax);
			return;
		}
		$syntax->settle_bytes(String_Literals::decode($syntax));
	}

	public function lower_cpp_literal(ast_expression $syntax): string
	{
		if (!($syntax instanceof ast_string_expression) || ($syntax->type_def() !== $this)) {
			throw new \LogicException('Expected a prepared string literal');
		}
		return $this->cpp_type() . '{' . Cpp_String_Literals::bytes($syntax->bytes()) . ', '
			. strlen($syntax->bytes()) . '}';
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return [@storage_header@];
	}
