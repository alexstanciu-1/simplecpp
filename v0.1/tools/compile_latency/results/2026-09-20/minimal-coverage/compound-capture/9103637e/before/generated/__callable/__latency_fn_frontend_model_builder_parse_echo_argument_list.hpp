#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
void __latency_fn_frontend_model_builder_parse_echo_argument_list(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t>& firstArgumentNodeId, int_t<std::uint16_t>& argumentCount, shared_p<FrontendModelKernelCounters> counters);
}
