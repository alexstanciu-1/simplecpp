#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_binary_expression_from_nodes_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> leftNodeId, int_t<std::uint32_t> rightNodeId, int_t<std::uint32_t> inferredTypeRefId, int_t<std::uint16_t> operatorId, shared_p<FrontendModelKernelCounters> counters);
}
