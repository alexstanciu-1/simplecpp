
/** Canonical published hash<V,K> identity; empty construction, checked keyed writes and copied reads. */
final class @class@ extends hash_type_definition
{
	/** STATIC PROPERTIES */
	private static ?self $instance = null;

	/** CONSTRUCTOR */
	private function __construct()
	{
		parent::__construct(@family@ . '<' . @value@::instance()->name . ',' . @key@::instance()->name . '>',
			@value@::instance(), @key@::instance());
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

	/** Strict keys are identities, not bool/float/text coercion boundaries. */
	public function prepare_key(ast_expression $key): void
	{
		if ($key->type_def() !== @key@::instance()) {
			throw new diagnostic($key->source(), $key->byte_start(), 'Hash key requires ' . @key@::instance()->name);
		}
	}

	public function lower_cpp_empty(): string
	{
		return @cpp@ . '{}';
	}

	public function lower_cpp_index(string $receiver, string $index): string
	{
		return '(' . $receiver . ').@index@(static_cast<' . @key@::instance()->cpp_type() . '>(' . $index . '))';
	}

	public function lower_cpp_write(string $receiver, string $index, string $value): string
	{
		return '(' . $receiver . ').@set@(static_cast<' . @key@::instance()->cpp_type() . '>(' . $index . '), '
			. 'static_cast<' . @value@::instance()->cpp_type() . '>(' . $value . '))';
	}

@cardinality@
	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
