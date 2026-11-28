
	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		return @receiver@ . '.@member@(' . $this->lower_cpp_key($key, $index) . ')';
	}
