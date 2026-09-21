#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_frontend_model_builder_mark_expression_node_flag(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, int_t<std::uint16_t> flagId, shared_p<FrontendModelKernelCounters> counters);
}
