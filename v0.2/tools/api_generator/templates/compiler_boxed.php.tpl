
/** Published @name@ scalar box and its generated admission and lowering bindings. */
final class type_@name@ extends boxed_type_definition
{
	/** STATIC PROPERTIES */
	private static ?self $instance = null;

	/** METHODS */
	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return ['@header@'];
	}

	public function accepts_scalar(type_definition $source): bool
	{
		return match ($source->name) { @sources@ => true, default => false };
	}

	public function admits_extraction(type_definition $target, conversion_context $context): bool
	{
		return match ($context) {
@targets@			default => false,
		};
	}

	public function box_cpp(type_definition $source, string $value): string
	{
		return match ($source->name) {
@factories@			default => throw new \LogicException('Unprepared boxing operation'),
		};
	}

	public function admits_pair(binary_operator $operator, type_definition $peer, bool $box_left): bool
	{
		return match ($operator) {
@ops@			default => false,
		};
	}

@direct_method@	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self('@name@');
	}
}
