#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
struct ProjectSymbolIndexRow;
void __latency_fn_control_flow_transfers_append_transfer_row__exec(string_t& text, int_t<std::uint32_t> rowId, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, FrontendNodeRow statementNode, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters);
}
