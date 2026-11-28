
	public function lower_cpp_write(string $receiver, string $key, string $value): string
	{
		return $receiver . '.@member@(' . $key . ', ' . $value . ')';
	}
