#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/shared_p.hpp"

namespace scpp {

template <typename T>
class weak_p final {
private:
	std::weak_ptr<T> value_;

	template <typename>
	friend class weak_p;

public:
	using element_type = T;

	weak_p() = default;
	weak_p(null_t) noexcept : value_() {}
	weak_p(nullptr_t) noexcept : value_() {}
	explicit weak_p(std::weak_ptr<T> value) noexcept : value_(std::move(value)) {}

	weak_p(const shared_p<T> &value) noexcept : value_(value.native_value()) {}

	template <typename U>
	weak_p(const shared_p<U> &value) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::static_pointer_cast<T>(value.native_value())) {}

	template <typename U>
	weak_p(const weak_p<U> &value) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(value.value_) {}

	template <typename U>
	weak_p &operator=(const shared_p<U> &value) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = std::static_pointer_cast<T>(value.native_value());
		return *this;
	}

	template <typename U>
	weak_p &operator=(const weak_p<U> &value) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = value.value_;
		return *this;
	}

	weak_p &operator=(null_t) noexcept {
		value_.reset();
		return *this;
	}

	weak_p &operator=(nullptr_t) noexcept {
		value_.reset();
		return *this;
	}

	[[nodiscard]] bool_t expired() const noexcept { return bool_t(value_.expired()); }
	[[nodiscard]] std::size_t use_count() const noexcept { return value_.use_count(); }
	[[nodiscard]] long debug_use_count() const noexcept { return value_.use_count(); }
	[[nodiscard]] shared_p<T> lock() const noexcept { return shared_p<T>(value_.lock()); }
	void reset() noexcept { value_.reset(); }
	void reset(null_t) noexcept { value_.reset(); }
	void reset(nullptr_t) noexcept { value_.reset(); }
	void swap(weak_p &other) noexcept { value_.swap(other.value_); }

	[[nodiscard]] const std::weak_ptr<T> &native_value() const noexcept { return value_; }
	[[nodiscard]] std::weak_ptr<T> &native_value() noexcept { return value_; }

	[[nodiscard]] explicit operator bool() const noexcept { return !value_.expired(); }
};

} // namespace scpp
