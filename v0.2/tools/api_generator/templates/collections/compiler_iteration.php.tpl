
	public function iteration_value_type(): type_definition
	{
		return @value@;
	}

	public function iteration_key_type(): ?type_definition
	{
		return @key@;
	}

	public function iteration_method(): ?ast_function_declaration
	{
		return null;
	}

	public function lower_cpp_iteration_state(string $receiver, bool $keyed): string
	{
		return 'const auto iteration_limit = (' . $receiver . ').@limit@(); scpp::int64 iteration_position = 0; ' . ($keyed ? @state@ : '');
	}

	public function lower_cpp_iteration_next(string $receiver, string $value, ?string $key): string
	{
		$prefix = '(' . $receiver . ').@next@(' . 'iteration_position, iteration_limit, ';
		if ($key === null) return $prefix . $value . ')';
		return $prefix . @arguments@ . ')'@publish@;
	}
