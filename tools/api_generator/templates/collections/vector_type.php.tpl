
/** Canonical published @class@ identity; empty construction, checked element writes and value-copy indexing. */
final class @class@ extends vector_type_definition@interfaces@, subset_source, map_source, @entry_builder@
{
	use @entry_insert@;
	use collection_keyed_store;
	use @entry_presence@;
@writable_iteration@@checked_slot_binding@
@fill_binding@

	/** @var array<string,runtime_function|null> Canonical signatures cached on this type, never on each call. */
	private array $runtime_methods = [];
@identity@
@initialization@
	/** METHODS */
@copy_effect@
@declarations@
@subset_result@
@map_result@
@iteration@
@methods@
	public function cpp_type(): string
	{
		return @cpp@;
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return [@header@];
	}

	public function lower_cpp_empty(): string
	{
		return $this->cpp_type() . '{}';
	}

	public function lower_cpp_sequence_append(ast_expression $element, int $conversion /** uint16 */, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$value = @element@->lower_cpp_exact_argument($element, $conversion, $value, $adapters);
		return $this->lower_cpp_append_value($receiver, $value);
	}

	/** Append a native value already proven to have the exact element type. */
	public function lower_cpp_append_value(string $receiver, string $value): string
	{
		return '(' . $receiver . ').@append@(' . $value . ')';
	}

@indexed_read@

	/** Read through an already typed native index, including an iteration position. */
	public function lower_cpp_element_read(string $receiver, string $index): string
	{
		return '(' . $receiver . ').@index@(' . $index . ')';
	}

@indexed_store@

@cardinality@
@factory@
}
