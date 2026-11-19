
/** Canonical published @class@ identity; empty construction, checked element writes and value-copy indexing. */
final class @class@ extends vector_type_definition@interfaces@
{
	/** @var array<string,runtime_function|null> Canonical signatures cached on this type, never on each call. */
	private array $runtime_methods = [];
@identity@
@initialization@
	/** METHODS */
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

	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== @count@::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Vector index requires ' . @count@::instance()->name);
		}
	}

	public function lower_cpp_empty(): string
	{
		return $this->cpp_type() . '{}';
	}

	public function lower_cpp_append(ast_append_statement $write, string $receiver, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$value = @element@->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return '(' . $receiver . ').@append@(' . $value . ')';
	}

	public function lower_cpp_index(ast_expression $key, string $receiver, string $index): string
	{
		$index = @count@::instance()->lower_cpp_exact_argument($key, value_conversion::identity, $index);
		return '(' . $receiver . ').@index@(' . $index . ')';
	}

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $index, string $value, ?Cpp_Adapters $adapters = null): string
	{
		$value = @element@->lower_cpp_exact_argument($write->value, $write->conversion(), $value, $adapters);
		return $this->lower_cpp_replace($write->index, $receiver, $index, $value);
	}

	public function lower_cpp_replace(ast_expression $key, string $receiver, string $index, string $value): string
	{
		$index = @count@::instance()->lower_cpp_exact_argument($key, value_conversion::identity, $index);
		return $receiver . '.@set@(' . $index . ', ' . $value . ');';
	}

@cardinality@
@factory@
}
