#pragma once
#include "scpp/int_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/string_t.hpp"

namespace signature_proof {
// No native defaults: the compiler must explicitly materialize every argument.
inline scpp::int_t<> defaults(scpp::int_t<> n, scpp::string_t text, scpp::bool_t enabled, scpp::float_t scale) {
	return scpp::int_t<>{n.native_value() + static_cast<std::int64_t>(text.size())
		+ (enabled.native_value() ? 10 : 0) + static_cast<std::int64_t>(scale.native_value() * 2)};
}
template <typename T> T choose(T, T fallback) { return fallback; }
inline scpp::int_t<> pick(scpp::int_t<> n, scpp::int_t<> suffix) { return scpp::int_t<>{n.native_value() + suffix.native_value()}; }
inline scpp::int_t<> pick(scpp::string_t text) { return scpp::int_t<>{static_cast<std::int64_t>(text.size())}; }
}
