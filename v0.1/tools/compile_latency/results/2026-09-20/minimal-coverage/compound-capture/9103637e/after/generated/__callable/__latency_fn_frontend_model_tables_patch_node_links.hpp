#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
bool_t __latency_fn_frontend_model_tables_patch_node_links(shared_p<FrontendModel> model, int_t<std::uint32_t> nodeId, int_t<std::uint32_t> parentNodeId, int_t<std::uint32_t> firstChildNodeId, int_t<std::uint32_t> nextSiblingNodeId, shared_p<FrontendModelKernelCounters> counters);
}
