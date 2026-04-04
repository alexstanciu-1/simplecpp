#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullptr_t.hpp"

namespace scpp {

template <typename T>
class shared_p final {
private:
	std::shared_ptr<T> value_;

	template <typename>
	friend class shared_p;

public:
	using element_type = T;

	shared_p() = default;
	shared_p(null_t) noexcept : value_() {}
	shared_p(nullptr_t) noexcept : value_() {}
	explicit shared_p(std::shared_ptr<T> value) noexcept : value_(std::move(value)) {}

	template <typename U>
	explicit shared_p(std::shared_ptr<U> value) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::static_pointer_cast<T>(std::move(value))) {}

	template <typename U>
	shared_p(const shared_p<U> &value) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::static_pointer_cast<T>(value.value_)) {}

	template <typename U>
	shared_p(shared_p<U> &&value) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::static_pointer_cast<T>(std::move(value.value_))) {}

	template <typename U>
	shared_p &operator=(const shared_p<U> &value) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = std::static_pointer_cast<T>(value.value_);
		return *this;
	}

	template <typename U>
	shared_p &operator=(shared_p<U> &&value) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = std::static_pointer_cast<T>(std::move(value.value_));
		return *this;
	}

	shared_p &operator=(null_t) noexcept {
		value_.reset();
		return *this;
	}

	shared_p &operator=(nullptr_t) noexcept {
		value_.reset();
		return *this;
	}

	[[nodiscard]] bool_t has_value() const noexcept { return bool_t(static_cast<bool>(value_)); }
	[[nodiscard]] std::size_t use_count() const noexcept { return value_.use_count(); }
	[[nodiscard]] bool_t unique() const noexcept { return bool_t(value_.use_count() == 1U); }
	[[nodiscard]] long debug_use_count() const noexcept { return static_cast<long>(value_.use_count()); }
	void reset() noexcept { value_.reset(); }
	void reset(null_t) noexcept { value_.reset(); }
	void reset(nullptr_t) noexcept { value_.reset(); }
	void swap(shared_p &other) noexcept { value_.swap(other.value_); }

	T &deref() const {
		if (!value_) {
			throw std::runtime_error("scpp::shared_p dereference on null");
		}
		return *value_;
	}

	T *arrow() const noexcept { return value_.get(); }
	T &operator*() const { return deref(); }
	T *operator->() const noexcept { return value_.get(); }

	[[nodiscard]] const std::shared_ptr<T> &native_value() const noexcept { return value_; }
	[[nodiscard]] std::shared_ptr<T> &native_value() noexcept { return value_; }

	[[nodiscard]] T *get() const noexcept { return value_.get(); }
	[[nodiscard]] explicit operator bool() const noexcept { return static_cast<bool>(value_); }
};

} // namespace scpp
