#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
void __latency_fn_control_flow_dataflows_append_post_branch_read(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, const vector_t<int_t<std::uint32_t>>& branchLocalNameIds, shared_p<FrontendModelKernelCounters> counters);
}
