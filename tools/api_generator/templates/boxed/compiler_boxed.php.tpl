
/** Published @name@ scalar box and its generated admission and lowering bindings. */
final class type_@name@ extends boxed_type_definition implements iterable_type, counted_collection_type, appendable_collection_type@literal_interfaces@@transfer@
{
@literal_methods@
@iteration@
@append_method@
	public function mutates_referenced_value(): bool { return @reference_mutation@; }

	public function supports_instance_field(): bool { return @field_admission@; }

	public function supports_static_property(): bool { return false; }

	public function cardinality_result(collection_cardinality $operation): type_definition
	{
		return $operation === collection_cardinality::count ? type_int64::instance() : type_bool::instance();
	}

	public function lower_cpp_cardinality(collection_cardinality $operation, string $receiver): string
	{
		$method = $operation === collection_cardinality::count ? '@count_method@' : '@empty_method@';
		return '(' . $receiver . ').' . $method . '()';
	}

	/** STATIC PROPERTIES */
	private static ?self $instance = null;

	/** METHODS */
	/** @return string[] */
	public function cpp_headers(): array /** list<string> */
	{
		return ['@header@'];
	}

@export_factory@	public function accepts_scalar(type_definition $source): bool
	{
		return match ($source->name) { @sources@ => true, default => false };
	}

	public function admits_extraction(type_definition $target, conversion_context $context): bool
	{
		return match ($context) {
@targets@			default => false,
		};
	}

	public function box_cpp(ast_expression $syntax, string $value): string
	{
		$source = $syntax->value_type();
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

	public function binary_result(binary_operator $operator, type_definition $right): ?type_definition
	{
		return $this->admits_pair($operator, $right, true) ? $this->boxed_result($operator) : null;
	}

	protected function boxed_result(binary_operator $operator): ?type_definition
	{
		return match ($operator) {
@results@			default => null,
		};
	}

	public function supports_compound(binary_operator $operator): bool
	{
		return match ($operator) { @compounds@ => true, default => false };
	}

	public function supports_update(update_operator $operator): bool
	{
		return match ($operator) { @updates@ => true, default => false };
	}

	protected function flavor_mask(ast_node $site): int /** uint8 */
	{
		return match ($site->source()->flavor) { @flavor_mask@ };
	}

	/** Each admitted pair calls a checked helper; its borrowed operands must survive sibling evaluation. */
	public function cpp_direct_binary(ast_binary_expression $expr, bool $operands_safe, bool $same_spelling): bool
	{
		return self::stable_cpp_operand($expr->left) && self::stable_cpp_operand($expr->right);
	}

	/** STATIC METHODS */
	public static function instance(): self
	{
		return self::$instance ??= new self('@name@');
	}
}
