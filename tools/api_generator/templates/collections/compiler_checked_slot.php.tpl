
	public function lower_cpp_slot(string $receiver, string $key): string
	{
		return "$receiver.@method@($key)";
	}
