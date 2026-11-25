#pragma once

#include "scpp/bool_t.hpp"
#include "scpp/detail.hpp"

namespace scpp {

// Inline-storage semantic wrapper.
//
// Purpose:
// - marks values that should stay inline rather than heap-allocated
// - gives generated code a distinct runtime family for explicit value storage
// - keeps ownership wrappers and inline-storage wrappers separate
template <typename T>
class value_p final {
private:
	static_assert(!detail::is_handle_like_v<T>, "value_p<T> cannot wrap handle-like runtime wrappers");

	T value_;

public:
	value_p() = default;
	value_p(const value_p &) = default;
	value_p(value_p &&) noexcept = default;
	value_p &operator=(const value_p &) = default;
	value_p &operator=(value_p &&) noexcept = default;

	// Direct value adoption constructor.
	explicit value_p(const T &value)
		: value_(value) {
	}

	// Direct move-adoption constructor.
	explicit value_p(T &&value) noexcept(std::is_nothrow_move_constructible_v<T>)
		: value_(std::move(value)) {
	}

	// In-place constructor used by value<T>(...).
	template <typename... TArgs>
	explicit value_p(TArgs &&...args)
		: value_(std::forward<TArgs>(args)...) {
	}

	// Presence is always true for inline storage.
	[[nodiscard]] bool_t has_value() const noexcept {
		return bool_t(true);
	}

	// Explicit named accessors keep wrapper intent visible in hand-written code.
	[[nodiscard]] T &get() noexcept {
		return value_;
	}

	[[nodiscard]] const T &get() const noexcept {
		return value_;
	}

	// Dereference helpers mirror the pointer families while still staying inline.
	[[nodiscard]] T &deref() noexcept {
		return value_;
	}

	[[nodiscard]] const T &deref() const noexcept {
		return value_;
	}

	[[nodiscard]] T *arrow() noexcept {
		return std::addressof(value_);
	}

	[[nodiscard]] const T *arrow() const noexcept {
		return std::addressof(value_);
	}

	[[nodiscard]] T *operator->() noexcept {
		return std::addressof(value_);
	}

	[[nodiscard]] const T *operator->() const noexcept {
		return std::addressof(value_);
	}

	[[nodiscard]] T &operator*() noexcept {
		return value_;
	}

	[[nodiscard]] const T &operator*() const noexcept {
		return value_;
	}

	// Reference conversions let value_p<T> interoperate with normal C++ APIs that
	// expect T&, while still preserving wrapper intent in declarations.
	operator T &() noexcept {
		return value_;
	}

	operator const T &() const noexcept {
		return value_;
	}
};

} // namespace scpp
