
/** Canonical published @class@ identity; shared transport uses metadata-selected state admission and runtime bindings. */
final class @class@ extends carrier_type_definition
{
	private static ?self $instance = null;

	private function __construct()
	{
		parent::__construct(@family@ . '<' . @payload@::instance()->name . '>', @payload@::instance());
	}

	public function accepts_empty(): bool
	{
		return @empty@;
	}

	public function implicit_extraction(): bool
	{
		return @implicit@;
	}

	public function lower_cpp_extract(string $value): string
	{
		return '(' . $value . ').@access@()';
	}

	public function sentinel_factory(string $state): ?string
	{
		return match ($state) {
@sentinel_cases@
			default => null,
		};
	}

	public function lower_cpp_presence(string $value): string
	{
		return @presence_expression@;
	}

	public function lower_cpp_condition(string $value): string
	{
		return '([&]() { const auto &carrier = ' . $value . '; return carrier.@presence@() ? '
			. $this->payload_type->lower_cpp_condition('carrier.@access@()') . ' : @true_presence@; }())';
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
