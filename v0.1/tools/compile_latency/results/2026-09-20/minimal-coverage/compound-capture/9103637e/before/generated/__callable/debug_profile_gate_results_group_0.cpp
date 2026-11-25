#include <scpp/lang/php.hpp>
#include "__types/DebugProfileGateResultArtifact.hpp"
#include "__types/DebugProfileGateResultRow.hpp"
#include "__types/DebugProfileRowCountSummary.hpp"
#include "__types/debug_profile_gate_results.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_kind_gate_result_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_key_gate_result_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_passed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_failed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_skipped_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_kind_gate_result_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_kind_name.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_key_gate_result_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_key_name.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_blocked_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_failed_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_name.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_passed_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_status_skipped_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_key_gate_result_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_artifact_kind_gate_result_id.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_new_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_compact_result_row.hpp"
#include "__callable/__latency_fn_debug_profile_gate_results_compact_row_count.hpp"
namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
bool_t debug_profile_gate_results::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == debug_profile_gate_results::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_debug_profile_gate_results_artifact_kind_gate_result_id() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::artifact_kind_gate_result_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_debug_profile_gate_results_artifact_key_gate_result_id() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::artifact_key_gate_result_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_debug_profile_gate_results_status_passed_id() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::status_passed_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_debug_profile_gate_results_status_failed_id() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::status_failed_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_debug_profile_gate_results_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_debug_profile_gate_results_status_skipped_id() {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::status_skipped_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
string_t __latency_fn_debug_profile_gate_results_artifact_kind_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::artifact_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[6]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_artifact_kind_gate_result_id())))) {
		return string_t("v2_debug_profile_gate_result_artifact");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
string_t __latency_fn_debug_profile_gate_results_artifact_key_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::artifact_key_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[7]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_artifact_key_gate_result_id())))) {
		return string_t("debug_profile_gate_result:v1");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
string_t __latency_fn_debug_profile_gate_results_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[8]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_status_passed_id())))) {
		return string_t("passed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_status_failed_id())))) {
		return string_t("failed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_status_blocked_id())))) {
		return string_t("blocked");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_debug_profile_gate_results_status_skipped_id())))) {
		return string_t("skipped");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
DebugProfileGateResultArtifact __latency_fn_debug_profile_gate_results_new_artifact(int_t<std::uint32_t> gateId, int_t<std::uint32_t> milestoneId, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[9]);
	DebugProfileGateResultArtifact artifact = DebugProfileGateResultArtifact{};
	artifact->artifact_kind_id = __latency_fn_debug_profile_gate_results_artifact_kind_gate_result_id();
	artifact->artifact_key_id = __latency_fn_debug_profile_gate_results_artifact_key_gate_result_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->gate_id = gateId;
	artifact->milestone_id = milestoneId;
	artifact->status_id = statusId;
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
DebugProfileGateResultRow __latency_fn_debug_profile_gate_results_compact_result_row(int_t<std::uint32_t> gateId, int_t<std::uint32_t> taskId, int_t<std::uint32_t> sampleId, int_t<std::uint16_t> statusId, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::compact_result_row", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[10]);
	DebugProfileGateResultRow row = DebugProfileGateResultRow{};
	row->gate_id = gateId;
	row->task_id = taskId;
	row->sample_id = sampleId;
	row->status_id = statusId;
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_debug_profile_gate_results[]; }
namespace scpp {
DebugProfileRowCountSummary __latency_fn_debug_profile_gate_results_compact_row_count(int_t<std::uint32_t> ownerGateId, int_t<std::uint32_t> ownerTaskId, int_t<std::uint32_t> rowFamilyId, int_t<std::uint32_t> rowCount, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("debug_profile_gate_results::compact_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/support/debug_profile_gate_results.phs", __latency_lines_debug_profile_gate_results[11]);
	DebugProfileRowCountSummary row = DebugProfileRowCountSummary{};
	row->owner_gate_id = ownerGateId;
	row->owner_task_id = ownerTaskId;
	row->row_family_id = rowFamilyId;
	row->row_count = rowCount;
	row->status_id = statusId;
	return row;
}

}
