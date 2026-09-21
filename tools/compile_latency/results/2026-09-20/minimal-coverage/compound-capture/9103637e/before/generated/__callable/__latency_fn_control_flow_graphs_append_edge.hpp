#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowGraphArtifact;
int_t<std::uint32_t> __latency_fn_control_flow_graphs_append_edge(shared_p<ControlFlowGraphArtifact>& artifact, int_t<std::uint32_t> fromBlockId, int_t<std::uint32_t> toBlockId, int_t<std::uint16_t> edgeKindId, int_t<std::uint32_t> sourceRowId);
}
