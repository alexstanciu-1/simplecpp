#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
class PhsParserState;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_parse_use_function_declaration(shared_p<PhsParserState> state, const string_t& source, shared_p<FrontendModel> model, shared_p<FrontendModelKernelCounters> counters);
}
