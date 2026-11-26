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

	/** @param mixed[] $declarations @param bool[] $headers */
	private function encode_definition(record_type_definition $record, array &$declarations, array &$headers): string
	{
		$contract = runtime_functions::resolve('php:func:json_encode');
		$headers[$contract->record_header] = true;
		$name = $record->cpp_type();
		$method = self::encode_method($record);
		$lines = ['out.begin_object();'];
		foreach ($record->declaration->data_constructor->parameters as $field) {
			$type = $field->type_def();
			$member = Cpp_Identifier::source($field->short_name);
			$read = $field->parent === $record->declaration ? "this->$member"
				: 'static_cast<const ' . $field->parent->definition->cpp_type() . '&>(*this).' . $member;
			$lines[] = 'out.field(' . Cpp_String_Literals::bytes($field->short_name) . ');';
			if ($type instanceof native_sequence_type) {
				$count = $type->lower_cpp_cardinality(collection_cardinality::count, $read);
				$element = $type->lower_cpp_element_read($read, 'i');
				$write = $this->encode_leaf($type->write_type(), $element, $declarations);
				$lines[] = 'out.begin_array();';
				$lines[] = "for (::scpp::int64 i = 0, count = $count; i < count; ++i) { $write }";
				$lines[] = 'out.end_array();';
			} else $lines[] = $this->encode_leaf($type, $read, $declarations);
		}
		$lines[] = 'out.end_object();';
		return "void {$name}::{$method}(::scpp::Encoder& out) const {\n    " . implode("\n    ", $lines) . "\n}\n"
			. "void {$name}::scpp_escape_encode_fields(const void* value, ::scpp::Encoder& out) {\n"
			. "    static_cast<const $name*>(value)->{$method}(out);\n}\n"
			. "::scpp::result_string {$name}::scpp_escape_json_encode(const $name& value) {\n"
			. "    return ::scpp::json::encode_fields(&value, &scpp_escape_encode_fields);\n}\n";
	}

	/** A shared view is borrowed for the visit; the encoder owns ancestor detection and null output. @param mixed[] $declarations */
	private function encode_leaf(type_definition $leaf, string $value, array &$declarations): string
	{
		if ($leaf instanceof shared_record_type_definition) {
			$nested = $leaf->payload;
			$declarations[$leaf->name] = $leaf;
			$declarations[$nested->declaration->symbol_key()] = $nested->declaration;
			return "out.owned(($value).view, &" . $nested->cpp_type() . '::scpp_escape_encode_fields);';
		}
		if ($leaf instanceof record_type_definition) {
			$declarations[$leaf->declaration->symbol_key()] = $leaf->declaration;
			return "($value)." . self::encode_method($leaf) . '(out);';
		}
		return "out.write($value);";
	}
}
