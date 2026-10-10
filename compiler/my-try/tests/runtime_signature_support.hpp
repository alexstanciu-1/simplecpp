#pragma once
#include "scpp/int_t.hpp"
#include "scpp/float_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"

namespace signature_proof {
// No native defaults: the compiler must explicitly materialize every argument.
inline scpp::int_t<> defaults(scpp::int_t<> n, scpp::string_t text, scpp::bool_t enabled, scpp::float_t scale) {
	return scpp::int_t<>{n.native_value() + static_cast<std::int64_t>(text.size())
		+ (enabled.native_value() ? 10 : 0) + static_cast<std::int64_t>(scale.native_value() * 2)};
}
template <typename T> T choose(T, T fallback) { return fallback; }
inline void touch(scpp::int_t<> &value) { value = scpp::int_t<>{11}; }
inline void touch(scpp::string_t &value) { value = scpp::string_t{"changed"}; }
template <typename T> void assign(T &value, T replacement) { value = replacement; }
template <typename T> T read_ref(const T &value) { return value; }
inline scpp::int_t<> pick(scpp::int_t<> n, scpp::int_t<> suffix) { return scpp::int_t<>{n.native_value() + suffix.native_value()}; }
inline scpp::int_t<> pick(scpp::string_t text) { return scpp::int_t<>{static_cast<std::int64_t>(text.size())}; }
inline scpp::int_t<> sum(scpp::int_t<> base, scpp::vector_t<scpp::int_t<>> values) {
	auto total = base.native_value();
	for (std::size_t index = 0; index < values.size(); ++index)
		total += values.at(scpp::int_t<>{static_cast<std::int64_t>(index)}).native_value();
	return scpp::int_t<>{total};
}
template <typename T> scpp::vector_t<T> gather(scpp::vector_t<T> values) { return values; }
template <typename T> scpp::vector_t<T> gather_after(T, scpp::vector_t<T> values) { return values; }
inline scpp::int_t<> measure(scpp::vector_t<scpp::int_t<>> values) { return sum(scpp::int_t<>{0}, values); }
inline scpp::int_t<> measure(scpp::vector_t<scpp::string_t> values) {
	std::int64_t total = 0;
	for (std::size_t index = 0; index < values.size(); ++index)
		total += values.at(scpp::int_t<>{static_cast<std::int64_t>(index)}).size();
	return scpp::int_t<>{total};
}
}
