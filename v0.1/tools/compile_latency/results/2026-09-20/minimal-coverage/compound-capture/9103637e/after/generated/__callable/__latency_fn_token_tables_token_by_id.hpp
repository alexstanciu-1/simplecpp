#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TokenRow;
class TokenStream;
TokenRow __latency_fn_token_tables_token_by_id(shared_p<TokenStream> stream, int_t<std::uint32_t> tokenId);
}
