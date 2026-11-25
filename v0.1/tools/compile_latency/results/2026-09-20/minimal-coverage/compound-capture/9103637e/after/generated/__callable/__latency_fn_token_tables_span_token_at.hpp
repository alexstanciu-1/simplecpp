#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TokenRow;
struct TokenRowSpan;
class TokenStream;
TokenRow __latency_fn_token_tables_span_token_at(shared_p<TokenStream> stream, TokenRowSpan span, int_t<> offset);
}
