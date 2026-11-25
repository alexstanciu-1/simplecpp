#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowDataflowArtifact;
void __latency_fn_control_flow_dataflows_append_row(shared_p<ControlFlowDataflowArtifact>& artifact, int_t<std::uint16_t> rowKindId, int_t<std::uint16_t> branchKindId, int_t<std::uint16_t> statusId, int_t<std::uint16_t> diagnosticId, int_t<std::uint32_t> statementNodeId, int_t<std::uint32_t> localNameId, int_t<std::uint32_t> localSourceRowId, int_t<std::uint32_t> typeRefId);
}
