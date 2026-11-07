
/** Canonical published @class@ identity; empty construction, checked append and value-copy indexing. */
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

	public function prepare_index(ast_index_expression $value): void
	{
		if ($value->index->type_def() !== @count@::instance()) {
			throw new diagnostic($value->index->source(), $value->index->byte_start(), 'Vector index requires ' . @count@::instance()->name);
		}
		$value->settle_element(@element@::instance());
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

@cardinality@
	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
