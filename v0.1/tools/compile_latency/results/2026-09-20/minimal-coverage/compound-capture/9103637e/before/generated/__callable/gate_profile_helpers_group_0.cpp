#include <scpp/lang/php.hpp>
#include "__types/CompilerProfileEventRow.hpp"
#include "__types/DebugProfileStageTimingSummary.hpp"
#include "__types/gate_profile_helpers.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_gate_profile_helpers_elapsed_or_zero.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_compact_stage_timing.hpp"
#include "__callable/__latency_fn_gate_profile_helpers_from_profile_event.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_gate_profile_helpers_timing_summary_debug_string.hpp"
namespace scpp { extern const int __latency_lines_gate_profile_helpers[]; }
namespace scpp {
bool_t gate_profile_helpers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == gate_profile_helpers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_gate_profile_helpers[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_gate_profile_helpers_elapsed_or_zero(int_t<std::uint64_t> startedUs) {
	SCPP_CALL_DEPTH_GUARD("gate_profile_helpers::elapsed_or_zero", "/tmp/scpp-edit-latency-20260919/app/profile/gate_profile_helpers.phs", __latency_lines_gate_profile_helpers[0]);
	return __latency_fn_compiler_profile_events_elapsed_us_since(startedUs);
}

}

namespace scpp { extern const int __latency_lines_gate_profile_helpers[]; }
namespace scpp {
DebugProfileStageTimingSummary __latency_fn_gate_profile_helpers_from_profile_event(int_t<std::uint32_t> ownerGateId, CompilerProfileEventRow event) {
	SCPP_CALL_DEPTH_GUARD("gate_profile_helpers::from_profile_event", "/tmp/scpp-edit-latency-20260919/app/profile/gate_profile_helpers.phs", __latency_lines_gate_profile_helpers[1]);
	return __latency_fn_debug_profile_gate_results_compact_stage_timing(ownerGateId, __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(event->stage_id)), event->row_count, event->row_count, event->elapsed_us, event->status_id);
}

}

namespace scpp { extern const int __latency_lines_gate_profile_helpers[]; }
namespace scpp {
string_t __latency_fn_gate_profile_helpers_timing_summary_debug_string(DebugProfileStageTimingSummary summary) {
	SCPP_CALL_DEPTH_GUARD("gate_profile_helpers::timing_summary_debug_string", "/tmp/scpp-edit-latency-20260919/app/profile/gate_profile_helpers.phs", __latency_lines_gate_profile_helpers[2]);
	return (string_t("debug_profile_stage_timing:") + cast<string_t>(cast<int_t<>>(summary->owner_gate_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(summary->stage_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(summary->elapsed_us)));
}

}
