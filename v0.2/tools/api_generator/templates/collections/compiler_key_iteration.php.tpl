
	public function lower_cpp_iteration_next_key(string $receiver, string $output, string $prefix = ''): string
	{
		return '(' . $receiver . ').@next_key@(' . $prefix . 'iteration_position, ' . $prefix . 'iteration_limit, ' . $output . ')';
	}

	public function lower_cpp_iteration_current_value(string $receiver): string
	{
		return '(' . $receiver . ').@current_value@(iteration_position)';
	}
