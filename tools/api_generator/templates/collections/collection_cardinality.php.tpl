	public function cardinality_result(collection_cardinality $operation): type_definition
	{
		return $operation === collection_cardinality::count ? @count_type@::instance() : @empty_type@::instance();
	}

	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string
	{
		$member = $operation === collection_cardinality::count ? '@size@' : '@empty@';
		return '(' . $receiver . ').' . $member . '()';
	}
