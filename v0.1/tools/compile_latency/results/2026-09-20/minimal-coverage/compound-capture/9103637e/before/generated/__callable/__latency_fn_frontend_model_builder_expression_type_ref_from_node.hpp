#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_expression_type_ref_from_node(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, shared_p<FrontendModelKernelCounters> counters);
}
