#include <scpp/lang/php.hpp>
#include "__types/DebugProfileGateResultRow.hpp"
#include "__types/DebugProfileRowCountSummary.hpp"
#include "__types/gate_result_inspection_helpers.hpp"
#include "__callable/__latency_fn_gate_result_inspection_helpers_row_count_has_expected_total.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_blocked_id.hpp"
#include "__callable/__latency_fn_gate_result_inspection_helpers_result_row_is_blocked.hpp"
#include "__callable/__latency_fn_gate_result_inspection_helpers_result_row_has_source_anchor.hpp"
#include "__callable/__latency_fn_gate_result_inspection_helpers_row_count_summary_debug_string.hpp"
namespace scpp { extern const int __latency_lines_gate_result_inspection_helpers[]; }
namespace scpp {
bool_t gate_result_inspection_helpers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == gate_result_inspection_helpers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_gate_result_inspection_helpers[]; }
namespace scpp {
bool_t __latency_fn_gate_result_inspection_helpers_row_count_has_expected_total(DebugProfileRowCountSummary summary, int_t<std::uint32_t> expected) {
	SCPP_CALL_DEPTH_GUARD("gate_result_inspection_helpers::row_count_has_expected_total", "/tmp/scpp-edit-latency-20260919/app/inspection/gate_result_inspection_helpers.phs", __latency_lines_gate_result_inspection_helpers[0]);
	return bool_t(php::identical(cast<int_t<>>(summary->row_count), cast<int_t<>>(expected)));
}

}

namespace scpp { extern const int __latency_lines_gate_result_inspection_helpers[]; }
namespace scpp {
bool_t __latency_fn_gate_result_inspection_helpers_result_row_is_blocked(DebugProfileGateResultRow row) {
	SCPP_CALL_DEPTH_GUARD("gate_result_inspection_helpers::result_row_is_blocked", "/tmp/scpp-edit-latency-20260919/app/inspection/gate_result_inspection_helpers.phs", __latency_lines_gate_result_inspection_helpers[1]);
	return bool_t(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_status_blocked_id())));
}

}

namespace scpp { extern const int __latency_lines_gate_result_inspection_helpers[]; }
namespace scpp {
bool_t __latency_fn_gate_result_inspection_helpers_result_row_has_source_anchor(DebugProfileGateResultRow row) {
	SCPP_CALL_DEPTH_GUARD("gate_result_inspection_helpers::result_row_has_source_anchor", "/tmp/scpp-edit-latency-20260919/app/inspection/gate_result_inspection_helpers.phs", __latency_lines_gate_result_inspection_helpers[2]);
	return (php::not_identical(cast<int_t<>>(row->source_unit_id), static_cast<int_t<> >(0)) && php::not_identical(cast<int_t<>>(row->source_range_id), static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_gate_result_inspection_helpers[]; }
namespace scpp {
string_t __latency_fn_gate_result_inspection_helpers_row_count_summary_debug_string(DebugProfileRowCountSummary summary) {
	SCPP_CALL_DEPTH_GUARD("gate_result_inspection_helpers::row_count_summary_debug_string", "/tmp/scpp-edit-latency-20260919/app/inspection/gate_result_inspection_helpers.phs", __latency_lines_gate_result_inspection_helpers[3]);
	return (string_t("debug_profile_row_count:") + cast<string_t>(cast<int_t<>>(summary->owner_gate_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(summary->row_family_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(summary->row_count)));
}

}
