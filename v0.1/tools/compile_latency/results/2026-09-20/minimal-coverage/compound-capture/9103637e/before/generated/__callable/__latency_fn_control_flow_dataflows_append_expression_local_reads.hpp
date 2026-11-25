#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
class FrontendModel;
class FrontendModelKernelCounters;
void __latency_fn_control_flow_dataflows_append_expression_local_reads(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, int_t<std::uint32_t> expressionNodeId, int_t<std::uint32_t> statementNodeId, int_t<std::uint16_t> rowKindId, int_t<std::uint16_t> branchKindId, shared_p<FrontendModelKernelCounters> counters);
}
