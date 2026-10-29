#pragma once
// Adapted from runtime/include/modules/datetime at 46d3ebf31.
// Native provider boundary; generated concrete adapters own public value transport.
#include <array>
#include <cstdint>
#include <string_view>
#include <string>

namespace scpp::detail::datetime {
std::int64_t now_unix_seconds();
std::int64_t now_unix_millis();
std::int64_t monotonic_millis();
std::uint64_t monotonic_micros();
std::uint64_t monotonic_nanos();
void sleep_millis(std::int64_t millis);
std::array<char, 20> format_iso_utc(std::int64_t unix_seconds);
struct parse_result { std::int64_t value = 0; const char* error = nullptr; };
parse_result parse_iso_utc(std::string_view text) noexcept;
std::string format_local(std::string_view pattern, std::int64_t seconds);
std::string format_local_now(std::string_view pattern);
parse_result parse_common_local(std::string_view text) noexcept;
}
