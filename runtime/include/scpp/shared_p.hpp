#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullptr_t.hpp"

namespace scpp {

// Shared ownership semantic wrapper.
//
// Enforces:
// - ownership flows through scpp::shared_p rather than raw std::shared_ptr at the language level
// - null comparisons use runtime sentinels
// - dereference checks can fail with a runtime error instead of producing UB on null access
template <typename T>
class shared_p final {
private:
	std::shared_ptr<T> value_;

public:
	shared_p() = default;

	// Sentinel constructors normalize null-like values to an empty shared_ptr.
	shared_p(null_t) noexcept
		: value_(nullptr) {
	}

	shared_p(nullptr_t) noexcept
		: value_(nullptr) {
	}

	// Explicit native constructor for controlled boundary crossing.
	explicit shared_p(std::shared_ptr<T> value) noexcept
		: value_(std::move(value)) {
	}

	// Presence query returned as bool_t.
	[[nodiscard]] bool_t has_value() const noexcept {
		return bool_t(static_cast<bool>(value_));
	}

	// Raw pointer access for integration code.
	[[nodiscard]] T *get() const noexcept {
		return value_.get();
	}

	// Checked dereference.
	// Throws instead of allowing undefined behavior on null access.
	T &deref() const {
		if (!value_) {
			throw std::runtime_error("scpp::shared_p dereference on null");
		}
		return *value_;
	}

	// Member-access helper for generated code.
	T *arrow() const noexcept {
		return value_.get();
	}

	// Native C++ member-access bridge used by generated object calls.
	T *operator->() const noexcept {
		return value_.get();
	}

	// Controlled access to the native smart pointer.
	[[nodiscard]] const std::shared_ptr<T> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::shared_ptr<T> &native_value() noexcept {
		return value_;
	}

	// Sentinel-aware equality/inequality.
	[[nodiscard]] friend bool_t operator==(const shared_p<T> &left, null_t) noexcept {
		return bool_t(left.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator==(null_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(const shared_p<T> &left, null_t) noexcept {
		return bool_t(left.value_ != nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(null_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ != nullptr);
	}

	[[nodiscard]] friend bool_t operator==(const shared_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator==(nullptr_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ == nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(const shared_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_ != nullptr);
	}

	[[nodiscard]] friend bool_t operator!=(nullptr_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ != nullptr);
	}

	// Shared pointer equality preserves pointer identity semantics.
	[[nodiscard]] friend bool_t operator==(const shared_p<T> &left, const shared_p<T> &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	[[nodiscard]] friend bool_t operator!=(const shared_p<T> &left, const shared_p<T> &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}
};

} // namespace scpp
