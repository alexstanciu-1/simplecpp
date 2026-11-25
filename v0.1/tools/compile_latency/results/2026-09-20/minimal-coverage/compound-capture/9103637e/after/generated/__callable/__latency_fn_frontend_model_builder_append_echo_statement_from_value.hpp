#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_echo_statement_from_value(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> valueNodeId, int_t<std::uint32_t> sourceRangeId, shared_p<FrontendModelKernelCounters> counters);
}
