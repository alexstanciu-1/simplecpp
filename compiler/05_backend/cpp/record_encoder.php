<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Emit a concrete, read-only traversal in the unit owning the prepared data fields. */
trait Cpp_Record_Encoder
{
	private static function encode_method(record_type_definition $record): string
	{
		if ($record->declaration->short_name === 'encode') return 'scpp_escape_encode';
		for ($parent = $record; $parent !== null; $parent = $parent->base()) {
			if (isset($parent->declaration->fields['encode'])) return 'scpp_escape_encode';
		}
		return 'encode';
	}

	/** Visit an ordered struct slot synchronously. External encoders retain value-read semantics unless they guarantee a stable source. */
	public static function record_vector_walk(native_sequence_type $vector, string $source): string
	{
		$count = $vector->lower_cpp_cardinality(collection_cardinality::count, $source);
		$read = $vector->lower_cpp_element_read($source, 'i');
		$method = self::encode_method($vector->write_type());
		return "out.sequence($count);\n    for (::scpp::int64 i = 0, count = $count; i < count; ++i) {\n"
			. "        if (out.stable_record_elements()) ($source).scpp_escape_read_slot(i).$method(out);\n"
			. "        else ($read).$method(out);\n    }\n    out.end_array();";
	}

	/** @param mixed[] $declarations */
	private function encode_value(type_definition $type, string $source, array &$declarations): string
	{
		if ($type instanceof application_collection_type) $this->concrete_collections->request($type);
		return match ($type->structural_encoding()) {
			encoding_shape::leaf => "out.write($source);",
			encoding_shape::signed_integer => "out.write(static_cast<::std::int64_t>($source));",
			encoding_shape::unsigned_integer => "out.write_unsigned($source);",
			encoding_shape::null_value => "(void)($source); out.write_null();",
			encoding_shape::optional => $this->encode_optional($type, $source, $declarations),
			encoding_shape::sequence => $this->encode_sequence($type, $source, $declarations),
			encoding_shape::mapping => $this->encode_mapping($type, $source, $declarations),
			encoding_shape::record, encoding_shape::owner => $this->encode_record($type, $source, $declarations),
			default => throw new \LogicException('Missing prepared structural encoding'),
		};
	}

	/** @param mixed[] $declarations */
	private function encode_optional(carrier_type_definition $type, string $source, array &$declarations): string
	{
		$present = $type->lower_cpp_presence('slot');
		$write = $this->encode_value($type->payload_type, 'payload', $declarations);
		$borrow = $type->lower_cpp_borrow('slot');
		$copy = $type->lower_cpp_extract('slot');
		return "[&](const auto& slot) { if (!$present) { out.write_null(); return; } "
			. "auto visit = [&](const auto& payload) { $write }; "
			. "if (out.stable_record_elements()) visit($borrow); else visit($copy); }($source);";
	}

	/** @param mixed[] $declarations */
	private function encode_sequence(vector_type_definition $type, string $source, array &$declarations): string
	{
		$leaf = $type->write_type();
		if ($leaf->structural_encoding() === encoding_shape::record) {
			$declarations[$leaf->declaration->symbol_key()] = $leaf->declaration;
			return self::record_vector_walk($type, $source);
		}
		$count = $type->lower_cpp_cardinality(collection_cardinality::count, 'values');
		$read = $type->lower_cpp_element_read('values', 'i');
		$write = $this->encode_value($leaf, $read, $declarations);
		return "[&](const auto& values) { out.sequence($count); for (::scpp::int64 i = 0, count = $count; i < count; ++i) { $write } out.end_array(); }($source);";
	}

	/** A stable encoder borrows the selected slot. An external encoder may mutate the source from a callback.
	 * @param mixed[] $declarations */
	private function encode_mapping(hash_type_definition $type, string $source, array &$declarations): string
	{
		$count = $type->lower_cpp_cardinality(collection_cardinality::count, 'values');
		$init = $type->lower_cpp_iteration_storage(true) . $type->lower_cpp_iteration_initialize('values');
		$next = $type->lower_cpp_iteration_next_key('values', 'key');
		$key = $type->key_type;
		$emit_key = $key->scalar_kind() === value_kind::string
			? 'out.field({key.data(), static_cast<::std::size_t>(key.size())});' : 'out.integer_key(key);';
		$write = $this->encode_value($type->write_type(), 'entry', $declarations);
		$copy = $type->lower_cpp_iteration_current_value('values');
		return "[&](const auto& values) { out.mapping($count); $init " . $key->cpp_type() . " key{}; while ($next) { "
			. "auto visit = [&](const auto& entry) { $emit_key $write }; "
			. "if (out.stable_record_elements()) visit(values.scpp_escape_read_slot(iteration_position)); else visit($copy); } out.end_object(); }($source);";
	}

	/** @param mixed[] $declarations @param bool[] $headers */
	private function encode_definition(record_type_definition $record, array &$declarations, array &$headers): string
	{
		$name = $record->cpp_type();
		$method = self::encode_method($record);
		$label = Cpp_String_Literals::bytes($record->name);
		$count = count($record->declaration->data_constructor->parameters);
		$lines = ["out.record($label, $count);"];
		foreach ($record->declaration->data_constructor->parameters as $field) {
			$type = $field->type_def();
			$member = Cpp_Identifier::source($field->short_name);
			$read = $field->parent === $record->declaration ? "this->$member"
				: 'static_cast<const ' . $field->parent->definition->cpp_type() . '&>(*this).' . $member;
			$lines[] = 'out.field(' . Cpp_String_Literals::bytes($field->short_name) . ');';
			$lines[] = $this->encode_value($type, $read, $declarations);
		}
		$lines[] = 'out.end_object();';
		$walk = "void {$name}::{$method}(::scpp::Encoder& out) const {\n    " . implode("\n    ", $lines) . "\n}\n"
			. "void {$name}::scpp_escape_encode_fields(const void* value, ::scpp::Encoder& out) {\n"
			. "    static_cast<const $name*>(value)->{$method}(out);\n}\n";
		if (!$this->record_demand($record->declaration, dependency_use::record_json_encode)) return $walk;
		$headers[runtime_functions::resolve('php:func:json_encode')->record_header] = true;
		return $walk . "::scpp::result_string {$name}::scpp_escape_json_encode(const $name& value) {\n"
			. "    return ::scpp::json::encode_fields(&value, &scpp_escape_encode_fields);\n}\n";
	}

	/** A shared view is borrowed for the visit; the encoder owns ancestor detection and null output. @param mixed[] $declarations */
	private function encode_record(type_definition $leaf, string $value, array &$declarations): string
	{
		if ($leaf->structural_encoding() === encoding_shape::owner) {
			$nested = $leaf->payload;
			$declarations[$leaf->name] = $leaf;
			$declarations[$nested->declaration->symbol_key()] = $nested->declaration;
			return "out.owned(($value).view, &" . $nested->cpp_type() . '::scpp_escape_encode_fields);';
		}
		$declarations[$leaf->declaration->symbol_key()] = $leaf->declaration;
		return "($value)." . self::encode_method($leaf) . '(out);';
	}
}
