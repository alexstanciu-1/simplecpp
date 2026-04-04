#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"

namespace scpp {

template <typename T>
class nullable final {
private:
	std::optional<T> value_;

public:
	nullable() = default;
	nullable(null_t) noexcept
		: value_(std::nullopt) {
	}
	nullable(nullopt_t) noexcept
		: value_(std::nullopt) {
	}
	nullable(const T &value)
		: value_(value) {
	}
	nullable(T &&value) noexcept(std::is_nothrow_move_constructible_v<T>)
		: value_(std::move(value)) {
	}

	nullable &operator=(null_t) noexcept {
		value_.reset();
		return *this;
	}

	nullable &operator=(nullopt_t) noexcept {
		value_.reset();
		return *this;
	}

	nullable &operator=(const T &value) {
		value_ = value;
		return *this;
	}

	nullable &operator=(T &&value) noexcept(std::is_nothrow_move_assignable_v<T>) {
		value_ = std::move(value);
		return *this;
	}

	[[nodiscard]] bool_t has_value() const noexcept {
		return bool_t(value_.has_value());
	}

	void reset() noexcept {
		value_.reset();
	}

	void reset(null_t) noexcept {
		value_.reset();
	}

	void reset(nullopt_t) noexcept {
		value_.reset();
	}

	T &value() {
		return value_.value();
	}

	const T &value() const {
		return value_.value();
	}

	template <typename U>
	T value_or(U &&fallback) const {
		return value_.value_or(static_cast<T>(std::forward<U>(fallback)));
	}

	[[nodiscard]] const std::optional<T> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::optional<T> &native_value() noexcept {
		return value_;
	}
};

} // namespace scpp
