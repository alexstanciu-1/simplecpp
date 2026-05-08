#pragma once

#include "modules/regex/regex.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/result_or_false.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

#include <functional>

namespace scpp::php::detail {

[[nodiscard]] inline mixed_t vector_to_php_array(const vector_t<string_t> &values) {
	auto table = unique<hash_t<mixed_t>>();
	for (std::size_t index = 0; index < values.size(); ++index) {
		static_cast<void>(table->append(mixed_t(values[index])));
	}
	return mixed_t(std::move(table));
}

[[nodiscard]] inline mixed_t nested_vector_to_php_array(const vector_t<vector_t<string_t>> &rows) {
	auto outer = unique<hash_t<mixed_t>>();
	for (std::size_t index = 0; index < rows.size(); ++index) {
		static_cast<void>(outer->append(vector_to_php_array(rows[index])));
	}
	return mixed_t(std::move(outer));
}

[[nodiscard]] inline mixed_t named_match_table_to_php_array(const hash_t<string_t, string_t> &values) {
	auto table = unique<hash_t<mixed_t>>();
	for (auto it = values.begin_entries(); it != values.end_entries(); ++it) {
		const auto entry = *it;
		const string_t &key = entry.key();
		const string_t &value = entry.value_ref();
		std::int64_t numeric_key = 0;
		bool is_numeric = !key.native_value().empty();
		if (is_numeric) {
			for (const char ch : key.native_value()) {
				if (ch < '0' || ch > '9') {
					is_numeric = false;
					break;
				}
				numeric_key = (numeric_key * 10) + static_cast<std::int64_t>(ch - '0');
			}
		}
		if (is_numeric) {
			table->set(int_t(numeric_key), mixed_t(value));
		} else {
			table->set(key, mixed_t(value));
		}
	}
	return mixed_t(std::move(table));
}

[[nodiscard]] inline mixed_t nested_named_match_tables_to_php_array(const vector_t<hash_t<string_t, string_t>> &rows) {
	auto outer = unique<hash_t<mixed_t>>();
	for (std::size_t index = 0; index < rows.size(); ++index) {
		static_cast<void>(outer->append(named_match_table_to_php_array(rows[index])));
	}
	return mixed_t(std::move(outer));
}

[[nodiscard]] inline vector_t<string_t> php_array_to_string_vector(const mixed_t &input) {
	vector_t<string_t> out;
	const auto *table = input.try_get_hash();
	if (table == nullptr) {
		return out;
	}
	for (std::size_t index = 0; index < table->size(); ++index) {
		out.append(static_cast<string_t>((*table)[static_cast<std::int64_t>(index)]));
	}
	return out;
}

[[nodiscard]] inline mixed_t php_array_filter_preserving_keys(
	const mixed_t &input,
	const std::function<nullable<mixed_t>(const mixed_t &key, const string_t &value)> &mapper
) {
	auto out = unique<hash_t<mixed_t>>();
	const auto *table = input.try_get_hash();
	if (table == nullptr) {
		return mixed_t(std::move(out));
	}
	for (auto it = table->begin_entries(); it != table->end_entries(); ++it) {
		const auto entry = *it;
		const mixed_t key(entry.key());
		const auto mapped = mapper(key, static_cast<string_t>(entry.value_ref()));
		if (!mapped.has_value().native_value()) {
			continue;
		}
		out->set(key, mapped.value());
	}
	return mixed_t(std::move(out));
}

} // namespace scpp::php::detail

