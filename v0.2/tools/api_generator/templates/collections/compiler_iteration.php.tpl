
	public function iteration_value_type(): type_definition
	{
		return @value@;
	}

	public function iteration_key_type(): ?type_definition
	{
		return @key@;
	}

	public function lower_cpp_iteration_state(): string
	{
		return @state@;
	}

	public function lower_cpp_iteration_limit(string $receiver): string
	{
		return '(' . $receiver . ').@limit@()';
	}

	public function lower_cpp_iteration_next(string $receiver, string $position, string $limit, string $value, ?string $key): string
	{
		$prefix = '(' . $receiver . ').@next@(' . $position . ', ' . $limit . ', ';
		if ($key === null) return $prefix . $value . ')';
		return $prefix . @arguments@ . ')'@publish@;
	}
