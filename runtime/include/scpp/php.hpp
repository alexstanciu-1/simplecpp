#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/int_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/nullable.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/unique_p.hpp"
#include "scpp/weak_p.hpp"
#include <cstdint>
#include <iostream>
#include <limits>
#include <type_traits>
#include <utility>

namespace scpp::php {

// PHP compatibility constants consumed by generated code.
inline const int_t PHP_INT_MAX{static_cast<std::int64_t>(std::numeric_limits<std::int64_t>::max())};

inline void echo_one(const string_t &value) {
	std::cout << value.native_value();
}

inline void echo_one(const int_t &value) {
	std::cout << value.native_value();
}

inline void echo_one(const float_t &value) {
	std::cout << value.native_value();
}

inline void echo_one(const bool_t &value) {
	std::cout << (value.native_value() ? "1" : "");
}

inline void echo_one(null_t) {
}

inline void echo_one(nullopt_t) {
}

inline void echo_one(nullptr_t) {
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, string_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, int_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, float_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, bool_t>
)
inline void echo_one(T &&value) {
	std::cout << std::forward<T>(value);
}

inline void echo() {
}

template <typename... Args>
inline void echo(Args &&...args) {
	(echo_one(std::forward<Args>(args)), ...);
}


inline bool_t isset() {
	return bool_t(true);
}

inline bool_t isset_one(null_t) {
	return bool_t(false);
}

inline bool_t isset_one(nullopt_t) {
	return bool_t(false);
}

inline bool_t isset_one(nullptr_t) {
	return bool_t(false);
}

template <typename T>
inline bool_t isset_one(const nullable<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const shared_p<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const unique_p<T> &value) {
	return value.has_value();
}

template <typename T>
inline bool_t isset_one(const weak_p<T> &value) {
	return bool_t(!value.expired().native_value());
}

template <typename T>
requires (
	!std::is_same_v<std::remove_cvref_t<T>, null_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullopt_t>
	&& !std::is_same_v<std::remove_cvref_t<T>, nullptr_t>
)
inline bool_t isset_one(T &&) {
	return bool_t(true);
}

template <typename... Args>
inline bool_t isset(Args &&...args) {
	bool result = true;
	((result = result && isset_one(std::forward<Args>(args)).native_value()), ...);
	return bool_t(result);
}

inline void unset() {
}

template <typename T>
inline void unset_one(nullable<T> &value) {
	value.reset();
}

template <typename T>
inline void unset_one(shared_p<T> &value) {
	value.native_value().reset();
}

template <typename T>
inline void unset_one(unique_p<T> &value) {
	value.native_value().reset();
}

template <typename T>
inline void unset_one(weak_p<T> &value) {
	value.native_value().reset();
}

template <typename T>
inline void unset_one(T &) {
	// No-op for non-nullable value types in the current prototype.
}

template <typename... Args>
inline void unset(Args &...args) {
	(unset_one(args), ...);
}

} // namespace scpp::php
