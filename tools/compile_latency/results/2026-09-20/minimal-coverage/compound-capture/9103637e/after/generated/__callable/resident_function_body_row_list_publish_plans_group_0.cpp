#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/resident_function_body_row_list_publish_plans.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_remap_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_work_decision_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_snapshot_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_source_unit_node_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_empty_replacement_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_owner_identity_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_publish_plan_kind_body_owned_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_deferred_after_run_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_has_stable_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_work_decision_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
bool_t resident_function_body_row_list_publish_plans::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_row_list_publish_plans::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[3]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_remap_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_remap_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_work_decision_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_missing_work_decision_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_snapshot_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_missing_current_snapshot_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_source_unit_node_list_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_missing_current_source_unit_node_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_empty_replacement_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_empty_replacement_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_owner_identity_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::blocked_reason_missing_owner_identity_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_publish_plan_kind_body_owned_replacement_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::publish_plan_kind_body_owned_replacement_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::cleanup_plan_kind_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[11]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_deferred_after_run_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::cleanup_plan_kind_deferred_after_run_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_row_list_publish_plans_has_stable_remap_proofs(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::has_stable_remap_proofs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[13]);
	auto __latency_local_0 = report->resident_function_body_stable_node_remap_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto proof = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(proof->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
ResidentFunctionBodyWorkDecisionRow __latency_fn_resident_function_body_row_list_publish_plans_work_decision_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> decisionId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::work_decision_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[14]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(decisionId, php::count(report->resident_function_body_work_decisions))))) {
		ResidentFunctionBodyWorkDecisionRow decision = report->resident_function_body_work_decisions[__latency_fn_structure_row_ids_dense_index(decisionId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(decision->decision_id), cast<int_t<>>(decisionId)))) {
			return decision;
		}
	}
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(decision->decision_id), cast<int_t<>>(decisionId)))) {
			return decision;
		}
	}
	ResidentFunctionBodyWorkDecisionRow empty = ResidentFunctionBodyWorkDecisionRow{};
	return empty;
}

}
