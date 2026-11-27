<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Concrete nominal result storage and field loops for prepared record decoder demands. */
trait Cpp_Record_Decoder
{
	private function record_demand(ast_record_declaration $node, dependency_use $use): bool
	{
		return $node->is_data && $node->definition->uses_helper($use);
	}

	/** Source fields and a record named decode keep their ordinary C++ names. */
	private static function decode_method(record_type_definition $record): string
	{
		return $record->declaration->short_name === 'decode' || isset($record->declaration->fields['decode'])
			? 'scpp_escape_decode' : 'decode';
	}

	/** The inactive union member never constructs a record, including on parse failure. @param bool[] $includes */
	private function result_declaration(record_type_definition $record, array &$includes /** array<string,bool> */): string
	{
		$result = $record->record_result();
		foreach ($result->cpp_headers() as $header) $includes[$header] = true;
		$includes['scpp/decoder.hpp'] = $includes['new'] = $includes['stdexcept'] = $includes['utility'] = true;
		$name = $result->cpp_type();
		$value = $record->default_type()->cpp_type();
		$decode = $this->record_demand($record->declaration, dependency_use::record_json_decode);
		$text = $decode ? "    static $name from_text(const ::scpp::string&);\n" : '';
		$cursor = !$decode || type_registry::resolve('json_pull_reader') === null ? '' : "    static Value from_cursor(const ::scpp::json_pull_reader&);\n";
		if ($cursor !== '') foreach (type_json_pull_reader::instance()->cpp_headers() as $header) $includes[$header] = true;
		if ($this->record_demand($record->declaration, dependency_use::record_borrow_storage)) {
			$includes['memory'] = true;
			return <<<CPP
struct $name {
    using Value = $value;
    $name() = default;
    explicit $name(Value input) : payload(std::make_shared<Value>(std::move(input))) {}
    explicit $name(::scpp::error_t error) : failure(std::move(error)) {
        if (!failure.has_error()) throw std::invalid_argument("result_requires_real_error");
    }
    $name(const $name& other) : payload(other.payload ? std::make_shared<Value>(*other.payload) : nullptr), failure(other.failure) {}
    $name($name&&) = default;
    $name& operator=($name other) { payload.swap(other.payload); failure = std::move(other.failure); return *this; }
    bool has_value() const noexcept { return bool(payload); }
    bool is_success() const noexcept { return has_value(); }
    bool has_error() const noexcept { return failure.has_error(); }
    ::scpp::error_t error() const { if (!has_error()) throw std::runtime_error("result_no_error"); return failure; }
    Value& checked_ref() { check(); return *payload; }
    const Value& checked_ref() const { check(); return *payload; }
    auto pin() { check(); return payload; }
    std::shared_ptr<const Value> pin() const { check(); return payload; }
    const Value& value() const & { return checked_ref(); }
    Value value() && { return std::move(checked_ref()); }
$text$cursor
private:
    std::shared_ptr<Value> payload;
    ::scpp::error_t failure;
    void check() const { if (!payload) throw std::runtime_error("carrier_no_value"); }
};

CPP;
		}
		return <<<CPP
struct $name {
    using Value = $value;
    $name() {}
    explicit $name(Value value) : payload(std::move(value)), present(true) {}
    explicit $name(::scpp::error_t error) : failure(std::move(error)) {
        if (!failure.has_error()) throw std::invalid_argument("result_requires_real_error");
    }
    ~$name() { clear(); }
    $name(const $name& other) : failure(other.failure) {
        if (other.present) { new (&payload) Value(other.payload); present = true; }
    }
    $name($name&& other) : failure(std::move(other.failure)) {
        if (other.present) { new (&payload) Value(std::move(other.payload)); present = true; }
    }
    $name& operator=($name other) {
        clear();
        failure = std::move(other.failure);
        if (other.present) { new (&payload) Value(std::move(other.payload)); present = true; }
        return *this;
    }
    bool has_value() const noexcept { return present; }
    bool is_success() const noexcept { return has_value(); }
    bool has_error() const noexcept { return failure.has_error(); }
    ::scpp::error_t error() const {
        if (!has_error()) throw std::runtime_error("result_no_error");
        return failure;
    }
    Value& checked_ref() { check(); return payload; }
    const Value& checked_ref() const { check(); return payload; }
    const Value& value() const & { return checked_ref(); }
    Value value() && { check(); return std::move(payload); }
$text$cursor
private:
    union { Value payload; };
    bool present = false;
    ::scpp::error_t failure;
    void clear() { if (present) { payload.~Value(); present = false; } }
    void check() const { if (!present) throw std::runtime_error("carrier_no_value"); }
};

CPP;
	}

