#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PhsParserCursor;
struct TokenRow;
class TokenStream;
TokenRow __latency_fn_parser_cursor_current(shared_p<TokenStream> tokens, PhsParserCursor cursor);
}
