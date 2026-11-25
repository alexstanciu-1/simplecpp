#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
class ControlFlowLocalWriteSet;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
void __latency_fn_control_flow_dataflows_append_local_write_statement(shared_p<ControlFlowDataflowArtifact>& artifact, shared_p<FrontendModel> model, const string_t& sourceText, FrontendNodeRow statementNode, int_t<std::uint16_t> rowKindId, int_t<std::uint16_t> branchKindId, shared_p<ControlFlowLocalWriteSet>& writes, shared_p<FrontendModelKernelCounters> counters);
}
