#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PhsParserState;
struct TokenRow;
TokenRow __latency_fn_parser_state_expect(shared_p<PhsParserState> state, const string_t& source, int_t<std::uint16_t> kindId, const string_t& literal);
}
