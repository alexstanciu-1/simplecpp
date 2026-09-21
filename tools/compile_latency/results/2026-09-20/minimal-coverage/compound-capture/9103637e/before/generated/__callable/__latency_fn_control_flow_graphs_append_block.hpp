#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowGraphArtifact;
int_t<std::uint32_t> __latency_fn_control_flow_graphs_append_block(shared_p<ControlFlowGraphArtifact>& artifact, int_t<std::uint16_t> blockKindId, int_t<std::uint16_t> statementKindId, int_t<std::uint32_t> statementNodeId, int_t<std::uint32_t> parentBlockId, int_t<std::uint32_t> firstSourceRowId, int_t<std::uint32_t> lastSourceRowId, int_t<std::uint16_t> loopDepth);
}
