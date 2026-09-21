#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_synthetic_literal_expression(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> sourceRangeId, int_t<std::uint32_t> typeRefId, int_t<> value, shared_p<FrontendModelKernelCounters> counters);
}
