#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"

namespace scpp {

template <typename T>
class nullable final {
private:
	// Stores the optional wrapped payload used by the runtime nullable contract.
	std::optional<T> value_;

	// Covers the project rule that nullable<T> misuse must raise a runtime error through the runtime surface instead of leaking std::optional exceptions.
	// Throws a project-shaped runtime error when a caller tries to read an empty nullable value.
	[[noreturn]] static void throw_empty_access(const char *context) {
		throw std::runtime_error(std::string("scpp::nullable: ") + context + " requires a present value");
	}

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

	// Covers the centralized nullable-lifting rule used by casts and generated operators.
	// Returns the wrapped value when present; otherwise throws a project-shaped runtime error with caller context.
	T &require_value(const char *context) {
		if (!value_.has_value()) {
			throw_empty_access(context);
		}
		return *value_;
	}

	// Covers the centralized nullable-lifting rule used by casts and generated operators.
	// Returns the wrapped value when present; otherwise throws a project-shaped runtime error with caller context.
	const T &require_value(const char *context) const {
		if (!value_.has_value()) {
			throw_empty_access(context);
		}
		return *value_;
	}

	// Covers the runtime rule that value extraction from nullable<T> is checked and must not leak raw std::optional access semantics.
	// Returns the wrapped value when present; otherwise throws a project-shaped runtime error.
	T &value() {
		return require_value("value()");
	}

	// Covers the runtime rule that const value extraction from nullable<T> is checked and must not leak raw std::optional access semantics.
	// Returns the wrapped value when present; otherwise throws a project-shaped runtime error.
	const T &value() const {
		return require_value("value() const");
	}

	// Covers nullable object/property dereference for direct object payloads and pointer-like runtime wrappers.
	// Empty nullable dereference fails through the centralized runtime error path with explicit arrow context.
	auto operator->() requires (!detail::has_arrow_operator_v<T>) {
		return &require_value("operator->()");
	}

	// Covers nullable object/property dereference for direct object payloads and pointer-like runtime wrappers.
	// Empty nullable dereference fails through the centralized runtime error path with explicit arrow context.
	auto operator->() const requires (!detail::has_arrow_operator_v<T>) {
		return &require_value("operator->() const");
	}

	// Covers forwarding nullable dereference when the wrapped runtime type already exposes operator->.
	// Empty nullable dereference fails before forwarding so wrapper semantics stay centralized in nullable<T>.
	auto operator->() requires detail::has_arrow_operator_v<T> {
		return require_value("operator->()").operator->();
	}

	// Covers forwarding nullable dereference when the wrapped runtime type already exposes operator->.
	// Empty nullable dereference fails before forwarding so wrapper semantics stay centralized in nullable<T>.
	auto operator->() const requires detail::has_arrow_operator_v<T> {
		return require_value("operator->() const").operator->();
	}

	template <typename U>
	T value_or(U &&fallback) const {
		return value_.value_or(static_cast<T>(std::forward<U>(fallback)));
	}


	// Covers the temporary typed-boundary bridge required while the current generator remains symbol/type-blind.
	// Allows nullable<T> to satisfy typed by-value or const-reference boundaries by implicitly unwrapping when present and throwing on empty state.
	operator T() const requires std::copy_constructible<T> {
		return require_value("implicit typed boundary conversion");
	}

	[[nodiscard]] const std::optional<T> &native_value() const noexcept {
		return value_;
	}

	[[nodiscard]] std::optional<T> &native_value() noexcept {
		return value_;
	}
};

} // namespace scpp
