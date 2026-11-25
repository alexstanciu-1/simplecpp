#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentDirtyQueueRow.hpp"
#include "__types/ResidentFunctionBodyChangeRow.hpp"
#include "__types/ResidentFunctionBodyWorkDecisionRow.hpp"
#include "__types/ResidentPublicSurfaceIncrementalAcceptanceRow.hpp"
#include "__types/ResidentReverseDependencyIndexRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_dirty_public_surface_queue_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_block_row.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_block_row.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_dirty_bailout_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_function_body_change_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_reverse_index_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_reverse_lookup_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_work_decision_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_not_public_surface_change_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_publish_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_reverse_index_fallback_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_reverse_lookup_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_target_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_work_decision_mismatch_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_dirty_public_surface_queue_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_frontend_node_list_publish_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_function_body_change_by_symbol_change.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_path_kind_no_dependents_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_path_kind_with_dependents_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_total_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_row_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_selected_dependent_resolution_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_selected_source_reparse_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_selected_symbol_target_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_token_list_publish_count.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_work_decision_by_function_body_change.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_backend_refresh_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_local_lowering_id.hpp"
#include "__callable/__latency_fn_resident_recompute_targets_target_public_surface_publish_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependency_indexes_index_by_provider.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_append_row.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_public_surface_incremental_acceptance_dirty_public_surface_queue_count(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::dirty_public_surface_queue_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[26]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_dirty_queue_rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(change->owner_run_id)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(change->source_unit_id))) && php::identical(cast<int_t<>>(row->symbol_id), cast<int_t<>>(change->symbol_id))) && php::identical(cast<int_t<>>(row->reason_change_id), cast<int_t<>>(change->change_id))) && php::identical(cast<int_t<>>(row->dirty_reason_id), cast<int_t<>>(__latency_fn_resident_dirty_propagation_dirty_reason_symbol_public_surface_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
ResidentPublicSurfaceIncrementalAcceptanceRow __latency_fn_resident_public_surface_incremental_acceptance_block_row(ResidentPublicSurfaceIncrementalAcceptanceRow row, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::block_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[27]);
	row->status_id = __latency_fn_resident_public_surface_incremental_acceptance_status_blocked_id();
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
ResidentPublicSurfaceIncrementalAcceptanceRow __latency_fn_resident_public_surface_incremental_acceptance_row_from_symbol_change(shared_p<CompilerProjectRunReport> report, ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::row_from_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[28]);
	ResidentPublicSurfaceIncrementalAcceptanceRow row = ResidentPublicSurfaceIncrementalAcceptanceRow{};
	row->owner_run_id = change->owner_run_id;
	row->source_unit_id = change->source_unit_id;
	row->symbol_id = change->symbol_id;
	row->symbol_definition_change_id = change->change_id;
	row->status_id = __latency_fn_resident_public_surface_incremental_acceptance_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_none_id();
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_surface_changed_id()))))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_not_public_surface_change_id());
	}
	ResidentFunctionBodyChangeRow bodyChange = __latency_fn_resident_public_surface_incremental_acceptance_function_body_change_by_symbol_change(report, change);
	row->function_body_change_id = bodyChange->change_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(bodyChange->change_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_function_body_change_id());
	}
	ResidentFunctionBodyWorkDecisionRow decision = __latency_fn_resident_public_surface_incremental_acceptance_work_decision_by_function_body_change(report, bodyChange);
	row->function_body_work_decision_id = decision->decision_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(decision->decision_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_work_decision_id());
	}
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(decision->work_decision_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id())) || php::not_identical(cast<int_t<>>(decision->status_id), cast<int_t<>>(__latency_fn_resident_function_body_work_decisions_status_ready_id()))))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_work_decision_mismatch_id());
	}
	ResidentReverseDependencyIndexRow index = __latency_fn_resident_reverse_dependency_indexes_index_by_provider(report, change->owner_run_id, change->symbol_id);
	row->reverse_index_id = index->index_id;
	if (static_cast<bool>(php::identical(cast<int_t<>>(index->index_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_reverse_index_id());
	}
	row->reverse_lookup_hit_count = __latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_count(report, change, __latency_fn_resident_reverse_dependencies_status_dependent_found_id());
	row->reverse_lookup_no_dependent_count = __latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_count(report, change, __latency_fn_resident_reverse_dependencies_status_no_dependents_id());
	row->reverse_lookup_count = __latency_fn_resident_public_surface_incremental_acceptance_reverse_lookup_total_count(report, change);
	row->reverse_index_lookup_count = php::ternary_eval([&]() -> decltype(auto) { return (cast<int_t<>>(row->reverse_lookup_count) > static_cast<int_t<> >(0)); }, [&]() -> decltype(auto) { return __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(static_cast<int_t<> >(1)); }, [&]() -> decltype(auto) { return __latency_fn_structure_row_ids_none_id(); });
	row->reverse_index_hit_count = row->reverse_lookup_hit_count;
	row->reverse_index_no_dependent_count = row->reverse_lookup_no_dependent_count;
	row->reverse_index_fallback_scan_count = report->resident_reverse_dependency_index_fallback_scan_count;
	row->path_kind_id = __latency_fn_resident_public_surface_incremental_acceptance_path_kind_no_dependents_id();
	if (static_cast<bool>((cast<int_t<>>(row->reverse_lookup_hit_count) > static_cast<int_t<> >(0)))) {
		row->path_kind_id = __latency_fn_resident_public_surface_incremental_acceptance_path_kind_with_dependents_id();
	}
	row->selected_source_reparse_count = __latency_fn_resident_public_surface_incremental_acceptance_selected_source_reparse_count(report, change);
	row->selected_public_surface_publish_count = __latency_fn_resident_public_surface_incremental_acceptance_selected_symbol_target_count(report, change, __latency_fn_resident_recompute_targets_target_public_surface_publish_id());
	row->selected_dependent_resolution_count = __latency_fn_resident_public_surface_incremental_acceptance_selected_dependent_resolution_count(report, change);
	row->selected_local_lowering_count = __latency_fn_resident_public_surface_incremental_acceptance_selected_symbol_target_count(report, change, __latency_fn_resident_recompute_targets_target_local_lowering_id());
	row->selected_backend_refresh_count = __latency_fn_resident_public_surface_incremental_acceptance_selected_symbol_target_count(report, change, __latency_fn_resident_recompute_targets_target_backend_refresh_id());
	row->token_list_publish_count = __latency_fn_resident_public_surface_incremental_acceptance_token_list_publish_count(report, change);
	row->frontend_node_list_publish_count = __latency_fn_resident_public_surface_incremental_acceptance_frontend_node_list_publish_count(report, change);
	row->dirty_public_surface_queue_count = __latency_fn_resident_public_surface_incremental_acceptance_dirty_public_surface_queue_count(report, change);
	row->dirty_budget_count = report->resident_dirty_budget_count;
	row->dirty_bailout_count = report->resident_dirty_bailout_count;
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->reverse_lookup_count), static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_missing_reverse_lookup_id());
	}
	if (static_cast<bool>((cast<int_t<>>(row->reverse_index_fallback_scan_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_reverse_index_fallback_id());
	}
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(row->reverse_lookup_count), (cast<int_t<>>(row->reverse_lookup_hit_count) + cast<int_t<>>(row->reverse_lookup_no_dependent_count))) || (cast<int_t<>>(row->reverse_lookup_no_dependent_count) > static_cast<int_t<> >(1))) || ((cast<int_t<>>(row->reverse_lookup_hit_count) > static_cast<int_t<> >(0)) && (cast<int_t<>>(row->reverse_lookup_no_dependent_count) > static_cast<int_t<> >(0)))))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_reverse_lookup_mismatch_id());
	}
	if (static_cast<bool>(((((php::not_identical(cast<int_t<>>(row->selected_source_reparse_count), static_cast<int_t<> >(1)) || php::not_identical(cast<int_t<>>(row->selected_public_surface_publish_count), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(row->selected_dependent_resolution_count), cast<int_t<>>(row->reverse_lookup_hit_count))) || php::not_identical(cast<int_t<>>(row->selected_local_lowering_count), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(row->selected_backend_refresh_count), static_cast<int_t<> >(0))))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_target_mismatch_id());
	}
	if (static_cast<bool>(((php::not_identical(cast<int_t<>>(row->token_list_publish_count), static_cast<int_t<> >(1)) || php::not_identical(cast<int_t<>>(row->frontend_node_list_publish_count), static_cast<int_t<> >(1))) || php::not_identical(cast<int_t<>>(row->dirty_public_surface_queue_count), static_cast<int_t<> >(1))))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_publish_mismatch_id());
	}
	if (static_cast<bool>((cast<int_t<>>(row->dirty_bailout_count) > static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_public_surface_incremental_acceptance_block_row(row, __latency_fn_resident_public_surface_incremental_acceptance_blocked_reason_dirty_bailout_id());
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
void __latency_fn_resident_public_surface_incremental_acceptance_append_row(shared_p<CompilerProjectRunReport>& report, ResidentPublicSurfaceIncrementalAcceptanceRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[29]);
	row->acceptance_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_public_surface_incremental_acceptances));
	(void) report->resident_public_surface_incremental_acceptances.append(row);
	report->resident_public_surface_incremental_acceptance_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(php::count(report->resident_public_surface_incremental_acceptances));
	report->resident_public_surface_incremental_acceptance_reverse_index_lookup_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_reverse_index_lookup_count) + cast<int_t<>>(row->reverse_index_lookup_count)));
	report->resident_public_surface_incremental_acceptance_reverse_index_hit_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_reverse_index_hit_count) + cast<int_t<>>(row->reverse_index_hit_count)));
	report->resident_public_surface_incremental_acceptance_reverse_index_no_dependent_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_reverse_index_no_dependent_count) + cast<int_t<>>(row->reverse_index_no_dependent_count)));
	report->resident_public_surface_incremental_acceptance_fallback_scan_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_fallback_scan_count) + cast<int_t<>>(row->reverse_index_fallback_scan_count)));
	report->resident_public_surface_incremental_acceptance_source_reparse_target_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_source_reparse_target_count) + cast<int_t<>>(row->selected_source_reparse_count)));
	report->resident_public_surface_incremental_acceptance_public_surface_publish_target_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_public_surface_publish_target_count) + cast<int_t<>>(row->selected_public_surface_publish_count)));
	report->resident_public_surface_incremental_acceptance_dependent_resolution_target_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_dependent_resolution_target_count) + cast<int_t<>>(row->selected_dependent_resolution_count)));
	report->resident_public_surface_incremental_acceptance_local_lowering_target_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_local_lowering_target_count) + cast<int_t<>>(row->selected_local_lowering_count)));
	report->resident_public_surface_incremental_acceptance_backend_refresh_target_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_backend_refresh_target_count) + cast<int_t<>>(row->selected_backend_refresh_count)));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_public_surface_incremental_acceptance_status_ready_id())))) {
		report->resident_public_surface_incremental_acceptance_ready_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->resident_public_surface_incremental_acceptance_blocked_count = __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((cast<int_t<>>(report->resident_public_surface_incremental_acceptance_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_public_surface_incremental_acceptance[]; }
namespace scpp {
void __latency_fn_resident_public_surface_incremental_acceptance_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_public_surface_incremental_acceptance::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_public_surface_incremental_acceptance.phs", __latency_lines_resident_public_surface_incremental_acceptance[30]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int(rowCount), __latency_fn_resident_public_surface_incremental_acceptance_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentPublicSurfaceIncrementalAcceptanceRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}
