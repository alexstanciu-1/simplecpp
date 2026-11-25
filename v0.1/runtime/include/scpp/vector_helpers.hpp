#pragma once

#include "scpp/vector_t.hpp"

// Native provider for the strict vector helpers; public bindings are generated.
namespace scpp::php {

template <typename T>
inline void vector_reserve(vector_t<T> &values, const int_t<> &capacity) {
	const auto native_capacity = capacity.native_value();
	if (native_capacity < 0) {
		throw runtime_error(
			"vector_reserve capacity must be non-negative.",
			"value_error",
			"runtime",
			"vector_reserve"
		);
	}
	values.reserve(static_cast<std::size_t>(native_capacity));
}

template <typename T>
inline int_t<> vector_capacity(const vector_t<T> &values) {
	return int_t<>(static_cast<std::int64_t>(values.capacity()));
}

template <typename T>
inline void vector_resize(vector_t<T> &values, const int_t<> &count, const T &default_value) {
	const auto native_count = count.native_value();
	if (native_count < 0) {
		throw runtime_error(
			"vector_resize count must be non-negative.",
			"value_error",
			"runtime",
			"vector_resize"
		);
	}
	values.resize(static_cast<std::size_t>(native_count), default_value);
}

template <typename T>
inline vector_t<T> vector_filled(const int_t<> &count, const T &default_value) {
	const auto native_count = count.native_value();
	if (native_count < 0) {
		throw runtime_error(
			"vector_filled count must be non-negative.",
			"value_error",
			"runtime",
			"vector_filled"
		);
	}

	vector_t<T> values;
	values.resize(static_cast<std::size_t>(native_count), default_value);
	return values;
}

template <typename T>
inline void vector_clear(vector_t<T> &values) {
	values.clear();
}

template <typename T>
inline void vector_clear_keep_capacity(vector_t<T> &values) {
	values.clear();
}

template <typename T>
inline void vector_compact(vector_t<T> &values) {
	values.compact();
}

template <typename T>
inline void vector_compact(vector_t<T> &values, const int_t<> &capacity) {
	const auto native_capacity = capacity.native_value();
	if (native_capacity < 0) {
		throw runtime_error(
			"vector_compact capacity must be non-negative.",
			"value_error",
			"runtime",
			"vector_compact"
		);
	}
	values.compact(static_cast<std::size_t>(native_capacity));
}
} // namespace scpp::php
