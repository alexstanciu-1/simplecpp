#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PhsParserState;
struct SourceUnitTableRow;
class TokenStream;
shared_p<PhsParserState> __latency_fn_parser_state_make(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sourceBufferId, shared_p<TokenStream> tokens);
}
