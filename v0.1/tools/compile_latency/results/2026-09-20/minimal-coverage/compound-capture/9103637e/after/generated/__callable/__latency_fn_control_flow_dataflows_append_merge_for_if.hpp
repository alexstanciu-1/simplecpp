#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
class ControlFlowLocalWriteSet;
void __latency_fn_control_flow_dataflows_append_merge_for_if(shared_p<ControlFlowDataflowArtifact>& artifact, int_t<std::uint32_t> ifNodeId, shared_p<ControlFlowLocalWriteSet> thenWrites, shared_p<ControlFlowLocalWriteSet> elseWrites);
}
