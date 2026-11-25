#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct PhsParserCursor;
class TokenStream;
PhsParserCursor __latency_fn_parser_cursor_from_tokens(shared_p<TokenStream> tokens);
}
