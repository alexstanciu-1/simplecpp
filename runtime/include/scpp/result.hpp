#pragma once

#include "scpp/result_core.hpp"

namespace scpp {

template <typename T>
class result final : public detail::result_core<T, true, false> {
private:
	using base_t = detail::result_core<T, true, false>;

public:
	result() requires std::default_initializable<T> = default;
	result(const T &value) : base_t(value) {}
	result(T &&value) noexcept(std::is_nothrow_move_constructible_v<T>) : base_t(std::move(value)) {}
	result(const error_t &error_value) : base_t(error_value) {}
	result(error_t &&error_value) noexcept(std::is_nothrow_move_constructible_v<error_t>) : base_t(std::move(error_value)) {}
	result(error_sentinel_t sentinel) : base_t(sentinel) {}

	using base_t::operator->;
	using base_t::operator T;
	using base_t::error;
	using base_t::has_error;
	using base_t::has_value;
	using base_t::require_error;
	using base_t::require_value;
	using base_t::value;

	result &operator=(const T &value) { this->assign_value(value); return *this; }
	result &operator=(T &&value) noexcept(std::is_nothrow_move_assignable_v<T>) { this->assign_value(std::move(value)); return *this; }
	result &operator=(const error_t &error_value) { this->assign_error(error_value); return *this; }
	result &operator=(error_t &&error_value) noexcept(std::is_nothrow_move_assignable_v<error_t>) { this->assign_error(std::move(error_value)); return *this; }
	result &operator=(error_sentinel_t sentinel) { this->assign_error(sentinel); return *this; }
};

} // namespace scpp
