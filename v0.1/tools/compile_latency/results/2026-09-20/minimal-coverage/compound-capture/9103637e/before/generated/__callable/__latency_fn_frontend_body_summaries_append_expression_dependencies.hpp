#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendBodySummaryArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
void __latency_fn_frontend_body_summaries_append_expression_dependencies(shared_p<FrontendBodySummaryArtifact>& artifact, shared_p<FrontendModel> model, int_t<std::uint32_t> scopeNodeId, int_t<std::uint32_t> parentStatementNodeId, FrontendNodeRow valueNode, shared_p<FrontendModelKernelCounters> counters);
}
