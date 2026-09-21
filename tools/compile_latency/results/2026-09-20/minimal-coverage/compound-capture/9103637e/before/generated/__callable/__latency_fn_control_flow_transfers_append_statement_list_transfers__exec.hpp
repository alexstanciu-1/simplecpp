#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
struct ProjectSymbolIndexRow;
void __latency_fn_control_flow_transfers_append_statement_list_transfers__exec(string_t& text, int_t<std::uint32_t>& rowCount, ProjectSymbolIndexRow symbol, shared_p<FrontendModel> model, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters);
}
