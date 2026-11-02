#pragma once
#include <stddef.h>
#include <stdint.h>

namespace scpp {
// Compiler-facing output boundary. Bytes are borrowed for the duration of the call.
// Writes exact bytes with no separator/newline; integers use locale-independent decimal.
// Failures may throw. Formatting and IO implementation dependencies remain private.
void echo_bytes(const char* data, ::size_t length);
void echo_int(::int64_t value);
}
