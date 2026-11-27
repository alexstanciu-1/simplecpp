	public function lower_cpp_append_value(string $receiver, string $value): string
	{
		return '(' . $receiver . ').@append@(' . $value . ')';
	}
