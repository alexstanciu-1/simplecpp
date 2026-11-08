
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

	public function lower_cpp_empty(): string
	{
		return @cpp@ . '{}';
	}

	public function lower_cpp_append(string $receiver, string $value): string
	{
		return '(' . $receiver . ').@append@(static_cast<' . @element@::instance()->cpp_type() . '>(' . $value . '))';
	}

@index_methods@
	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
