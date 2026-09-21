#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_frontend_model_builder_append_index_access_expression_from_nodes_with_type(shared_p<FrontendModel> model, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> subjectNodeId, int_t<std::uint32_t> indexNodeId, int_t<std::uint32_t> inferredTypeRefId, shared_p<FrontendModelKernelCounters> counters);
}
