#pragma once
#include <functional>
#include "scpp/int_t.hpp"
#include "scpp/string_t.hpp"

namespace callback_proof {
inline scpp::int_t<> apply(std::function<scpp::int_t<>(scpp::int_t<>)> fn, scpp::int_t<> value) { return fn(value); }
template <typename T, typename R> R transform(std::function<R(T)> fn, T value) { return fn(value); }
inline scpp::int_t<> choose(std::function<scpp::int_t<>(scpp::int_t<>)> fn, scpp::int_t<> value) { return fn(value); }
inline scpp::int_t<> choose(std::function<scpp::int_t<>(scpp::string_t)> fn, scpp::string_t value) { return fn(value); }
inline std::function<scpp::int_t<>()> retain(std::function<scpp::int_t<>()> fn) { return fn; }
inline void mutate(std::function<void(scpp::int_t<>&)> fn, scpp::int_t<> &value) { fn(value); }
}
