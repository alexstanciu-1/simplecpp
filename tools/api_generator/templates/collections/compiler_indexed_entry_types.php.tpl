
	public function write_type(): type_definition
	{
		return @value@;
	}

	public function cpp_entry_key_type(): string
	{
		return @key@->cpp_type();
	}

	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== @key@) {
			throw new diagnostic($key->source(), $key->byte_start(), @key_diagnostic@ . @key@->name);
		}
	}

	public function lower_cpp_key(ast_expression $syntax, string $key): string
	{
		return @key@->lower_cpp_exact_argument($syntax, value_conversion::identity, $key);
	}
