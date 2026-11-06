
/** Canonical published @class@ identity; source construction and value operations remain deferred. */
final class @class@ extends vector_type_definition
{
	private static ?self $instance = null;

	private function __construct()
	{
		parent::__construct(@name@, @element@::instance());
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

	public static function instance(): self
	{
		return self::$instance ??= new self();
	}
}
