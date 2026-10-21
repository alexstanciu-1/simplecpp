<?php
namespace scpp\compiler;

/** Physical interfaces consume runtime recipes and settled literal facts, never source names. */
final class CPP_Physical_Interfaces
{
	/** Preserve the native handle's hash/equality policy across concrete storage composition. */
	public static function identity_key(canonical_type_use $type, string $name, string $native): cpp_type_interface
	{
		$result = new cpp_type_interface();
		if (!Type_Queries::is_nominal_handle($type)
			|| (Type_Queries::canonical($type)->definition()->origin() !== type_definition_origin::source)) { return $result; }
		$result->declaration_prefix = "#include <stdint.h>\n";
		$result->declaration = "using scpp_runtime_hash_key = void;\nuint64_t hash_key_hash() const;\n"
			. 'bool hash_key_equal(const ' . $name . "& other) const;\n";
		$result->implementation = 'uint64_t ' . $name . '::hash_key_hash() const { return scpp::hash_detail::key_ops<'
			. $native . '>::hash(unwrap(*this)); }' . "\n"
			. 'bool ' . $name . '::hash_key_equal(const ' . $name . '& other) const { return scpp::hash_detail::key_ops<'
			. $native . '>::equal(unwrap(*this), unwrap(other)); }' . "\n";
		return $result;
	}

	public static function literal(canonical_type_use $type, cpp_type $mapping, string $name, string $native): cpp_type_interface
	{
		$recipe = CPP_Types::stored_recipe($type);
		if ($recipe === null) { return new cpp_type_interface(); }
		if ($recipe->literal_interface === '') { return new cpp_type_interface(); }
		if ($recipe->literal_interface === 'scalar') { return self::scalar($type, $mapping->literal, $name, $native); }
		if ($recipe->literal_interface === 'bytes') { return self::bytes($recipe, $name, $native); }
		throw new \LogicException('Unknown physical literal interface');
	}

	private static function scalar(canonical_type_use $type, cpp_literal_kind $literal, string $name, string $native): cpp_type_interface
	{
		$result = new cpp_type_interface();
		$scalar = self::scalar_type($type, $literal);
		$result->declaration_prefix = "#include <stdint.h>\n";
		if ($literal === cpp_literal_kind::signed_integer) {
			$result->declaration .= 'using scpp_runtime_integer_rep = ' . $scalar . ";\n";
		}
		if ($literal === cpp_literal_kind::boolean) {
			$result->declaration .= "explicit operator bool() const noexcept { return native_value(); }\n";
		}
		$result->declaration .= 'explicit ' . $name . '(' . $scalar . ' value); ' . $scalar . " native_value() const noexcept;\n";
		$result->implementation = $name . '::' . $name . '(' . $scalar . ' value) { ::new(address()) ' . $native . '(value); }' . "\n"
			. $scalar . ' ' . $name . '::native_value() const noexcept { return unwrap(*this).native_value(); }' . "\n";
		return $result;
	}

	private static function bytes(cpp_type_recipe $recipe, string $name, string $native): cpp_type_interface
	{
		$result = new cpp_type_interface();
		$adapter = $recipe->byte_interface;
		$result->declaration_prefix = "#include <stddef.h>\n";
		$result->declaration = "using scpp_runtime_byte_sequence = void;\n"
			. $name . '(const char* bytes, size_t size); const char* byte_data() const noexcept; size_t byte_size() const noexcept;'
			. ' void append(const ' . $name . "& value);\n";
		$result->implementation = $name . '::' . $name . '(const char* bytes, size_t size) { ::new(address()) ' . $native
			. '(' . $adapter . '::construct(bytes, size)); }' . "\n"
			. 'const char* ' . $name . '::byte_data() const noexcept { return ' . $adapter . '::data(unwrap(*this)); }' . "\n"
			. 'size_t ' . $name . '::byte_size() const noexcept { return ' . $adapter . '::size(unwrap(*this)); }' . "\n"
			. 'void ' . $name . '::append(const ' . $name . '& value) { ' . $adapter . '::append(unwrap(*this), unwrap(value)); }' . "\n";
		return $result;
	}

	/** Semantic literal facts choose the scalar ABI; metadata opts into the interface. */
	private static function scalar_type(canonical_type_use $type, cpp_literal_kind $literal): string
	{
		if ($literal === cpp_literal_kind::boolean) { return 'bool'; }
		if ($literal === cpp_literal_kind::floating) { return 'double'; }
		if ($literal !== cpp_literal_kind::signed_integer) { throw new \RuntimeException('Scalar physical interface requires a scalar literal type'); }
		$definition = object_cast(Type_Queries::canonical($type)->definition(), integer_type_definition::class);
		return ($definition->signed() ? 'int' : 'uint') . $definition->bit_width() . '_t';
	}
}
