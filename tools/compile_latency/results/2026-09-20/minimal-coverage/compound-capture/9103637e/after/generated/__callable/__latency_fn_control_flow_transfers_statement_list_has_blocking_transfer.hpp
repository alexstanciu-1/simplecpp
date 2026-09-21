#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
bool_t __latency_fn_control_flow_transfers_statement_list_has_blocking_transfer(shared_p<FrontendModel> model, int_t<std::uint32_t> firstStatementNodeId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters);
}
