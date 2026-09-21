#include <scpp/lang/php.hpp>
#include "__types/DebugProfileStageTimingSummary.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_compact_stage_timing.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_result_tsv_header.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_row_count_tsv_header.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_stage_timing_tsv_header.hpp"
namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
DebugProfileStageTimingSummary __latency_fn_debug_profile_gate_results_compact_stage_timing(int_t<std::uint32_t> ownerGateId, int_t<std::uint32_t> stageId, int_t<std::uint32_t> inputCount, int_t<std::uint32_t> outputCount, int_t<std::uint32_t> elapsedUs, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::compact_stage_timing", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[12]);
	DebugProfileStageTimingSummary row = DebugProfileStageTimingSummary{};
	row->owner_gate_id = ownerGateId;
	row->stage_id = stageId;
	row->input_count = inputCount;
	row->output_count = outputCount;
	row->elapsed_us = elapsedUs;
	row->status_id = statusId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
string_t __latency_fn_debug_profile_gate_results_result_tsv_header() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::result_tsv_header", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[13]);
	return string_t("row_id\tgate_id\ttask_id\tsample_id\tsource_unit_id\tsource_range_id\tassertion_count\taccepted_row_count\tblocked_row_count\tdiagnostic_count\tstatus_id\tblocked_reason_id");
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
string_t __latency_fn_debug_profile_gate_results_row_count_tsv_header() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::row_count_tsv_header", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[14]);
	return string_t("summary_id\towner_gate_id\towner_task_id\tsource_unit_id\trow_family_id\trow_count\taccepted_count\tblocked_count\tskipped_count\tstatus_id");
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
string_t __latency_fn_debug_profile_gate_results_stage_timing_tsv_header() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::stage_timing_tsv_header", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[15]);
	return string_t("summary_id\towner_gate_id\tstage_id\tinput_count\toutput_count\telapsed_us\tstatus_id");
}

}
