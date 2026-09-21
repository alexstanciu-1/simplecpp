#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_frontend_model_builder_retag_expression_node_type(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, int_t<std::uint32_t> typeRefId, shared_p<FrontendModelKernelCounters> counters);
}