namespace scpp::php {

[[nodiscard]] inline result_or_false<mixed_t> preg_filter(const string_t &pattern, const string_t &replacement, const mixed_t &input, const int_t &limit, int_t &count);

[[nodiscard]] inline bool_t preg_jit_available() {
	return scpp::regex::jit_available();
}

[[nodiscard]] inline string_t preg_quote(const string_t &text) {
	return scpp::regex::quote(text);
}

[[nodiscard]] inline string_t preg_quote(const string_t &text, const string_t &delimiter) {
	return scpp::regex::quote(text, delimiter);
}

[[nodiscard]] inline result_or_false<int_t> preg_match(const string_t &pattern, const string_t &subject) {
	const auto result = scpp::regex::match(pattern, subject);
	if (result.is_false().native_value()) {
		return false_sentinel;
	}
	return int_t(result.value().empty().native_value() ? 0 : 1);
}

[[nodiscard]] inline result_or_false<int_t> preg_match(const string_t &pattern, const string_t &subject, mixed_t &matches) {
	const auto result = scpp::regex::match_named(pattern, subject);
	if (result.is_false().native_value()) {
		matches = detail::named_match_table_to_php_array(hash_t<string_t, string_t>());
		return false_sentinel;
	}
	matches = detail::named_match_table_to_php_array(result.value());
	return int_t(result.value().empty().native_value() ? 0 : 1);
}

[[nodiscard]] inline result_or_false<int_t> preg_match_all(const string_t &pattern, const string_t &subject) {
	const auto result = scpp::regex::match_all(pattern, subject);
	if (result.is_false().native_value()) {
		return false_sentinel;
	}
	return int_t(static_cast<std::int64_t>(result.value().size()));
}

[[nodiscard]] inline result_or_false<int_t> preg_match_all(const string_t &pattern, const string_t &subject, mixed_t &matches) {
	const auto result = scpp::regex::match_all_named(pattern, subject);
	if (result.is_false().native_value()) {
		matches = detail::nested_named_match_tables_to_php_array(vector_t<hash_t<string_t, string_t>>());
		return false_sentinel;
	}
	matches = detail::nested_named_match_tables_to_php_array(result.value());
	return int_t(static_cast<std::int64_t>(result.value().size()));
}

[[nodiscard]] inline result_or_false<mixed_t> preg_grep(const string_t &pattern, const mixed_t &input) {
	bool invalid_pattern = false;
	const auto output = detail::php_array_filter_preserving_keys(
		input,
		[&](const mixed_t &, const string_t &value) -> nullable<mixed_t> {
			const auto matched = scpp::regex::match(pattern, value);
			if (matched.is_false().native_value()) {
				invalid_pattern = true;
				return nullable<mixed_t>(nullopt);
			}
			if (matched.value().empty().native_value()) {
				return nullable<mixed_t>(nullopt);
			}
			nullable<mixed_t> result;
			result.native_value() = mixed_t(value);
			return result;
		});
	if (invalid_pattern) {
		return false_sentinel;
	}
	return output;
}

[[nodiscard]] inline result_or_false<mixed_t> preg_filter(const string_t &pattern, const string_t &replacement, const mixed_t &input) {
	bool invalid_pattern = false;
	const auto output = detail::php_array_filter_preserving_keys(
		input,
		[&](const mixed_t &, const string_t &value) -> nullable<mixed_t> {
			const auto replaced = scpp::regex::replace(pattern, replacement, value);
			if (replaced.is_false().native_value()) {
				invalid_pattern = true;
				return nullable<mixed_t>(nullopt);
			}
			if (replaced.value().native_value() == value.native_value()) {
				return nullable<mixed_t>(nullopt);
			}
			nullable<mixed_t> result;
			result.native_value() = mixed_t(replaced.value());
			return result;
		});
	if (invalid_pattern) {
		return false_sentinel;
	}
	return output;
}

[[nodiscard]] inline result_or_false<mixed_t> preg_filter(const string_t &pattern, const string_t &replacement, const mixed_t &input, const int_t &limit) {
	bool invalid_pattern = false;
	const auto output = detail::php_array_filter_preserving_keys(
		input,
		[&](const mixed_t &, const string_t &value) -> nullable<mixed_t> {
			const auto replaced = scpp::regex::replace(pattern, replacement, value, limit);
			if (replaced.is_false().native_value()) {
				invalid_pattern = true;
				return nullable<mixed_t>(nullopt);
			}
			if (replaced.value().native_value() == value.native_value()) {
				return nullable<mixed_t>(nullopt);
			}
			nullable<mixed_t> result;
			result.native_value() = mixed_t(replaced.value());
			return result;
		});
	if (invalid_pattern) {
		return false_sentinel;
	}
	return output;
}

[[nodiscard]] inline result_or_false<mixed_t> preg_filter(const string_t &pattern, const string_t &replacement, const mixed_t &input, int_t &count) {
	return preg_filter(pattern, replacement, input, int_t(-1), count);
}

[[nodiscard]] inline result_or_false<mixed_t> preg_filter(const string_t &pattern, const string_t &replacement, const mixed_t &input, const int_t &limit, int_t &count) {
	bool invalid_pattern = false;
	std::int64_t replacements = 0;
	const auto output = detail::php_array_filter_preserving_keys(
		input,
		[&](const mixed_t &, const string_t &value) -> nullable<mixed_t> {
			int_t local_count(0);
			const auto replaced = scpp::regex::replace(pattern, replacement, value, limit, local_count);
			if (replaced.is_false().native_value()) {
				invalid_pattern = true;
				return nullable<mixed_t>(nullopt);
			}
			replacements += local_count.native_value();
			if (local_count.native_value() == 0) {
				return nullable<mixed_t>(nullopt);
			}
			nullable<mixed_t> result;
			result.native_value() = mixed_t(replaced.value());
			return result;
		});
	if (invalid_pattern) {
		return false_sentinel;
	}
	count = int_t(replacements);
	return output;
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback(const string_t &pattern, const std::function<string_t(mixed_t)> &callback, const string_t &subject) {
	const auto adapter = [&callback](vector_t<string_t> values) -> string_t {
		return callback(detail::vector_to_php_array(values));
	};
	return scpp::regex::replace_callback(pattern, adapter, subject, int_t(-1));
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback(const string_t &pattern, const std::function<string_t(mixed_t)> &callback, const string_t &subject, const int_t &limit) {
	const auto adapter = [&callback](vector_t<string_t> values) -> string_t {
		return callback(detail::vector_to_php_array(values));
	};
	return scpp::regex::replace_callback(pattern, adapter, subject, limit);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback(const string_t &pattern, const std::function<string_t(mixed_t)> &callback, const string_t &subject, int_t &count) {
	const auto adapter = [&callback](vector_t<string_t> values) -> string_t {
		return callback(detail::vector_to_php_array(values));
	};
	return scpp::regex::replace_callback(pattern, adapter, subject, count);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback(const string_t &pattern, const std::function<string_t(mixed_t)> &callback, const string_t &subject, const int_t &limit, int_t &count) {
	const auto adapter = [&callback](vector_t<string_t> values) -> string_t {
		return callback(detail::vector_to_php_array(values));
	};
	return scpp::regex::replace_callback(pattern, adapter, subject, limit, count);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback_array(const hash_t<std::function<string_t(mixed_t)>, string_t> &callbacks, const string_t &subject) {
	hash_t<std::function<string_t(vector_t<string_t>)>, string_t> adapter_callbacks;
	for (auto it = callbacks.begin_entries(); it != callbacks.end_entries(); ++it) {
		const auto entry = *it;
		const auto adapter = [callback = entry.value_ref()](vector_t<string_t> values) -> string_t {
			return callback(detail::vector_to_php_array(values));
		};
		adapter_callbacks.set(entry.key(), std::move(adapter));
	}
	return scpp::regex::replace_callback_array(adapter_callbacks, subject, int_t(-1));
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback_array(const hash_t<std::function<string_t(mixed_t)>, string_t> &callbacks, const string_t &subject, const int_t &limit) {
	hash_t<std::function<string_t(vector_t<string_t>)>, string_t> adapter_callbacks;
	for (auto it = callbacks.begin_entries(); it != callbacks.end_entries(); ++it) {
		const auto entry = *it;
		const auto adapter = [callback = entry.value_ref()](vector_t<string_t> values) -> string_t {
			return callback(detail::vector_to_php_array(values));
		};
		adapter_callbacks.set(entry.key(), std::move(adapter));
	}
	return scpp::regex::replace_callback_array(adapter_callbacks, subject, limit);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback_array(const hash_t<std::function<string_t(mixed_t)>, string_t> &callbacks, const string_t &subject, int_t &count) {
	hash_t<std::function<string_t(vector_t<string_t>)>, string_t> adapter_callbacks;
	for (auto it = callbacks.begin_entries(); it != callbacks.end_entries(); ++it) {
		const auto entry = *it;
		const auto adapter = [callback = entry.value_ref()](vector_t<string_t> values) -> string_t {
			return callback(detail::vector_to_php_array(values));
		};
		adapter_callbacks.set(entry.key(), std::move(adapter));
	}
	return scpp::regex::replace_callback_array(adapter_callbacks, subject, count);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace_callback_array(const hash_t<std::function<string_t(mixed_t)>, string_t> &callbacks, const string_t &subject, const int_t &limit, int_t &count) {
	hash_t<std::function<string_t(vector_t<string_t>)>, string_t> adapter_callbacks;
	for (auto it = callbacks.begin_entries(); it != callbacks.end_entries(); ++it) {
		const auto entry = *it;
		const auto adapter = [callback = entry.value_ref()](vector_t<string_t> values) -> string_t {
			return callback(detail::vector_to_php_array(values));
		};
		adapter_callbacks.set(entry.key(), std::move(adapter));
	}
	return scpp::regex::replace_callback_array(adapter_callbacks, subject, limit, count);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace(const string_t &pattern, const string_t &replacement, const string_t &subject) {
	return scpp::regex::replace(pattern, replacement, subject);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace(const string_t &pattern, const string_t &replacement, const string_t &subject, const int_t &limit) {
	return scpp::regex::replace(pattern, replacement, subject, limit);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace(const string_t &pattern, const string_t &replacement, const string_t &subject, int_t &count) {
	return scpp::regex::replace(pattern, replacement, subject, count);
}

[[nodiscard]] inline result_or_false<string_t> preg_replace(const string_t &pattern, const string_t &replacement, const string_t &subject, const int_t &limit, int_t &count) {
	return scpp::regex::replace(pattern, replacement, subject, limit, count);
}

[[nodiscard]] inline result_or_false<mixed_t> preg_split(const string_t &pattern, const string_t &subject) {
	const auto result = scpp::regex::split(pattern, subject);
	if (result.is_false().native_value()) {
		return false_sentinel;
	}
	return detail::vector_to_php_array(result.value());
}

[[nodiscard]] inline result_or_false<mixed_t> preg_split(const string_t &pattern, const string_t &subject, const int_t &limit) {
	const auto result = scpp::regex::split(pattern, subject, limit);
	if (result.is_false().native_value()) {
		return false_sentinel;
	}
	return detail::vector_to_php_array(result.value());
}

[[nodiscard]] inline result_or_false<mixed_t> preg_split(const string_t &pattern, const string_t &subject, const int_t &limit, const int_t &flags) {
	const auto result = scpp::regex::split(pattern, subject, limit, flags);
	if (result.is_false().native_value()) {
		return false_sentinel;
	}
	return detail::vector_to_php_array(result.value());
}

} // namespace scpp::php
