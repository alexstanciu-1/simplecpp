#pragma once

#include "scpp/result_core.hpp"

namespace scpp {

template <typename T>
class result_or_bool final : public detail::result_core<T, false, true> {
private:
	using base_t = detail::result_core<T, false, true>;

public:
	result_or_bool() = default;
	result_or_bool(false_sentinel_t sentinel) noexcept : base_t(sentinel) {}
	result_or_bool(null_t sentinel) noexcept : base_t(sentinel) {}
	result_or_bool(nullopt_t sentinel) noexcept : base_t(sentinel) {}
	result_or_bool(const bool_t &value) : base_t(value) {}
	result_or_bool(bool value) : base_t(value) {}
	result_or_bool(const T &value) : base_t(value) {}
	result_or_bool(T &&value) noexcept(std::is_nothrow_move_constructible_v<T>) : base_t(std::move(value)) {}

	using base_t::has_value;
	using base_t::is_false;
	using base_t::is_true;
	using base_t::native_state;
	using base_t::operator->;
	using base_t::operator T;
	using base_t::require_value;
	using base_t::reset;
	using base_t::value;

	result_or_bool &operator=(false_sentinel_t sentinel) noexcept { this->assign_false(sentinel); return *this; }
	result_or_bool &operator=(null_t sentinel) noexcept { this->assign_false(sentinel); return *this; }
	result_or_bool &operator=(nullopt_t sentinel) noexcept { this->assign_false(sentinel); return *this; }
	result_or_bool &operator=(const bool_t &value) { this->assign_bool(value); return *this; }
	result_or_bool &operator=(bool value) { this->assign_bool(value); return *this; }
	result_or_bool &operator=(const T &value) { this->assign_value(value); return *this; }
	result_or_bool &operator=(T &&value) noexcept(std::is_nothrow_move_assignable_v<T>) { this->assign_value(std::move(value)); return *this; }
};

} // namespace scpp
