#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_control_transfer_statement(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, const string_t& keyword, int_t<std::uint16_t> statementKindId, shared_p<FrontendModelKernelCounters> counters);
}
