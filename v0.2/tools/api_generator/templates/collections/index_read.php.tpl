	public function prepare_index(ast_index_expression $value): void
	{
		if ($value->index->type_def() !== @count@::instance()) {
			throw new diagnostic($value->index->source(), $value->index->byte_start(), '@receiver@ index requires ' . @count@::instance()->name);
		}
		$value->settle_element(@element@::instance());
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		return '(' . $receiver . ').@index@(static_cast<' . @count@::instance()->cpp_type() . '>(' . $index . '))';
	}
