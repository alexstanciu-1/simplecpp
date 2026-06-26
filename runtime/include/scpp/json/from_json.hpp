#pragma once

#include "modules/json/json.hpp"
#include "scpp/detail.hpp"
#include "scpp/dynamic_t.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/mixed_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/vector_t.hpp"

#include <type_traits>

namespace scpp::json {

using json_value = mixed_t;

namespace from_json_detail {

template <typename T>
struct target_type_name_t;

template <typename T>
[[nodiscard]] inline std::string target_type_name();

[[nodiscard]] inline std::string kind_name(const json_value &value) {
	switch (value.kind()) {
		case mixed_t::kind_t::null_v:
			return "null";
		case mixed_t::kind_t::bool_v:
			return "bool";
		case mixed_t::kind_t::int_v:
			return "int";
		case mixed_t::kind_t::float_v:
			return "float";
		case mixed_t::kind_t::string_v:
			return "string";
		case mixed_t::kind_t::table_v:
			return "table";
		case mixed_t::kind_t::shared_table_v:
			return "shared_table";
		case mixed_t::kind_t::weak_table_v:
			return "weak_table";
		case mixed_t::kind_t::dynamic_v:
			return "dynamic";
	}
	return "unknown";
}

[[noreturn]] inline void throw_conversion_error(
	const char *message,
	const std::string &path,
	const std::string &target_type,
	const json_value &actual_value
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

template <typename T>
[[nodiscard]] inline const hash_t<mixed_t> &require_table(
	const json_value &value,
	const std::string &path
) {
	const auto *table = value.table_if();
	if (table == nullptr) {
		throw_conversion_error("expected JSON array/object container", path, target_type_name<T>(), value);
	}
	return *table;
}

template <typename T>
[[nodiscard]] inline const dynamic_t<> &require_dynamic_handle(
	const json_value &value,
	const std::string &path
) {
	const auto *dynamic = value.dynamic_if();
	if (dynamic == nullptr) {
		throw_conversion_error("expected JSON array/object handle", path, target_type_name<T>(), value);
	}
	return *dynamic;
}

template <typename T>
struct is_supported : std::false_type {};

template <> struct is_supported<bool_t> : std::true_type {};
template <> struct is_supported<int_t<>> : std::true_type {};
template <> struct is_supported<float_t> : std::true_type {};
template <> struct is_supported<string_t> : std::true_type {};
template <> struct is_supported<mixed_t> : std::true_type {};
template <> struct is_supported<dynamic_t<>> : std::true_type {};

template <typename T>
struct is_supported<nullable<T>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
struct is_supported<vector_t<T>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
struct is_supported<hash_t<T, string_t>> : std::bool_constant<is_supported<T>::value> {};

template <typename T>
constexpr bool is_supported_v = is_supported<T>::value;

template <typename T>
[[nodiscard]] inline T convert(const json_value &value, const std::string &path);

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
struct target_type_name_t<dynamic_t<>> final {
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

template <>
[[nodiscard]] inline bool_t convert<bool_t>(const json_value &value, const std::string &path) {
	if (!value.is_bool().native_value()) {
		throw_conversion_error("expected JSON boolean", path, target_type_name<bool_t>(), value);
	}
	return value.get_bool();
}

template <>
[[nodiscard]] inline int_t<> convert<int_t<>>(const json_value &value, const std::string &path) {
	if (!value.is_int().native_value()) {
		throw_conversion_error("expected JSON integer number", path, target_type_name<int_t<>>(), value);
	}
	return value.get_int();
}

template <>
[[nodiscard]] inline float_t convert<float_t>(const json_value &value, const std::string &path) {
	if (value.is_float().native_value()) {
		return value.get_float();
	}
	if (value.is_int().native_value()) {
		return float_t(static_cast<double>(value.get_int().native_value()));
	}
	throw_conversion_error("expected JSON number", path, target_type_name<float_t>(), value);
}

template <>
[[nodiscard]] inline string_t convert<string_t>(const json_value &value, const std::string &path) {
	if (!value.is_string().native_value()) {
		throw_conversion_error("expected JSON string", path, target_type_name<string_t>(), value);
	}
	return value.get_string();
}

template <>
[[nodiscard]] inline mixed_t convert<mixed_t>(const json_value &value, const std::string &) {
	return value.clone();
}

template <>
[[nodiscard]] inline dynamic_t<> convert<dynamic_t<>>(const json_value &value, const std::string &path) {
	if (value.is_null().native_value()) {
		return dynamic_t<>(null_t{});
	}
	return require_dynamic_handle<dynamic_t<>>(value, path);
}

template <typename T>
[[nodiscard]] inline nullable<T> convert(const json_value &value, const std::string &path, nullable<T> *) {
	if (value.is_null().native_value()) {
		return nullable<T>(null_t{});
	}
	return nullable<T>(convert<T>(value, path));
}

template <typename T>
[[nodiscard]] inline vector_t<T> convert(const json_value &value, const std::string &path, vector_t<T> *) {
	const auto &table = require_table<vector_t<T>>(value, path);
	if (!table.is_packed().native_value()) {
		throw_conversion_error("expected JSON array", path, target_type_name<vector_t<T>>(), value);
	}

	vector_t<T> out;
	std::size_t index = 0;
	table.debug_visit_entries([&](const mixed_t &, const mixed_t &entry) {
		out.append(convert<T>(entry, (path.empty() ? std::string("$") : path) + "[" + std::to_string(index) + "]"));
		++index;
	});
	return out;
}

template <typename T>
[[nodiscard]] inline hash_t<T, string_t> convert(const json_value &value, const std::string &path, hash_t<T, string_t> *) {
	const auto &table = require_table<hash_t<T, string_t>>(value, path);
	if (table.is_packed().native_value()) {
		throw_conversion_error("expected JSON object", path, target_type_name<hash_t<T, string_t>>(), value);
	}

	hash_t<T, string_t> out;
	table.debug_visit_entries([&](const mixed_t &key, const mixed_t &entry) {
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

template <typename T>
[[nodiscard]] inline T convert(const json_value &value, const std::string &path) {
	static_assert(is_supported_v<T>, "scpp::json::from_json is not defined for this target type");

	if constexpr (std::is_same_v<T, bool_t>) {
		return convert<bool_t>(value, path);
	} else if constexpr (std::is_same_v<T, int_t<>>) {
		return convert<int_t<>>(value, path);
	} else if constexpr (std::is_same_v<T, float_t>) {
		return convert<float_t>(value, path);
	} else if constexpr (std::is_same_v<T, string_t>) {
		return convert<string_t>(value, path);
	} else if constexpr (std::is_same_v<T, mixed_t>) {
		return convert<mixed_t>(value, path);
	} else if constexpr (std::is_same_v<T, dynamic_t<>>) {
		return convert<dynamic_t<>>(value, path);
	} else if constexpr (detail::is_specialization_of<T, nullable>::value) {
		return convert(value, path, static_cast<T *>(nullptr));
	} else if constexpr (detail::is_specialization_of<T, vector_t>::value) {
		return convert(value, path, static_cast<T *>(nullptr));
	} else if constexpr (detail::is_specialization_of<T, hash_t>::value) {
		return convert(value, path, static_cast<T *>(nullptr));
	} else {
		static_assert(detail::always_false_v<T>, "scpp::json::from_json is not defined for this target type");
	}
}

} // namespace from_json_detail

template <typename T>
[[nodiscard]] inline T from_json(const json_value &value) {
	return from_json_detail::convert<T>(value, "");
}

template <typename T>
[[nodiscard]] inline T from_json(const string_t &json_text) {
	return from_json<T>(json_decode(json_text));
}

} // namespace scpp::json
