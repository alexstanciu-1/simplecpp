
/** Canonical published hash<V,K> identity; empty construction, checked keyed writes and copied reads. */
final class @class@ extends hash_type_definition@interfaces@, coalescing_index_read_type, subset_source, map_source, @entry_builder@
{
	use @entry_insert@;
	use collection_keyed_store;
	use @entry_presence@;
@writable_iteration@@checked_slot_binding@
@indexed_probe@

	/** @var array<string,runtime_function|null> Canonical signatures cached on this type, never on each call. */
	private array $runtime_methods = [];
	/** Immutable canonical binding, shared by occurrences; its type references are non-owning. */
	private readonly runtime_function $presence_binding;
@lookup_property@
@initialization@
@identity@
	/** METHODS */
@copy_effect@
@declarations@
@subset_result@
@map_result@
@iteration@
@methods@
	public function presence_function(): runtime_function
	{
		return $this->presence_binding;
	}

	public function cpp_type(): string
	{
		return @cpp@;
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return [@header@];
	}

	/** Strict keys are identities, not bool/float/text coercion boundaries. */
	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== @key@) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Hash key requires ' . @key@->name);
		}
	}

	public function lower_cpp_empty(): string
	{
		return $this->cpp_type() . '{}';
	}

	public function lower_cpp_coalescing_lookup(ast_expression $key, string $receiver, string $index): string
	{
		return $receiver . '.@coalescing_lookup@(' . $this->lower_cpp_key($key, $index) . ')';
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		return $receiver . '.@index@(' . $this->lower_cpp_key($key, $index) . ')';
	}

	public function lower_cpp_write(string $receiver, string $key, string $value): string
	{
		return $receiver . '.@set@(' . $key . ', ' . $value . ')';
	}

@append_method@
@lookup@
@cardinality@
@factory@
}
