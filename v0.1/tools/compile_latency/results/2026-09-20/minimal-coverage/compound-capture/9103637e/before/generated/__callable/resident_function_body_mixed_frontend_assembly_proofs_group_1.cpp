#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyMixedFrontendAssemblyProofRow.hpp"
#include "__types/ResidentFunctionBodyRowListPublishResultRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_mixed_frontend_assembly_proofs.hpp"
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_snapshot_node_count.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_segmented_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_replacement_only_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_empty_assembled_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_current_frontend_list_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_publish_result_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_no_publish_result_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_publish_result_by_decision.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_row_from_source_unit.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_snapshot_node_count.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_snapshot_node_count(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> currentSnapshotId, int_t<std::uint32_t> previousSnapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::snapshot_node_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[14]);
	if (static_cast<bool>((cast<int_t<>>(currentSnapshotId) > static_cast<int_t<> >(0)))) {
		ResidentFunctionBodySnapshotRow current = __latency_fn_resident_function_body_work_decisions_snapshot_by_id(report, currentSnapshotId);
		if (static_cast<bool>((cast<int_t<>>(current->snapshot_id) > static_cast<int_t<> >(0)))) {
			return current->body_node_count;
		}
	}
	if (static_cast<bool>((cast<int_t<>>(previousSnapshotId) > static_cast<int_t<> >(0)))) {
		ResidentFunctionBodySnapshotRow previous = __latency_fn_resident_function_body_work_decisions_snapshot_by_id(report, previousSnapshotId);
		if (static_cast<bool>((cast<int_t<>>(previous->snapshot_id) > static_cast<int_t<> >(0)))) {
			return previous->body_node_count;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
ResidentFunctionBodyMixedFrontendAssemblyProofRow __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_row_from_source_unit(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::row_from_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[15]);
	ResidentFunctionBodyMixedFrontendAssemblyProofRow row = ResidentFunctionBodyMixedFrontendAssemblyProofRow{};
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnitId;
	row->assembly_kind_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_replacement_only_id();
	row->status_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_none_id();
	int_t<> requiredPublishResultCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> missingPublishResultCount = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_work_decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::not_identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(ownerRunId)) || php::not_identical(cast<int_t<>>(decision->source_unit_id), cast<int_t<>>(sourceUnitId))) || php::not_identical(cast<int_t<>>(decision->status_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_status_ready_id()))))) {
			continue;
		}
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->current_frontend_state_id), static_cast<int_t<> >(0)) && (cast<int_t<>>(decision->current_frontend_state_id) > static_cast<int_t<> >(0))))) {
			row->current_frontend_state_id = decision->current_frontend_state_id;
		}
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->current_frontend_node_list_snapshot_id), static_cast<int_t<> >(0)) && (cast<int_t<>>(decision->current_frontend_node_list_snapshot_id) > static_cast<int_t<> >(0))))) {
			row->current_frontend_node_list_snapshot_id = decision->current_frontend_node_list_snapshot_id;
		}
		if (static_cast<bool>(php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id())))) {
			row->reused_body_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->reused_body_count) + static_cast<int_t<> >(1)));
			row->reused_body_node_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->reused_body_node_count) + cast<int_t<>>(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_snapshot_node_count(report, decision->current_function_body_snapshot_id, decision->previous_function_body_snapshot_id))));
		}
		else {
			if (static_cast<bool>((php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id())) || php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id()))))) {
				requiredPublishResultCount = (requiredPublishResultCount + static_cast<int_t<> >(1));
				ResidentFunctionBodyRowListPublishResultRow result = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_publish_result_by_decision(report, cast<int_t<std::uint32_t>>(ownerRunId), decision->decision_id);
				if (static_cast<bool>(php::identical(cast<int_t<>>(result->publish_result_id), static_cast<int_t<> >(0)))) {
					missingPublishResultCount = (missingPublishResultCount + static_cast<int_t<> >(1));
				}
				else {
					row->source_unit_publish_result_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->source_unit_publish_result_count) + static_cast<int_t<> >(1)));
					row->replacement_body_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->replacement_body_count) + static_cast<int_t<> >(1)));
					row->replacement_body_node_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->replacement_body_node_count) + cast<int_t<>>(result->published_body_node_count)));
					row->published_segment_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->published_segment_count) + cast<int_t<>>(result->published_segment_count)));
					if (static_cast<bool>(php::identical(cast<int_t<>>(decision->body_row_action_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id())))) {
						row->build_new_body_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->build_new_body_count) + static_cast<int_t<> >(1)));
					}
					if (static_cast<bool>(php::identical(cast<int_t<>>(result->storage_kind_id), cast<int_t<>>(__latency_fn_frontend_node_lists_storage_segmented_id())))) {
						row->published_segmented_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->published_segmented_count) + static_cast<int_t<> >(1)));
					}
				}
			}
		}
	}
	row->assembled_body_node_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(row->reused_body_node_count) + cast<int_t<>>(row->replacement_body_node_count)));
	if (static_cast<bool>((missingPublishResultCount > static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id();
		row->blocked_reason_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_publish_result_id();
		row->assembly_kind_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id();
	}
	else {
		if (static_cast<bool>((php::identical(requiredPublishResultCount, static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(row->source_unit_publish_result_count), static_cast<int_t<> >(0))))) {
			row->status_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id();
			row->blocked_reason_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_no_publish_result_id();
			row->assembly_kind_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id();
		}
		else {
			if (static_cast<bool>(php::identical(cast<int_t<>>(row->current_frontend_node_list_snapshot_id), static_cast<int_t<> >(0)))) {
				row->status_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id();
				row->blocked_reason_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_missing_current_frontend_list_id();
				row->assembly_kind_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id();
			}
			else {
				if (static_cast<bool>(php::identical(cast<int_t<>>(row->assembled_body_node_count), static_cast<int_t<> >(0)))) {
					row->status_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_blocked_id();
					row->blocked_reason_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_blocked_reason_empty_assembled_body_id();
					row->assembly_kind_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_blocked_id();
				}
				else {
					if (static_cast<bool>(((cast<int_t<>>(row->reused_body_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(row->replacement_body_count) > static_cast<int_t<> >(0))))) {
						row->assembly_kind_id = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_mixed_reuse_and_replacement_id();
					}
				}
			}
		}
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_proof(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyMixedFrontendAssemblyProofRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::append_proof", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[16]);
	row->assembly_proof_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_mixed_frontend_assembly_proofs));
	(void) report->resident_function_body_mixed_frontend_assembly_proofs.append(row);
	report->resident_function_body_mixed_frontend_assembly_proof_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int(php::count(report->resident_function_body_mixed_frontend_assembly_proofs));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_status_ready_id())))) {
		report->resident_function_body_mixed_frontend_assembly_proof_ready_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_proof_ready_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_mixed_frontend_assembly_source_unit_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_source_unit_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_mixed_frontend_assembly_reused_body_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_reused_body_count) + cast<int_t<>>(row->reused_body_count)));
		report->resident_function_body_mixed_frontend_assembly_replacement_body_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_replacement_body_count) + cast<int_t<>>(row->replacement_body_count)));
		report->resident_function_body_mixed_frontend_assembly_build_new_body_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_build_new_body_count) + cast<int_t<>>(row->build_new_body_count)));
		report->resident_function_body_mixed_frontend_assembly_reused_body_node_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_reused_body_node_count) + cast<int_t<>>(row->reused_body_node_count)));
		report->resident_function_body_mixed_frontend_assembly_replacement_body_node_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_replacement_body_node_count) + cast<int_t<>>(row->replacement_body_node_count)));
		report->resident_function_body_mixed_frontend_assembly_assembled_body_node_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_assembled_body_node_count) + cast<int_t<>>(row->assembled_body_node_count)));
		report->resident_function_body_mixed_frontend_assembly_published_segment_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_published_segment_count) + cast<int_t<>>(row->published_segment_count)));
		report->resident_function_body_mixed_frontend_assembly_published_segmented_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_published_segmented_count) + cast<int_t<>>(row->published_segmented_count)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->assembly_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_mixed_frontend_assembly_proofs_assembly_kind_mixed_reuse_and_replacement_id())))) {
			report->resident_function_body_mixed_frontend_assembly_mixed_source_unit_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_mixed_source_unit_count) + static_cast<int_t<> >(1)));
		}
	}
	else {
		report->resident_function_body_mixed_frontend_assembly_proof_blocked_count = __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((cast<int_t<>>(report->resident_function_body_mixed_frontend_assembly_proof_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[]; }
namespace scpp {
void __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_mixed_frontend_assembly_proofs::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_mixed_frontend_assembly_proofs.phs", __latency_lines_resident_function_body_mixed_frontend_assembly_proofs[17]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int(rowCount), __latency_fn_resident_function_body_mixed_frontend_assembly_proofs_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyMixedFrontendAssemblyProofRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}
