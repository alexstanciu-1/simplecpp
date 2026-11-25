#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
void __latency_fn_control_flow_dataflows_append_merge_for_if(shared_p<ControlFlowDataflowArtifact>& artifact, int_t<std::uint32_t> ifNodeId, const vector_t<int_t<std::uint32_t>>& thenNameIds, const vector_t<int_t<std::uint32_t>>& thenSourceRowIds, const vector_t<int_t<std::uint32_t>>& thenTypeRefIds, const vector_t<int_t<std::uint32_t>>& elseNameIds, const vector_t<int_t<std::uint32_t>>& elseSourceRowIds, const vector_t<int_t<std::uint32_t>>& elseTypeRefIds);
}
