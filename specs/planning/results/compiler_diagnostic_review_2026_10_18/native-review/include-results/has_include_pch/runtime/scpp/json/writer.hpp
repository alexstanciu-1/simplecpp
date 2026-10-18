#pragma once

#include "scpp/hash_t.hpp"
#include "scpp/vector_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/result.hpp"
#include "scpp/support/dynamic_value.hpp"
#include <unordered_set>
#include <type_traits>

namespace scpp::json::writer_detail {

template <typename T> struct is_supported : std::false_type {};
template <> struct is_supported<null_t> : std::true_type {};
template <> struct is_supported<bool_t> : std::true_type {};
template <> struct is_supported<int_t<>> : std::true_type {};
template <> struct is_supported<float_t> : std::true_type {};
template <> struct is_supported<string_t> : std::true_type {};
template <> struct is_supported<mixed_t> : std::true_type {};
template <> struct is_supported<detail::dynamic_value> : std::true_type {};
template <typename T> struct is_supported<nullable<T>> : is_supported<T> {};
template <typename T> struct is_supported<vector_t<T>> : is_supported<T> {};
template <typename T, typename K> struct is_supported<hash_t<T, K>> : std::bool_constant<
	is_supported<T>::value && (std::is_same_v<K, string_t> || std::is_same_v<K, int_t<>>)> {};
template <typename T> inline constexpr bool is_supported_v = is_supported<std::remove_cvref_t<T>>::value;

// Only checked serialization failures are caught at the result boundary.
struct encode_failure final { const char *message; };

class writer final {
	std::string output_;
	std::unordered_set<const void *> active_;
	void key(const string_t &value);
	void key(const int_t<> &value);
	void key(const mixed_t &value);
	template <typename Box> bool scalar(const Box &value);
	template <typename Table>
	void table(const Table &value, bool array) {
		if (!active_.insert(&value).second)
			throw encode_failure{"json_encode: cyclic container is not supported"};
		struct path_guard final {
			std::unordered_set<const void *> &active;
			const void *identity;
			~path_guard() { active.erase(identity); }
		} guard{active_, &value};
		output_.push_back(array ? '[' : '{');
		bool first = true;
		value.debug_visit_entries([&](const auto &entry_key, const auto &entry) {
			if (!first) output_.push_back(',');
			first = false;
			if (!array) { key(entry_key); output_.push_back(':'); }
			write(entry);
		});
		output_.push_back(array ? ']' : '}');
	}

public:
	writer() { output_.reserve(64); }
	void write(null_t);
	void write(const bool_t &value);
	void write(const int_t<> &value);
	void write(const float_t &value);
	void write(const string_t &value);
	void write(const mixed_t &value);
	void write(const detail::dynamic_value &value);
	template <typename T> void write(const nullable<T> &value) {
		if (value.has_value().native_value()) write(value.value());
		else write(null_t{});
	}
	template <typename T> void write(const vector_t<T> &value) {
		output_.push_back('[');
		bool first = true;
		for (const auto &entry : value.native_value()) {
			if (!first) output_.push_back(',');
			first = false;
			write(entry);
		}
		output_.push_back(']');
	}
	template <typename T, typename K> void write(const hash_t<T, K> &value) {
		// Typed hashes are objects even when their integer keys happen to be packed.
		table(value, false);
	}
	string_t finish() && { return string_t{std::move(output_)}; }
};

template <typename T>
result<string_t> encode(const T &value) {
	try {
		writer output;
		output.write(value);
		return std::move(output).finish();
	} catch (const encode_failure &failure) {
		return error_t{string_t{failure.message}};
	}
}

} // namespace scpp::json::writer_detail
