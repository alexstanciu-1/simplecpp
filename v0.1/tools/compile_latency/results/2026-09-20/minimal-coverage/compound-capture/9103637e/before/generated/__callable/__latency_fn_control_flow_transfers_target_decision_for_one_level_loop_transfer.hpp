#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ControlTransferTargetDecision;
class FrontendModel;
class FrontendModelKernelCounters;
struct FrontendNodeRow;
shared_p<ControlTransferTargetDecision> __latency_fn_control_flow_transfers_target_decision_for_one_level_loop_transfer(shared_p<FrontendModel> model, FrontendNodeRow statementNode, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, int_t<std::uint16_t> loopDepth, shared_p<FrontendModelKernelCounters> counters);
}
