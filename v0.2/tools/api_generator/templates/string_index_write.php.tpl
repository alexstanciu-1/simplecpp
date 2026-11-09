	use ordered_keyed_store;

	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== @key@::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'String index requires ' . @key@::instance()->name);
		}
	}

	public function write_type(): type_definition
	{
		return @value@::instance();
	}

	public function lower_cpp_key(string $key): string
	{
		return 'static_cast<' . @key@::instance()->cpp_type() . '>(' . $key . ')';
	}

	public function lower_cpp_write(string $receiver, string $key, string $value): string
	{
		return '(' . $receiver . ').@method@(' . $key . ', ' . $value . ')';
	}
