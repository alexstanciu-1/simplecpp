#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class FrontendModel;
class FrontendModelKernelCounters;
int_t<std::uint32_t> __latency_fn_control_flow_transfers_resolved_target_for_ready_transfer(shared_p<FrontendModel> model, int_t<std::uint16_t> transferKindId, int_t<std::uint32_t> enclosingLoopNodeId, shared_p<FrontendModelKernelCounters> counters);
}
