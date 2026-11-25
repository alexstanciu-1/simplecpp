#pragma once

#include "modules/json/json.hpp"
#include "scpp/detail.hpp"
#include "scpp/cast.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/vector_t.hpp"

#include <type_traits>

namespace scpp::json {

using json_value = detail::dynamic_value;

namespace from_json_detail {

template <typename T>
struct target_type_name_t;

template <typename T>
[[nodiscard]] inline std::string target_type_name();

template <typename Value>
[[nodiscard]] inline std::string kind_name(const Value &value) {
	using kind = typename Value::kind_t;
	switch (value.kind()) {
		case kind::null_v:
			return "null";
		case kind::bool_v:
			return "bool";
		case kind::int_v:
			return "int";
		case kind::float_v:
			return "float";
		case kind::string_v:
			return "string";
		case kind::table_v:
			return "table";
		default: break;
	}
	if constexpr (std::is_same_v<Value, mixed_t>) {
		switch (value.kind()) {
			case kind::shared_table_v: return "shared_table";
			case kind::weak_table_v: return "weak_table";
			default: break;
		}
	}
	return "unknown";
}

template <typename Value>
[[noreturn]] inline void throw_conversion_error(
	const char *message,
	const std::string &path,
	const std::string &target_type,
	const Value &actual_value
) {
	throw runtime_error(
		std::string("scpp::json::from_json conversion failed: ")
			+ message
			+ " at "
			+ (path.empty() ? std::string("$") : path)
			+ " for target "
			+ target_type
			+ " from "
			+ kind_name(actual_value),
		"json_from_json_conversion_failed",
		"scpp::json::from_json",
		"",
		{
			{"json_path", path.empty() ? "$" : path},
			{"target_type", target_type},
			{"actual_kind", kind_name(actual_value)},
		}
	);
}

template <typename T, typename Value>
[[nodiscard]] inline const auto &require_table(
	const Value &value,
	const std::string &path
) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		throw_conversion_error("expected JSON array/object container", path, target_type_name<T>(), value);
	}
	return *table;
}

template <typename T>
struct is_supported : std::false_type {};

template <> struct is_supported<bool_t> : std::true_type {};
template <> struct is_supported<int_t<>> : std::true_type {};
template <> struct is_supported<float_t> : std::true_type {};
template <> struct is_supported<string_t> : std::true_type {};
template <> struct is_supported<mixed_t> : std::true_type {};
template <> struct is_supported<detail::dynamic_value> : std::true_type {};

template <typename T>
struct is_supported<nullable<T>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
struct is_supported<vector_t<T>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
struct is_supported<hash_t<T, string_t>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
constexpr bool is_supported_v = is_supported<T>::value;

template <typename T, typename Value>
[[nodiscard]] inline T convert(const Value &value, const std::string &path);

template <>
struct target_type_name_t<bool_t> final {
	[[nodiscard]] static std::string get() { return "bool_t"; }
};

template <>
struct target_type_name_t<int_t<>> final {
	[[nodiscard]] static std::string get() { return "int_t"; }
};

template <>
struct target_type_name_t<float_t> final {
	[[nodiscard]] static std::string get() { return "float_t"; }
};

template <>
struct target_type_name_t<string_t> final {
	[[nodiscard]] static std::string get() { return "string_t"; }
};

template <>
struct target_type_name_t<mixed_t> final {
	[[nodiscard]] static std::string get() { return "mixed_t"; }
};

template <>
struct target_type_name_t<detail::dynamic_value> final {
	[[nodiscard]] static std::string get() { return "dynamic_t"; }
};

template <typename T>
struct target_type_name_t<nullable<T>> final {
	[[nodiscard]] static std::string get() { return "nullable<" + target_type_name_t<T>::get() + ">"; }
};

template <typename T>
struct target_type_name_t<vector_t<T>> final {
	[[nodiscard]] static std::string get() { return "vector<" + target_type_name_t<T>::get() + ">"; }
};

template <typename T>
struct target_type_name_t<hash_t<T, string_t>> final {
	[[nodiscard]] static std::string get() { return "hash<" + target_type_name_t<T>::get() + ">"; }
};

template <typename T>
[[nodiscard]] inline std::string target_type_name() {
	return target_type_name_t<T>::get();
}

template <typename T, typename Value>
[[nodiscard]] inline nullable<T> convert(const Value &value, const std::string &path, nullable<T> *) {
	if (value.is_null().native_value()) {
		return nullable<T>(null_t{});
	}
	return nullable<T>(convert<T>(value, path));
}

