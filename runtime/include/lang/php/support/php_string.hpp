#pragma once

#include "lang/php/support/php_common.hpp"
#include "modules/strings/strings.hpp"

namespace scpp::php {

inline int_t strlen(const string_t &value) {
	return scpp::str::length(value);
}

inline int_t strlen(const nullable<string_t> &value) {
	return scpp::str::length(value);
}

inline mixed_t strpos(const string_t &haystack, const string_t &needle) {
	const auto position = scpp::str::find(haystack, needle);
	if (!position.has_value().native_value()) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(position.value());
}

inline mixed_t strpos(const string_t &haystack, const string_t &needle, const int_t &offset) {
	const auto position = scpp::str::find(haystack, needle, offset);
	if (!position.has_value().native_value()) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(position.value());
}

inline mixed_t strrpos(const string_t &haystack, const string_t &needle) {
	const auto position = scpp::str::rfind(haystack, needle);
	if (!position.has_value().native_value()) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(position.value());
}

inline mixed_t strrpos(const string_t &haystack, const string_t &needle, const int_t &offset) {
	const auto position = scpp::str::rfind(haystack, needle, offset);
	if (!position.has_value().native_value()) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(position.value());
}

inline string_t strtolower(const string_t &value) {
	return scpp::str::lower(value);
}

inline string_t strtoupper(const string_t &value) {
	return scpp::str::upper(value);
}

inline string_t lcfirst(const string_t &value) {
	return scpp::str::lcfirst(value);
}

inline string_t ucfirst(const string_t &value) {
	return scpp::str::ucfirst(value);
}

inline bool_t str_starts_with(const string_t &haystack, const string_t &needle) {
	return scpp::str::starts_with(haystack, needle);
}

inline bool_t str_ends_with(const string_t &haystack, const string_t &needle) {
	return scpp::str::ends_with(haystack, needle);
}

inline string_t ltrim(const string_t &value) {
	return scpp::str::ltrim(value);
}

inline string_t ltrim(const string_t &value, const string_t &mask) {
	return scpp::str::ltrim(value, mask);
}

inline string_t rtrim(const string_t &value) {
	return scpp::str::rtrim(value);
}

inline string_t rtrim(const string_t &value, const string_t &mask) {
	return scpp::str::rtrim(value, mask);
}

inline string_t trim(const string_t &value) {
	return scpp::str::trim(value);
}

inline string_t trim(const string_t &value, const string_t &mask) {
	return scpp::str::trim(value, mask);
}

inline string_t substr(const string_t &value, const int_t &offset, const int_t &length) {
	return scpp::str::substr(value, offset, length);
}

inline string_t substr(const string_t &value, const int_t &offset) {
	return scpp::str::substr(value, offset);
}

inline int_t substr_compare(const string_t &main_str, const string_t &str, const int_t &offset) {
	return scpp::str::substr_compare(main_str, str, offset);
}

inline int_t substr_compare(const string_t &main_str, const string_t &str, const int_t &offset, const int_t &length) {
	return scpp::str::substr_compare(main_str, str, offset, length);
}

inline int_t substr_compare(const string_t &main_str, const string_t &str, const int_t &offset, const int_t &length, const bool_t &case_insensitive) {
	return scpp::str::substr_compare(main_str, str, offset, length, case_insensitive);
}

inline string_t substr_replace(const string_t &subject, const string_t &replacement, const int_t &offset) {
	return scpp::str::substr_replace(subject, replacement, offset);
}

inline string_t substr_replace(const string_t &subject, const string_t &replacement, const int_t &offset, const int_t &length) {
	return scpp::str::substr_replace(subject, replacement, offset, length);
}

inline string_t str_replace(const string_t &search, const string_t &replace, const string_t &subject) {
	return scpp::str::replace(search, replace, subject);
}

inline string_t str_pad(const string_t &input, const int_t &pad_length, const string_t &pad_string, const int_t &pad_type) {
	return scpp::str::pad(input, pad_length, pad_string, pad_type);
}

inline string_t str_pad(const string_t &input, const int_t &pad_length, const string_t &pad_string) {
	return scpp::str::pad(input, pad_length, pad_string);
}

inline string_t str_pad(const string_t &input, const int_t &pad_length) {
	return scpp::str::pad(input, pad_length);
}


inline mixed_t explode(const string_t &separator, const string_t &string, const int_t &limit) {
	auto pieces = scpp::str::split(separator, string, limit);
	hash_t<mixed_t> parts;
	for (std::size_t index = 0; index < pieces.size(); ++index) {
		static_cast<void>(parts.append(mixed_t(pieces.native_value()[index])));
	}
	return mixed_t(unique<hash_t<mixed_t>>(std::move(parts)));
}

inline mixed_t explode(const string_t &separator, const string_t &string) {
	return explode(separator, string, PHP_INT_MAX);
}

template <typename K>
inline string_t implode(const string_t &separator, const hash_t<string_t, K> &pieces) {
	return scpp::str::join(separator, pieces);
}

inline string_t implode(const string_t &separator, const vector_t<string_t> &pieces) {
	return scpp::str::join(separator, pieces);
}

inline string_t implode(const string_t &separator, const mixed_t &pieces) {
	vector_t<string_t> items;
	if (const auto *table = pieces.table_if(); table != nullptr) {
		for (auto it = table->begin_entries(); it != table->end_entries(); ++it) {
			items.push_back(cast<string_t>((*it).value_ref()));
		}
		return scpp::str::join(separator, items);
	}
	if (const auto *shared_table = pieces.shared_table_if(); shared_table != nullptr && *shared_table != null) {
		for (auto it = (*shared_table)->begin_entries(); it != (*shared_table)->end_entries(); ++it) {
			items.push_back(cast<string_t>((*it).value_ref()));
		}
		return scpp::str::join(separator, items);
	}
	if (const auto *weak_table = pieces.weak_table_if(); weak_table != nullptr) {
		const auto locked = weak_table->lock();
		if (locked != null) {
			for (auto it = locked->begin_entries(); it != locked->end_entries(); ++it) {
				items.push_back(cast<string_t>((*it).value_ref()));
			}
			return scpp::str::join(separator, items);
		}
	}

	throw std::runtime_error("implode(): Argument #2 ($pieces) must be array-like");
}

inline mixed_t hex2bin(const string_t &value) {
	const auto decoded = scpp::str::hex_decode(value);
	if (!decoded.has_value().native_value()) {
		return mixed_t(bool_t(false));
	}
	return mixed_t(decoded.value());
}

inline string_t bin2hex(const string_t &value) {
	return scpp::str::hex_encode(value);
}


inline string_t number_format(const int_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::str::number_format(value, decimals, decimal_separator, thousands_separator);
}

inline string_t number_format(const int_t &value, const int_t &decimals) {
	return scpp::str::number_format(value, decimals);
}

inline string_t number_format(const int_t &value) {
	return scpp::str::number_format(value);
}

inline string_t number_format(const float_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::str::number_format(value, decimals, decimal_separator, thousands_separator);
}

inline string_t number_format(const float_t &value, const int_t &decimals) {
	return scpp::str::number_format(value, decimals);
}

inline string_t number_format(const float_t &value) {
	return scpp::str::number_format(value);
}

inline string_t number_format(const string_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::str::number_format(value, decimals, decimal_separator, thousands_separator);
}

inline string_t number_format(const string_t &value, const int_t &decimals) {
	return scpp::str::number_format(value, decimals);
}

inline string_t number_format(const string_t &value) {
	return scpp::str::number_format(value);
}

inline string_t number_format(const bool_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::str::number_format(value, decimals, decimal_separator, thousands_separator);
}

inline string_t number_format(const bool_t &value, const int_t &decimals) {
	return scpp::str::number_format(value, decimals);
}

inline string_t number_format(const bool_t &value) {
	return scpp::str::number_format(value);
}

inline string_t number_format(const mixed_t &value, const int_t &decimals, const string_t &decimal_separator, const string_t &thousands_separator) {
	return scpp::str::number_format(value, decimals, decimal_separator, thousands_separator);
}

inline string_t number_format(const mixed_t &value, const int_t &decimals) {
	return scpp::str::number_format(value, decimals);
}

inline string_t number_format(const mixed_t &value) {
	return scpp::str::number_format(value);
}

// Implements PHP microtime() string mode.
// How: system_clock is sampled once, then formatted as "0.xxxxxxxx seconds" to mirror PHP's default contract.
inline string_t microtime() {
	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	const auto seconds = micros_total / static_cast<std::int64_t>(1000000);
	const auto micros_part = micros_total % static_cast<std::int64_t>(1000000);

	std::ostringstream stream;
	stream << std::fixed << std::setprecision(8)
		<< (static_cast<double>(micros_part) / 1000000.0)
		<< ' ' << seconds;
	return string_t(stream.str());
}

// Implements the numeric branch of PHP microtime(true).
// How: the helper is split out explicitly because the current runtime does not model a string|float union return type for one overload.
inline float_t microtime(bool_t as_float) {
	if (!as_float.native_value()) {
		throw std::logic_error("microtime(false) is not supported; use microtime() for string form");
	}

	const auto now = std::chrono::system_clock::now();
	const auto since_epoch = now.time_since_epoch();
	const auto micros_total = std::chrono::duration_cast<std::chrono::microseconds>(since_epoch).count();
	return float_t(static_cast<double>(micros_total) / 1000000.0);
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const string_t &value) {
	return value;
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const int_t &value) {
	return string_t(std::to_string(value.native_value()));
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const float_t &value) {
	std::ostringstream stream;
	stream << value.native_value();
	return string_t(stream.str());
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const bool_t &value) {
	return string_t(value.native_value() ? "1" : "");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(null_t) {
	return string_t("");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(nullopt_t) {
	return string_t("");
}

// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(nullptr_t) {
	return string_t("");
}

// Converts one dynamic table value into its PHP echo/string representation.
// How: scalar branches delegate to the existing overloads; missing-like branches stringify to empty string.
inline string_t to_string(const mixed_t &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return string_t("");
		case mixed_t::kind_t::bool_v:
			return to_string(value.bool_value());
		case mixed_t::kind_t::int_v:
			return to_string(value.int_value());
		case mixed_t::kind_t::float_v:
			return to_string(value.float_value());
		case mixed_t::kind_t::string_v: {
			const auto *s = value.string_if();
			return s == nullptr ? string_t("") : to_string(*s);
		}
		case mixed_t::kind_t::table_v:
		case mixed_t::kind_t::shared_table_v:
		case mixed_t::kind_t::weak_table_v:
			return string_t("Array");
		case mixed_t::kind_t::dynamic_v:
			return string_t("Object");
	}
	return string_t("");
}

template <typename T>
// Converts one runtime value into its PHP echo/string representation.
// How: behavior is defined here once so the generator and runtime can share one coercion layer.
inline string_t to_string(const nullable<T> &value) {
	if (!value.has_value().native_value()) {
		return string_t("");
	}
	return to_string(value.value());
}

template <typename T>
inline string_t to_string(const result_or_false<T> &value) {
	if (!value.has_value().native_value()) {
		return string_t("");
	}
	return to_string(value.value());
}

template <typename T>
inline string_t to_string(const result_or_bool<T> &value) {
	if (value.has_value().native_value()) {
		return to_string(value.value());
	}
	if (value.is_true().native_value()) {
		return to_string(bool_t(true));
	}
	return string_t("");
}

template <typename T>
inline string_t to_string(const result<T> &value) {
	if (!value.has_value().native_value()) {
		return value.error()->get_message();
	}
	return to_string(value.value());
}

// Prints one runtime value according to the PHP echo contract implemented by the prototype.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo_one(const string_t &value) {
	std::cout << value.native_value();
}

