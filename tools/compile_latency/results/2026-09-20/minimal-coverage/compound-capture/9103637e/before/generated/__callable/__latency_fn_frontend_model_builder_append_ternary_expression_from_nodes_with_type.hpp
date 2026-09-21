#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_ternary_expression_from_nodes_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> conditionNodeId, int_t<std::uint32_t> thenNodeId, int_t<std::uint32_t> elseNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters);
}
