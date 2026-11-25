#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlFlowGraphArtifact;
struct FrontendNodeRow;
struct FrontendStatementPayloadRow;
void __latency_fn_control_flow_graphs_append_if_statement_blocks(shared_p<ControlFlowGraphArtifact>& artifact, FrontendNodeRow statementNode, FrontendStatementPayloadRow statement, int_t<std::uint32_t> entryBlockId, int_t<std::uint32_t> exitBlockId);
}
