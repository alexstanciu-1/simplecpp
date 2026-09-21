#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PhsParserCursor;
class TokenStream;
bool_t __latency_fn_parser_cursor_at(const string_t& source, shared_p<TokenStream> tokens, PhsParserCursor cursor, int_t<std::uint16_t> kindId, const string_t& literal);
}
