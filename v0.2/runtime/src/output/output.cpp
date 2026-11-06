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

void echo_int(::int64_t value)
{
    char buffer[32];
    const auto result = std::to_chars(buffer, buffer + sizeof(buffer), value);
    if (result.ec != std::errc{}) {
        throw std::runtime_error("echo: integer formatting failed");
    }
    echo_bytes(buffer, static_cast<::size_t>(result.ptr - buffer));
}
}