	/** Emit one definition in the schema's owning unit. @param mixed[] $declarations @param bool[] $headers */
	private function decode_definition(record_type_definition $record, array &$declarations, array &$headers): string
	{
		$value = $record->default_type();
		if ($value instanceof shared_record_type_definition) $declarations[$value->name] = $value;
		$name = $value->cpp_type();
		$text_reader = 'read_leaf_record';
		$contract = runtime_functions::resolve('php:func:json_decode_as');
		$locals = $lookup = $cases = $finish = $arguments = [];
		foreach ($record->declaration->data_constructor->parameters as $i => $field) {
			$type = $field->storage_type();
			$leaf = $type instanceof native_sequence_type ? $type->write_type() : $type;
			$nested = $leaf instanceof shared_record_type_definition ? $leaf->payload : $leaf;
			$slot = 'field_' . $i;
			$spelling = Cpp_String_Literals::bytes($field->short_name);
			$body = [];
			$native = $type->cpp_type();
			if ($nested instanceof record_type_definition) {
				// Nested construction can run authored code before the parent finishes reading.
				$text_reader = 'read_record';
				$declarations[$nested->declaration->symbol_key()] = $nested->declaration;
				if ($leaf instanceof shared_record_type_definition) $declarations[$leaf->name] = $leaf;
				$read = $leaf->cpp_type() . '::' . self::decode_method($nested) . '(d)';
			} else $read = 'd.' . $leaf->structural_reader() . '()';
			if ($nested instanceof record_type_definition && $type === $leaf) {
				$storage = $nested->record_result()->cpp_type();
				$locals[] = "$storage $slot;";
				$body[] = "$slot = $storage($read);";
				$present = "$slot.has_value()";
				$take = "std::move($slot).value()";
			} else {
				$locals[] = "$native $slot{};";
				$present = "seen_$i";
				if ($field->required_data_input() || $field->initializer !== null) {
					$locals[] = "bool $present = false;";
					$body[] = "$present = true;";
				}
				if (isset($contract->sequences[$type::class])) {
					$body[] = 'd.' . $contract->sequences[$type::class] . "($slot);";
				} elseif ($type instanceof native_sequence_type) {
					$body[] = 'd.begin_array();';
					$body[] = 'while (d.next_element()) ' . $type->lower_cpp_append_value($slot, $read) . ';';
				} else $body[] = "$slot = $read;";
				$take = "std::move($slot)";
			}
			if ($field->required_data_input()) $finish[] = "if (!($present)) d.reject(\"missing required field: {$field->short_name}\");";
			elseif ($field->initializer !== null) {
				$default = $this->declaration_initialization($type, $field->initializer, $field->conversion, $headers);
				$finish[] = "if (!$present) $slot = $default;";
			} elseif ($nested instanceof record_type_definition && $type === $leaf) $take = "($present ? $take : $native{})";
			$lookup[] = "    if (key == $spelling) return $i;";
			$cases[] = "    case $i:\n        " . implode("\n        ", $body) . "\n        break;";
			$arguments[] = $take;
		}
		$mask = '{}';
		if ($lookup !== []) {
			$words = intdiv(count($lookup) + 63, 64);
			$locals[] = "std::uint64_t seen_fields[$words]{};";
			$mask = 'seen_fields';
		}
		$key = $lookup === [] ? 'std::string_view' : 'std::string_view key';
		$lines = [...$locals, "constexpr auto lookup = []($key) {", ...$lookup,
			'    return ::scpp::Decoder::unknown_field;', '};', 'd.begin_object();',
			"for (int field; (field = d.next_record_field(lookup, $mask)) != ::scpp::Decoder::end_fields;) {",
			'    switch (field) {', ...$cases, '    default: d.reject("unknown field");', '    }', '}', ...$finish,
			'return scpp_escape_from_data(' . implode(', ', $arguments) . ');'];
		$method = self::decode_method($record);
		$code = "$name {$name}::{$method}(::scpp::Decoder& d) {\n    " . implode("\n    ", $lines) . "\n}\n";
		if (!$this->record_demand($record->declaration, dependency_use::record_json_decode)) return $code;
		$result = $record->record_result()->cpp_type();
		$headers[runtime_functions::resolve('php:func:json_decode_as')->header] = true;
		$initialize = $this->record_demand($record->declaration, dependency_use::record_borrow_storage)
			? "slot.payload = std::make_shared<Value>($name::{$method}(d));"
			: "new (&slot.payload) Value($name::{$method}(d)); slot.present = true;";
		$consume = "[](void* out, ::scpp::Decoder& d) { auto& slot = *static_cast<$result*>(out); "
			. "$initialize }";
		$code .= "$result {$result}::from_text(const ::scpp::string& text) {\n    $result result;\n"
			. "    auto error = ::scpp::json::{$text_reader}(text, &result, $consume);\n"
			. "    if (error.has_error()) return $result(std::move(error));\n    return result;\n}\n";
		if (type_registry::resolve('json_pull_reader') !== null) {
			$code .= "$name {$result}::from_cursor(const ::scpp::json_pull_reader& reader) {\n    $result result;\n"
				. "    reader.with_decoder(&result, $consume);\n    return std::move(result).value();\n}\n";
		}
		return $code;
	}
}