template <typename T>
requires requires (const std::remove_cvref_t<T> &value) {
	{ to_string(value) } -> std::same_as<string_t>;
}
// Prints one runtime value according to the PHP echo contract implemented by the prototype.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo_one(T &&value) {
	std::cout << to_string(std::forward<T>(value)).native_value();
}

// Prints one or more values using the runtime echo helpers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo() {
}

template <typename... Args>
// Prints one or more values using the runtime echo helpers.
// How: behavior is defined here once so the generator can lower into stable helpers instead of ad-hoc code.
inline void echo(Args &&...args) {
	(echo_one(std::forward<Args>(args)), ...);
}

template <typename Fn>
requires requires (Fn &&fn) {
	std::forward<Fn>(fn)();
}
// Evaluates one deferred echo operand and prints it.
// How: the thunk form preserves PHP left-to-right operand evaluation when the generator wants one logical echo call.
inline void echo_eval_one(Fn &&fn) {
	echo_one(std::forward<Fn>(fn)());
}

template <typename... Fns>
// Evaluates deferred echo operands left-to-right and prints them.
// How: a comma-fold over thunk invocations preserves sequencing while still allowing the generator to emit one runtime call.
inline void echo_eval(Fns &&...fns) {
	(echo_eval_one(std::forward<Fns>(fns)), ...);
}

} // namespace scpp::php
