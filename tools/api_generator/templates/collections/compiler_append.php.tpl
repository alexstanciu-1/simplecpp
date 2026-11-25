	public function lower_cpp_append(ast_append_statement $write, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$value = $this->write_type()->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return '(' . $receiver . ').@append@(' . $value . ')';
	}