template <typename T, typename Value>
[[nodiscard]] inline vector_t<T> convert(const Value &value, const std::string &path, vector_t<T> *) {
	const auto &table = require_table<vector_t<T>>(value, path);
	if (!table.is_packed().native_value()) {
		throw_conversion_error("expected JSON array", path, target_type_name<vector_t<T>>(), value);
	}

	vector_t<T> out;
	std::size_t index = 0;
	table.debug_visit_entries([&](const mixed_t &, const auto &entry) {
		out.append(convert<T>(entry, (path.empty() ? std::string("$") : path) + "[" + std::to_string(index) + "]"));
		++index;
	});
	return out;
}

template <typename T, typename Value>
[[nodiscard]] inline hash_t<T, string_t> convert(const Value &value, const std::string &path, hash_t<T, string_t> *) {
	const auto &table = require_table<hash_t<T, string_t>>(value, path);
	if (table.is_packed().native_value()) {
		throw_conversion_error("expected JSON object", path, target_type_name<hash_t<T, string_t>>(), value);
	}

	hash_t<T, string_t> out;
	table.debug_visit_entries([&](const mixed_t &key, const auto &entry) {
		if (!key.is_string().native_value()) {
			throw runtime_error(
				"scpp::json::from_json object key is not a string",
				"json_from_json_object_key_not_string",
				"scpp::json::from_json",
				"",
				{
					{"json_path", path.empty() ? "$" : path},
					{"target_type", target_type_name<hash_t<T, string_t>>()},
					{"actual_key_kind", kind_name(key)},
				}
			);
		}
		const auto &string_key = key.get_string();
		const auto child_path = (path.empty() ? std::string("$") : path) + "." + string_key.native_value();
		out.set(string_key, convert<T>(entry, child_path));
	});
	return out;
}

template <typename T, typename Value>
[[nodiscard]] inline T convert(const Value &value, const std::string &path) {
	static_assert(is_supported_v<T>, "scpp::json::from_json is not defined for this target type");
	using kind = typename Value::kind_t;
	if constexpr (std::is_same_v<T, bool_t>) {
		if (value.kind() != kind::bool_v)
			throw_conversion_error("expected JSON boolean", path, target_type_name<T>(), value);
		return value.get_bool();
	} else if constexpr (std::is_same_v<T, int_t<>>) {
		if (value.kind() != kind::int_v)
			throw_conversion_error("expected JSON integer number", path, target_type_name<T>(), value);
		return value.get_int();
	} else if constexpr (std::is_same_v<T, float_t>) {
		if (value.kind() == kind::float_v) return value.get_float();
		if (value.kind() == kind::int_v) return float_t(static_cast<double>(value.get_int().native_value()));
		throw_conversion_error("expected JSON number", path, target_type_name<T>(), value);
	} else if constexpr (std::is_same_v<T, string_t>) {
		if (value.kind() != kind::string_v)
			throw_conversion_error("expected JSON string", path, target_type_name<T>(), value);
		return value.get_string();
	} else if constexpr (std::is_same_v<T, mixed_t> || std::is_same_v<T, detail::dynamic_value>) {
		if constexpr (std::is_same_v<T, Value>) {
			if constexpr (std::is_same_v<T, mixed_t>) return value.clone();
			else return value;
		} else {
			// Only scalar/null family crossings are allowed, including nested extraction.
			if (value.kind() != kind::null_v && value.kind() != kind::bool_v
				&& value.kind() != kind::int_v && value.kind() != kind::float_v && value.kind() != kind::string_v)
				throw_conversion_error("cross-family JSON container conversion is not supported", path, target_type_name<T>(), value);
			return scpp::cast<T>(value);
		}
	} else if constexpr (detail::is_specialization_of<T, nullable>::value
		|| detail::is_specialization_of<T, vector_t>::value || detail::is_specialization_of<T, hash_t>::value) {
		return convert(value, path, static_cast<T *>(nullptr));
	} else {
		static_assert(detail::always_false_v<T>, "scpp::json::from_json is not defined for this target type");
	}
}

} // namespace from_json_detail

template <typename T, typename Value>
	requires(std::is_same_v<Value, mixed_t> || std::is_same_v<Value, detail::dynamic_value>)
[[nodiscard]] inline T from_json(const Value &value) {
	return from_json_detail::convert<T>(value, "");
}

template <typename T>
[[nodiscard]] inline T from_json(const string_t &json_text) {
	// The text convenience follows the same dynamic default as json_decode.
	return from_json<T>(decode(json_text).value());
}

} // namespace scpp::json
