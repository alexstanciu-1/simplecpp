
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

	public function lower_cpp_iteration_storage(bool $keyed): string
	{
		return 'scpp::int64 iteration_limit{}; scpp::int64 iteration_position{}; ' . ($keyed ? @state@ : '');
	}

	public function lower_cpp_iteration_initialize(string $receiver, string $prefix = ''): string
	{
		return $prefix . 'iteration_limit = (' . $receiver . ').@limit@(); ' . $prefix . 'iteration_position = 0;';
	}

	public function lower_cpp_iteration_next(string $receiver, string $value, ?string $key, string $prefix = ''): string
	{
		$call = '(' . $receiver . ').@next@(' . $prefix . 'iteration_position, ' . $prefix . 'iteration_limit, ';
		if ($key === null) return $call . $value . ')';
		return $call . @arguments@ . ')'@publish@;
	}
