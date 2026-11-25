#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
struct TokenRow;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_identifier_expression_from_token_with_type(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, TokenRow nameToken, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters);
}
