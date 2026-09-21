#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CompilerProfileEventRow;
struct DebugProfileStageTimingSummary;
DebugProfileStageTimingSummary __latency_fn_gate_profile_helpers_from_profile_event(int_t<std::uint32_t> ownerGateId, CompilerProfileEventRow event);
}
