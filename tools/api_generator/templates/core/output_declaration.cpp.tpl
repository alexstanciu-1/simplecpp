namespace scpp {
// Compiler-facing output boundary. Bytes are borrowed for the duration of the call.
// Writes exact bytes with no separator/newline; integers use locale-independent decimal.
// Failures may throw. Formatting and IO implementation dependencies remain private.
void echo_bytes(const char* data, ::size_t length);
void echo_int(::int64_t value);
void echo_uint(::uint64_t value);
void echo_float(double value);
void echo_php_bool(bool value);
@string_output@}
