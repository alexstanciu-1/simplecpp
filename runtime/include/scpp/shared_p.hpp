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
// - implicit wrapper-level upcasts are allowed only when U* -> T* is a valid conversion
//
// Examples:
// - shared_p<Derived> -> shared_p<Base>          : allowed
// - shared_p<Concrete> -> shared_p<Interface>    : allowed
// - shared_p<Base> -> shared_p<Derived>          : rejected
//
template <typename T>
// Shared ownership wrapper used for PHP object-handle semantics and safe implicit upcasts.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class shared_p final {
private:
	std::shared_ptr<T> value_;

	template <typename>
	friend class shared_p;

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

	// Safe implicit upcast constructor.
	//
	// This keeps wrapper semantics aligned with std::shared_ptr upcasts while still
	// rejecting downcasts and unrelated conversions at compile time.
	template <typename U>
	shared_p(const shared_p<U> &other) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::static_pointer_cast<T>(other.value_)) {
	}

	// Safe implicit upcast move constructor.
	template <typename U>
	shared_p(shared_p<U> &&other) noexcept
		requires std::is_convertible_v<U *, T *>
		: value_(std::static_pointer_cast<T>(std::move(other.value_))) {
	}

	// Safe implicit upcast copy assignment.
	template <typename U>
	shared_p &operator=(const shared_p<U> &other) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = std::static_pointer_cast<T>(other.value_);
		return *this;
	}

	// Safe implicit upcast move assignment.
	template <typename U>
	shared_p &operator=(shared_p<U> &&other) noexcept
		requires std::is_convertible_v<U *, T *>
	{
		value_ = std::static_pointer_cast<T>(std::move(other.value_));
		return *this;
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

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator==(null_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ == nullptr);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(const shared_p<T> &left, null_t) noexcept {
		return bool_t(left.value_ != nullptr);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(null_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ != nullptr);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator==(const shared_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_ == nullptr);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator==(nullptr_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ == nullptr);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(const shared_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_ != nullptr);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(nullptr_t, const shared_p<T> &right) noexcept {
		return bool_t(right.value_ != nullptr);
	}

	// Shared pointer equality preserves pointer identity semantics.
	[[nodiscard]] friend bool_t operator==(const shared_p<T> &left, const shared_p<T> &right) noexcept {
		return bool_t(left.value_ == right.value_);
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(const shared_p<T> &left, const shared_p<T> &right) noexcept {
		return bool_t(left.value_ != right.value_);
	}
};

} // namespace scpp
