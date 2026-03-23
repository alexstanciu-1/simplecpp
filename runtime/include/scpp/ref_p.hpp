#pragma once

#include "scpp/detail.hpp"

namespace scpp {

// Safe reference wrapper for value-like storage.
//
// Purpose:
// - represents the future Simple C++ reference operator on non-handle values
// - avoids raw pointer arithmetic and ownership transfer
// - intentionally refuses wrapper stacking such as ref_p<shared_p<T>>
template <typename T>
class ref_p final {
private:
	static_assert(!detail::is_handle_like_v<T>, "ref_p<T> cannot wrap handle-like runtime wrappers");
	static_assert(!detail::is_ref_like_v<T>, "ref_p<T> cannot wrap ref_p<T>");

	T *value_;

public:
	ref_p() = delete;

	explicit ref_p(T &value) noexcept
		: value_(std::addressof(value)) {
	}

	ref_p(const ref_p &) noexcept = default;
	ref_p(ref_p &&) noexcept = default;
	ref_p &operator=(const ref_p &) noexcept = default;
	ref_p &operator=(ref_p &&) noexcept = default;

	[[nodiscard]] T &get() const noexcept {
		return *value_;
	}

	[[nodiscard]] T &deref() const noexcept {
		return *value_;
	}

	[[nodiscard]] T *arrow() const noexcept {
		return value_;
	}

	[[nodiscard]] T *operator->() const noexcept {
		return value_;
	}

	[[nodiscard]] T &operator*() const noexcept {
		return *value_;
	}

	operator T &() const noexcept {
		return *value_;
	}
};

} // namespace scpp
