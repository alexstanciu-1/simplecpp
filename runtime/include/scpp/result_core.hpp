#pragma once

#include "scpp/detail.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/error_t.hpp"
#include "scpp/false_sentinel_t.hpp"
#include "scpp/true_sentinel_t.hpp"
#include "scpp/null_t.hpp"
#include "scpp/nullopt_t.hpp"

#include <variant>

namespace scpp {

namespace detail {

enum class result_state : std::uint8_t {
	value,
	error,
	false_value,
	true_value,
};

template <typename T, bool AllowError, bool AllowTrue>
class result_core {
private:
	std::variant<T, error_t, std::monostate> storage_;
	result_state state_;

	[[noreturn]] static void throw_value_access(const char *context, const char *type_name) {
		throw std::runtime_error(std::string(type_name) + ": " + context + " requires a present value");
	}

	[[noreturn]] static void throw_error_access(const char *context, const char *type_name) {
		throw std::runtime_error(std::string(type_name) + ": " + context + " requires an error state");
	}

protected:
	static constexpr const char *type_name() {
		if constexpr (AllowError) {
			return "scpp::result";
		} else if constexpr (AllowTrue) {
			return "scpp::result_or_bool";
		} else {
			return "scpp::result_or_false";
		}
	}

	result_core() requires std::default_initializable<T> && AllowError
		: storage_(T{}), state_(result_state::value) {
	}

	result_core() requires (!AllowError)
		: storage_(std::monostate{}), state_(result_state::false_value) {
	}

	result_core(const T &value)
		: storage_(value), state_(result_state::value) {
	}

	result_core(T &&value) noexcept(std::is_nothrow_move_constructible_v<T>)
		: storage_(std::move(value)), state_(result_state::value) {
	}

	result_core(const error_t &error_value) requires AllowError
		: storage_(error_value), state_(result_state::error) {
	}

	result_core(error_t &&error_value) noexcept(std::is_nothrow_move_constructible_v<error_t>) requires AllowError
		: storage_(std::move(error_value)), state_(result_state::error) {
	}

	result_core(error_sentinel_t) requires AllowError
		: storage_(error_t{}), state_(result_state::error) {
	}

	result_core(false_sentinel_t) requires (!AllowError)
		: storage_(std::monostate{}), state_(result_state::false_value) {
	}

	result_core(null_t) requires (!AllowError)
		: storage_(std::monostate{}), state_(result_state::false_value) {
	}

	result_core(nullopt_t) requires (!AllowError)
		: storage_(std::monostate{}), state_(result_state::false_value) {
	}

	result_core(const bool_t &value) requires (!AllowError && AllowTrue)
		: storage_(std::monostate{}), state_(value.native_value() ? result_state::true_value : result_state::false_value) {
	}

	result_core(bool value) requires (!AllowError && AllowTrue)
		: storage_(std::monostate{}), state_(value ? result_state::true_value : result_state::false_value) {
	}

	result_core(true_sentinel_t) requires (!AllowError && AllowTrue)
		: storage_(std::monostate{}), state_(result_state::true_value) {
	}

	result_core &assign_value(const T &value) {
		storage_ = value;
		state_ = result_state::value;
		return *this;
	}

	result_core &assign_value(T &&value) noexcept(std::is_nothrow_move_assignable_v<T>) {
		storage_ = std::move(value);
		state_ = result_state::value;
		return *this;
	}

	result_core &assign_error(const error_t &error_value) requires AllowError {
		storage_ = error_value;
		state_ = result_state::error;
		return *this;
	}

	result_core &assign_error(error_t &&error_value) noexcept(std::is_nothrow_move_assignable_v<error_t>) requires AllowError {
		storage_ = std::move(error_value);
		state_ = result_state::error;
		return *this;
	}

	result_core &assign_error(error_sentinel_t) requires AllowError {
		storage_ = error_t{};
		state_ = result_state::error;
		return *this;
	}

	result_core &assign_false(false_sentinel_t) requires (!AllowError) {
		storage_ = std::monostate{};
		state_ = result_state::false_value;
		return *this;
	}

	result_core &assign_false(null_t) requires (!AllowError) {
		storage_ = std::monostate{};
		state_ = result_state::false_value;
		return *this;
	}

	result_core &assign_false(nullopt_t) requires (!AllowError) {
		storage_ = std::monostate{};
		state_ = result_state::false_value;
		return *this;
	}

	result_core &assign_bool(const bool_t &value) requires (!AllowError && AllowTrue) {
		storage_ = std::monostate{};
		state_ = value.native_value() ? result_state::true_value : result_state::false_value;
		return *this;
	}

	result_core &assign_bool(bool value) requires (!AllowError && AllowTrue) {
		storage_ = std::monostate{};
		state_ = value ? result_state::true_value : result_state::false_value;
		return *this;
	}

	result_core &assign_true(true_sentinel_t) requires (!AllowError && AllowTrue) {
		storage_ = std::monostate{};
		state_ = result_state::true_value;
		return *this;
	}

public:
	[[nodiscard]] bool_t has_value() const noexcept {
		return bool_t(state_ == result_state::value);
	}

	[[nodiscard]] bool_t has_error() const noexcept requires AllowError {
		return bool_t(state_ == result_state::error);
	}

	[[nodiscard]] bool_t is_false() const noexcept requires (!AllowError) {
		return bool_t(state_ == result_state::false_value);
	}

	[[nodiscard]] bool_t is_true() const noexcept requires (!AllowError && AllowTrue) {
		return bool_t(state_ == result_state::true_value);
	}

	void reset() noexcept requires (!AllowError) {
		storage_ = std::monostate{};
		state_ = result_state::false_value;
	}

	void reset(false_sentinel_t) noexcept requires (!AllowError) {
		storage_ = std::monostate{};
		state_ = result_state::false_value;
	}

	T &require_value(const char *context) {
		if (state_ != result_state::value) {
			throw_value_access(context, type_name());
		}
		return std::get<T>(storage_);
	}

	const T &require_value(const char *context) const {
		if (state_ != result_state::value) {
			throw_value_access(context, type_name());
		}
		return std::get<T>(storage_);
	}

	T &value() { return require_value("value()"); }
	const T &value() const { return require_value("value() const"); }

	error_t &require_error(const char *context) requires AllowError {
		if (state_ != result_state::error) {
			throw_error_access(context, type_name());
		}
		return std::get<error_t>(storage_);
	}

	const error_t &require_error(const char *context) const requires AllowError {
		if (state_ != result_state::error) {
			throw_error_access(context, type_name());
		}
		return std::get<error_t>(storage_);
	}

	error_t *error() requires AllowError { return &require_error("error()"); }
	const error_t *error() const requires AllowError { return &require_error("error() const"); }

	auto operator->() requires (!detail::has_arrow_operator_v<T>) { return &require_value("operator->()"); }
	auto operator->() const requires (!detail::has_arrow_operator_v<T>) { return &require_value("operator->() const"); }
	auto operator->() requires detail::has_arrow_operator_v<T> { return require_value("operator->()").operator->(); }
	auto operator->() const requires detail::has_arrow_operator_v<T> { return require_value("operator->() const").operator->(); }

	operator T() const requires std::copy_constructible<T> { return require_value("implicit typed boundary conversion"); }

	[[nodiscard]] result_state native_state() const noexcept {
		return state_;
	}
};

} // namespace detail

} // namespace scpp
