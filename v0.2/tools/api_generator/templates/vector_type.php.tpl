
/** Canonical published @class@ identity; empty construction, checked element writes and value-copy indexing. */
final class @class@ extends vector_type_definition
{
	/** STATIC PROPERTIES */
	private static ?self $instance = null;

	/** CONSTRUCTOR */
	private function __construct()
	{
		parent::__construct(@name@, @element@::instance());
	}

	/** METHODS */
	public function cpp_type(): string
	{
		return @cpp@;
	}

	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return [@header@];
	}

	public function prepare_append(ast_expression $value): int /** uint16 */
	{
		return @element@::instance()->prepare_conversion($value, conversion_context::typed_destination);
	}

	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== @count@::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Vector index requires ' . @count@::instance()->name);
		}
	}

	public function lower_cpp_empty(): string
	{
		return @cpp@ . '{}';
	}

	public function lower_cpp_append(string $receiver, string $value): string
	{
		return '(' . $receiver . ').@append@(static_cast<' . @element@::instance()->cpp_type() . '>(' . $value . '))';
	}

	public function lower_cpp_index(string $receiver, string $index): string
	{
		return '(' . $receiver . ').@index@(static_cast<' . @count@::instance()->cpp_type() . '>(' . $index . '))';
	}

	public function lower_cpp_store(ast_index_assignment $write, string $receiver, string $index, string $value): string
	{
		$index = @count@::instance()->lower_cpp_exact_argument($write->index, value_conversion::identity, $index);
		$value = @element@::instance()->lower_cpp_exact_argument($write->value, $write->conversion(), $value);
		return $receiver . '.@set@(' . $index . ', ' . $value . ');';
	}

@cardinality@
	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
