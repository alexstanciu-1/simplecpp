#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/runtime_error.hpp"

namespace scpp {

template <typename T>
class unique_p final {
private:
	std::unique_ptr<T> value_;

	template <typename>
	friend class unique_p;

public:
	using element_type = T;

	unique_p() = default;
	unique_p(null_t) noexcept : value_() {}
	unique_p(nullptr_t) noexcept : value_() {}
	explicit unique_p(std::unique_ptr<T> value) noexcept : value_(std::move(value)) {}

	unique_p(unique_p &&) noexcept = default;
	unique_p &operator=(unique_p &&) noexcept = default;

	template <typename U>
	unique_p(unique_p<U> &&other) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::move(other.value_)) {}
	unique_p(const unique_p &) = delete;
	unique_p &operator=(const unique_p &) = delete;

	unique_p &operator=(null_t) noexcept {
		value_.reset();
		return *this;
	}

	unique_p &operator=(nullptr_t) noexcept {
		value_.reset();
		return *this;
	}

	template <typename U>
	unique_p &operator=(unique_p<U> &&other) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = std::move(other.value_);
		return *this;
	}

	[[nodiscard]] bool_t has_value() const noexcept { return bool_t(static_cast<bool>(value_)); }
	void reset() noexcept { value_.reset(); }
	void reset(T *ptr) noexcept { value_.reset(ptr); }
	void reset(null_t) noexcept { value_.reset(); }
	void reset(nullptr_t) noexcept { value_.reset(); }
	void swap(unique_p &other) noexcept { value_.swap(other.value_); }

	T &deref() const {
		if (!value_) {
			throw runtime_error(
				"scpp::unique_p runtime error: dereference requires a present unique pointer value.",
				"invalid_unique_dereference_null",
				"scpp::unique_p",
				"operator*"
			);
		}
		return *value_;
	}

	T *arrow() const noexcept { return value_.get(); }
	T &operator*() const { return deref(); }
	T *operator->() const {
		if (!value_) {
			throw runtime_error(
				"scpp::unique_p runtime error: operator->() requires a present unique pointer value.",
				"invalid_unique_arrow_null",
				"scpp::unique_p",
				"operator->"
			);
		}
		return value_.get();
	}

	[[nodiscard]] const std::unique_ptr<T> &native_value() const noexcept { return value_; }
	[[nodiscard]] std::unique_ptr<T> &native_value() noexcept { return value_; }

	[[nodiscard]] T *get() const noexcept { return value_.get(); }
	[[nodiscard]] T *release() noexcept { return value_.release(); }
	[[nodiscard]] explicit operator bool() const noexcept { return static_cast<bool>(value_); }
};

} // namespace scpp
