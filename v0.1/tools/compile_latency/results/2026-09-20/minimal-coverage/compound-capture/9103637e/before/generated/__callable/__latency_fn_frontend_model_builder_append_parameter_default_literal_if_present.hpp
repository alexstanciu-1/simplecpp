#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
void __latency_fn_frontend_model_builder_append_parameter_default_literal_if_present(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, int_t<std::uint32_t> parameterNodeId, int_t<std::uint32_t> parameterTypeRefId, shared_p<FrontendModelKernelCounters> counters);
}
