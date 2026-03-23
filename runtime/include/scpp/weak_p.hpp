#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullptr_t.hpp"
#include "scpp/shared_p.hpp"

namespace scpp {

// Weak observational semantic wrapper.
//
// Enforces:
// - weak references never own the object
// - conversion into weak_p is only defined from shared ownership
// - null comparisons are currently modeled via expired()/empty weak state
template <typename T>
// Weak ownership observer wrapper aligned with the pointer helper rules.
// Spec link: this type centralizes behavior so generated code follows runtime/specs/spec.md instead of raw STL semantics.
class weak_p final {
private:
	std::weak_ptr<T> value_;

public:
	weak_p() = default;

	// Sentinel constructors produce an empty weak reference.
	weak_p(null_t) noexcept
		: value_() {
	}

	weak_p(nullptr_t) noexcept
		: value_() {
	}

	// Explicit native constructor for controlled boundary crossing.
	explicit weak_p(std::weak_ptr<T> value) noexcept
		: value_(std::move(value)) {
	}

	// Shared ownership can be observed weakly without changing ownership counts permanently.
	weak_p(const shared_p<T> &value) noexcept
		: value_(value.native_value()) {
	}

	// Reports whether the referenced object can no longer be locked.
	[[nodiscard]] bool_t expired() const noexcept {
		return bool_t(value_.expired());
	}

	// Reconstitutes shared ownership if the object is still alive.
	[[nodiscard]] shared_p<T> lock() const noexcept {
		return shared_p<T>(value_.lock());
	}

	// Controlled access to the native weak pointer.
	[[nodiscard]] const std::weak_ptr<T> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::weak_ptr<T> &native_value() noexcept {
		return value_;
	}

	// Null semantics are currently tied to expired()/empty weak state.
	[[nodiscard]] friend bool_t operator==(const weak_p<T> &left, null_t) noexcept {
		return bool_t(left.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator==(null_t, const weak_p<T> &right) noexcept {
		return bool_t(right.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(const weak_p<T> &left, null_t) noexcept {
		return bool_t(!left.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(null_t, const weak_p<T> &right) noexcept {
		return bool_t(!right.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator==(const weak_p<T> &left, nullptr_t) noexcept {
		return bool_t(left.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator==(nullptr_t, const weak_p<T> &right) noexcept {
		return bool_t(right.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(const weak_p<T> &left, nullptr_t) noexcept {
		return bool_t(!left.value_.expired());
	}

	// Implements one runtime operator overload required by the current type contract.
	// How: the overload keeps operations in wrapper space and returns wrapper results where the spec requires it.
	[[nodiscard]] friend bool_t operator!=(nullptr_t, const weak_p<T> &right) noexcept {
		return bool_t(!right.value_.expired());
	}
};

} // namespace scpp
