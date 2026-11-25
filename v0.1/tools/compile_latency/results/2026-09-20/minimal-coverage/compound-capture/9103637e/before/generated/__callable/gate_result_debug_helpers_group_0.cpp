#include <scpp/lang/php.hpp>
#include "__types/DebugProfileGateResultArtifact.hpp"
#include "__types/DebugProfileGateResultRow.hpp"
#include "__types/gate_result_debug_helpers.hpp"
#include "__callable/__latency_fn_gate_result_debug_helpers_debug_mode_gate_enabled.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_name.hpp"
#include "__callable/__latency_fn_gate_result_debug_helpers_artifact_debug_string.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_name.hpp"
#include "__callable/__latency_fn_gate_result_debug_helpers_result_row_debug_string.hpp"
#include "__callable/__latency_fn_gate_result_debug_helpers_no_semantic_authority_notice.hpp"
namespace scpp { extern const int __latency_lines_gate_result_debug_helpers[]; }
namespace scpp {
bool_t gate_result_debug_helpers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == gate_result_debug_helpers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_gate_result_debug_helpers[]; }
namespace scpp {
bool_t __latency_fn_gate_result_debug_helpers_debug_mode_gate_enabled(bool_t debugMode, const string_t& gateName) {
	SCPP_CALL_DEPTH_GUARD("gate_result_debug_helpers::debug_mode_gate_enabled", "/tmp/scpp-edit-latency-20260919/app/debug/gate_result_debug_helpers.phs", __latency_lines_gate_result_debug_helpers[0]);
	return (debugMode && php::not_identical(gateName, string_t("")));
}

}

namespace scpp { extern const int __latency_lines_gate_result_debug_helpers[]; }
namespace scpp {
string_t __latency_fn_gate_result_debug_helpers_artifact_debug_string(DebugProfileGateResultArtifact artifact) {
	SCPP_CALL_DEPTH_GUARD("gate_result_debug_helpers::artifact_debug_string", "/tmp/scpp-edit-latency-20260919/app/debug/gate_result_debug_helpers.phs", __latency_lines_gate_result_debug_helpers[1]);
	return (string_t("debug_profile_gate_result:") + cast<string_t>(cast<int_t<>>(artifact->gate_id)) + string_t(":") + cast<string_t>(__latency_fn_debug_profile_gate_results_status_name(artifact->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->sample_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(artifact->blocked_row_count)));
}

}

namespace scpp { extern const int __latency_lines_gate_result_debug_helpers[]; }
namespace scpp {
string_t __latency_fn_gate_result_debug_helpers_result_row_debug_string(DebugProfileGateResultRow row) {
	SCPP_CALL_DEPTH_GUARD("gate_result_debug_helpers::result_row_debug_string", "/tmp/scpp-edit-latency-20260919/app/debug/gate_result_debug_helpers.phs", __latency_lines_gate_result_debug_helpers[2]);
	return (string_t("debug_profile_gate_result_row:") + cast<string_t>(cast<int_t<>>(row->gate_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->task_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->sample_id)) + string_t(":") + cast<string_t>(__latency_fn_debug_profile_gate_results_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_gate_result_debug_helpers[]; }
namespace scpp {
string_t __latency_fn_gate_result_debug_helpers_no_semantic_authority_notice() {
	SCPP_CALL_DEPTH_GUARD("gate_result_debug_helpers::no_semantic_authority_notice", "/tmp/scpp-edit-latency-20260919/app/debug/gate_result_debug_helpers.phs", __latency_lines_gate_result_debug_helpers[3]);
	return string_t("debug/profile helpers observe rows and render artifacts; they do not accept or reject source behavior");
}

}
