#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishPlanRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyStableNodeRemapProofRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_body_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_frontend_node_list_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_planned_body_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_previous_body_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_planned_body_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_generation_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_body_row_bytes.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_empty_replacement_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_snapshot_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_source_unit_node_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_owner_identity_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_work_decision_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_remap_not_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_body_row_bytes.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_body_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_deferred_after_run_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_frontend_node_list_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_planned_body_generation_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_planned_body_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_previous_body_generation_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_publish_plan_kind_body_owned_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_row_from_stable_remap_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_row_list_publish_plans_work_decision_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_row_list_publish_plans_body_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::body_snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[15]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_function_body_snapshots))))) {
		ResidentFunctionBodySnapshotRow snapshot = report->resident_function_body_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return snapshot;
		}
	}
	auto __latency_local_0 = report->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return snapshot;
		}
	}
	ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_function_body_row_list_publish_plans_frontend_node_list_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::frontend_node_list_snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[16]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_frontend_node_list_snapshots))))) {
		ResidentFrontendNodeListSnapshotRow snapshot = report->resident_frontend_node_list_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return snapshot;
		}
	}
	auto __latency_local_0 = report->resident_frontend_node_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return snapshot;
		}
	}
	ResidentFrontendNodeListSnapshotRow empty = ResidentFrontendNodeListSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_row_list_publish_plans_planned_body_list_id(int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::planned_body_list_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[17]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(symbolId), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	return cast<int_t<std::uint32_t>>(symbolId);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_row_list_publish_plans_previous_body_generation_id(ResidentFunctionBodySnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::previous_body_generation_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[18]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	return __latency_fn_row_segment_policy_initial_generation_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_row_list_publish_plans_planned_body_generation_id(ResidentFunctionBodySnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::planned_body_generation_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[19]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_row_segment_policy_initial_generation_id();
	}
	return __latency_fn_row_segment_policy_next_generation_id(__latency_fn_row_segment_policy_initial_generation_id());
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_row_list_publish_plans_body_row_bytes(int_t<std::uint32_t> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::body_row_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[20]);
	return __latency_fn_resident_function_body_row_list_publish_plans_uint32_from_int((cast<int_t<>>(rowCount) * static_cast<int_t<> >(sizeof(FrontendNodeRow))));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_row_list_publish_plans[]; }
namespace scpp {
ResidentFunctionBodyRowListPublishPlanRow __latency_fn_resident_function_body_row_list_publish_plans_row_from_stable_remap_proof(shared_p<CompilerProjectRunReport> report, shared_p<CompilerProjectRunReport> previous, ResidentFunctionBodyStableNodeRemapProofRow remap) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_row_list_publish_plans::row_from_stable_remap_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_row_list_publish_plans.phs", __latency_lines_resident_function_body_row_list_publish_plans[21]);
	ResidentFunctionBodyWorkDecisionRow decision = __latency_fn_resident_function_body_row_list_publish_plans_work_decision_by_id(report, remap->function_body_work_decision_id);
	ResidentFunctionBodySnapshotRow currentSnapshot = ResidentFunctionBodySnapshotRow{};
	ResidentFunctionBodySnapshotRow previousSnapshot = ResidentFunctionBodySnapshotRow{};
	ResidentFunctionBodyRowListPublishPlanRow row = ResidentFunctionBodyRowListPublishPlanRow{};
	row->owner_run_id = remap->owner_run_id;
	row->stable_node_remap_proof_id = remap->remap_proof_id;
	row->publish_repoint_preflight_id = remap->publish_repoint_preflight_id;
	row->function_body_work_decision_id = remap->function_body_work_decision_id;
	row->current_function_body_snapshot_id = remap->current_function_body_snapshot_id;
	row->source_unit_id = remap->source_unit_id;
	row->symbol_id = remap->symbol_id;
	row->replacement_body_node_count = remap->local_node_ordinal_count;
	row->future_stable_node_id_start = remap->future_stable_node_id_start;
	row->future_stable_node_id_count = remap->future_stable_node_id_count;
	row->planned_body_list_id = __latency_fn_resident_function_body_row_list_publish_plans_planned_body_list_id(remap->symbol_id);
	row->planned_body_generation_id = __latency_fn_row_segment_policy_initial_generation_id();
	row->publish_plan_kind_id = __latency_fn_resident_function_body_row_list_publish_plans_publish_plan_kind_body_owned_replacement_id();
	row->cleanup_plan_kind_id = __latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_none_id();
	row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_none_id();
	if (static_cast<bool>((cast<int_t<>>(decision->decision_id) > static_cast<int_t<> >(0)))) {
		row->function_body_work_decision_id = decision->decision_id;
		row->previous_function_body_snapshot_id = decision->previous_function_body_snapshot_id;
		row->current_function_body_snapshot_id = decision->current_function_body_snapshot_id;
		row->source_unit_id = decision->source_unit_id;
		row->symbol_id = decision->symbol_id;
		row->current_frontend_state_id = decision->current_frontend_state_id;
		row->current_source_unit_frontend_node_list_snapshot_id = decision->current_frontend_node_list_snapshot_id;
		row->planned_body_list_id = __latency_fn_resident_function_body_row_list_publish_plans_planned_body_list_id(decision->symbol_id);
	}
	if (static_cast<bool>((cast<int_t<>>(row->current_function_body_snapshot_id) > static_cast<int_t<> >(0)))) {
		currentSnapshot = __latency_fn_resident_function_body_row_list_publish_plans_body_snapshot_by_id(report, row->current_function_body_snapshot_id);
		if (static_cast<bool>((cast<int_t<>>(currentSnapshot->snapshot_id) > static_cast<int_t<> >(0)))) {
			row->current_frontend_state_id = currentSnapshot->frontend_state_id;
			row->current_source_unit_frontend_node_list_snapshot_id = currentSnapshot->frontend_node_list_snapshot_id;
		}
	}
	if (static_cast<bool>((cast<int_t<>>(row->previous_function_body_snapshot_id) > static_cast<int_t<> >(0)))) {
		previousSnapshot = __latency_fn_resident_function_body_row_list_publish_plans_body_snapshot_by_id(previous, row->previous_function_body_snapshot_id);
		row->previous_source_unit_frontend_node_list_snapshot_id = previousSnapshot->frontend_node_list_snapshot_id;
		row->previous_body_node_count = previousSnapshot->body_node_count;
		row->previous_body_list_id = __latency_fn_resident_function_body_row_list_publish_plans_planned_body_list_id(row->symbol_id);
		row->previous_body_generation_id = __latency_fn_resident_function_body_row_list_publish_plans_previous_body_generation_id(previousSnapshot);
		row->planned_body_generation_id = __latency_fn_resident_function_body_row_list_publish_plans_planned_body_generation_id(previousSnapshot);
		row->retained_old_body_row_bytes = __latency_fn_resident_function_body_row_list_publish_plans_body_row_bytes(previousSnapshot->body_node_count);
		row->planned_cleanup_released_body_row_bytes = row->retained_old_body_row_bytes;
		if (static_cast<bool>((cast<int_t<>>(row->planned_cleanup_released_body_row_bytes) > static_cast<int_t<> >(0)))) {
			row->cleanup_plan_kind_id = __latency_fn_resident_function_body_row_list_publish_plans_cleanup_plan_kind_deferred_after_run_id();
		}
	}
	ResidentFrontendNodeListSnapshotRow currentNodeList = __latency_fn_resident_function_body_row_list_publish_plans_frontend_node_list_snapshot_by_id(report, row->current_source_unit_frontend_node_list_snapshot_id);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(remap->status_id), cast<int_t<>>(__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id()))))) {
		row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_remap_not_ready_id();
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(decision->decision_id), static_cast<int_t<> >(0)))) {
			row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_work_decision_id();
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(currentSnapshot->snapshot_id), static_cast<int_t<> >(0)))) {
				row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id();
				row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_snapshot_id();
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(currentNodeList->snapshot_id), static_cast<int_t<> >(0)))) {
					row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id();
					row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_current_source_unit_node_list_id();
				}
				else {
					if (static_cast<bool>(php::identical(cast<int_t<>>(row->replacement_body_node_count), static_cast<int_t<> >(0)))) {
						row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id();
						row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_empty_replacement_body_id();
					}
					else {
						if (static_cast<bool>(((php::identical(cast<int_t<>>(row->source_unit_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(row->symbol_id), static_cast<int_t<> >(0))) || php::identical(cast<int_t<>>(row->planned_body_list_id), static_cast<int_t<> >(0))))) {
							row->status_id = __latency_fn_resident_function_body_row_list_publish_plans_status_blocked_id();
							row->blocked_reason_id = __latency_fn_resident_function_body_row_list_publish_plans_blocked_reason_missing_owner_identity_id();
						}
					}
				}
			}
		}
	}
	return row;
}

}
