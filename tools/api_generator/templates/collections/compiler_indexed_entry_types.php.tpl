
	public function write_type(): type_definition
	{
		return @value@;
	}

	public function cpp_entry_key_type(): string
	{
		return @key@->cpp_type();
	}

	public function lower_cpp_key(ast_expression $syntax, string $key): string
	{
		return @key@->lower_cpp_exact_argument($syntax, value_conversion::identity, $key);
	}
