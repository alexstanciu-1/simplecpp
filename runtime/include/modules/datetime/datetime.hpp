#pragma once

#include "scpp/int_t.hpp"
#include "scpp/result.hpp"
#include "scpp/string_t.hpp"

namespace scpp::dt {

[[nodiscard]] int_t<> now_unix_seconds();
[[nodiscard]] int_t<> now_unix_millis();
[[nodiscard]] int_t<> monotonic_millis();
void sleep_millis(const int_t<> &millis);

[[nodiscard]] string_t format_iso_utc(const int_t<> &unix_seconds);
[[nodiscard]] result<int_t<>> parse_iso_utc(const string_t &value);
[[nodiscard]] string_t format_local(const string_t &format, const int_t<> &unix_seconds);
[[nodiscard]] string_t format_local_now(const string_t &format);
[[nodiscard]] result<int_t<>> parse_common_local(const string_t &value);

} // namespace scpp::dt
