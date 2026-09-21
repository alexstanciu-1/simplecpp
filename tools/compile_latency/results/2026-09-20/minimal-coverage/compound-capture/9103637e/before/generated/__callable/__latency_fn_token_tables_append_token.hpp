#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TokenRow;
class TokenStream;
int_t<std::uint32_t> __latency_fn_token_tables_append_token(shared_p<TokenStream> stream, TokenRow row);
}
