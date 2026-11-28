#include "output_provider.hpp"

#include <charconv>
#include <cstdio>
#include <stdexcept>
#include <system_error>

namespace scpp {
void echo_bytes(const char* data, ::size_t length)
{
    if (std::fwrite(data, 1, length, stdout) != length) {
        throw std::runtime_error("echo: stdout write failed");
    }
}

#ifdef SCPP_OUTPUT_HAS_STRING
void echo_string(const string& value)
{
    echo_bytes(value.data(), static_cast<::size_t>(value.size()));
}
#endif

template<class T>
static void echo_number(T value)
{
    char buffer[64];
    const auto result = std::to_chars(buffer, buffer + sizeof(buffer), value);
    if (result.ec != std::errc{}) {
        throw std::runtime_error("echo: number formatting failed");
    }
    echo_bytes(buffer, static_cast<::size_t>(result.ptr - buffer));
}

void echo_int(::int64_t value) { echo_number(value); }
void echo_uint(::uint64_t value) { echo_number(value); }
void echo_float(double value) { echo_number(value); }
void echo_php_bool(bool value) { if (value) echo_bytes("1", 1); }
}
