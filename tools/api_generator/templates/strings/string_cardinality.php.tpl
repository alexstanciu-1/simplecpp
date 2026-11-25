	public function cardinality_result(collection_cardinality $operation): ?type_definition
	{
		return $operation === collection_cardinality::empty ? type_bool::instance() : null;
	}

	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string
	{
		return match ($operation) {
			collection_cardinality::empty => '(' . $receiver . ').empty()',
		};
	}
