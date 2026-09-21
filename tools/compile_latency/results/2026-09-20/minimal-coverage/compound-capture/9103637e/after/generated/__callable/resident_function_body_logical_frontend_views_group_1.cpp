#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/ResidentFunctionBodySourceUnitRepointProofRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_logical_frontend_views.hpp"
#include "__types/resident_function_body_source_unit_repoint_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_view.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_kind_mixed_reuse_and_replacement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_max_source_unit_id_from_ready_views.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_build_view_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_lookup_slot_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_from_source_unit_repoint_proofs_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_lookup_slot_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_append_view.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_build_view_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_has_ready_repoint_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_max_source_unit_id_from_ready_views.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_row_from_repoint_proof.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_view_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_views_append_view(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodyLogicalFrontendViewRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::append_view", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[13]);
	row->view_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_function_body_logical_frontend_views));
	(void) report->resident_function_body_logical_frontend_views.append(row);
	report->resident_function_body_logical_frontend_view_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int(php::count(report->resident_function_body_logical_frontend_views));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id())))) {
		report->resident_function_body_logical_frontend_view_ready_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_ready_count) + static_cast<int_t<> >(1)));
		report->resident_function_body_logical_frontend_view_source_unit_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_source_unit_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->view_kind_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_view_kind_mixed_reuse_and_replacement_id())))) {
			report->resident_function_body_logical_frontend_view_mixed_source_unit_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_mixed_source_unit_count) + static_cast<int_t<> >(1)));
		}
		report->resident_function_body_logical_frontend_view_reused_body_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_reused_body_count) + cast<int_t<>>(row->reused_body_count)));
		report->resident_function_body_logical_frontend_view_replacement_body_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_replacement_body_count) + cast<int_t<>>(row->replacement_body_count)));
		report->resident_function_body_logical_frontend_view_build_new_body_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_build_new_body_count) + cast<int_t<>>(row->build_new_body_count)));
		report->resident_function_body_logical_frontend_view_logical_source_unit_row_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_logical_source_unit_row_count) + cast<int_t<>>(row->logical_source_unit_row_count)));
		report->resident_function_body_logical_frontend_view_stable_non_body_node_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_stable_non_body_node_count) + cast<int_t<>>(row->stable_non_body_node_count)));
		report->resident_function_body_logical_frontend_view_retained_body_node_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_retained_body_node_count) + cast<int_t<>>(row->retained_body_node_count)));
		report->resident_function_body_logical_frontend_view_repointed_body_node_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_repointed_body_node_count) + cast<int_t<>>(row->repointed_body_node_count)));
		report->resident_function_body_logical_frontend_view_logical_body_node_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_logical_body_node_count) + cast<int_t<>>(row->logical_body_node_count)));
		report->resident_function_body_logical_frontend_view_current_full_source_rebuild_row_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_current_full_source_rebuild_row_count) + cast<int_t<>>(row->current_full_source_rebuild_row_count)));
		report->resident_function_body_logical_frontend_view_cleanup_released_body_row_bytes = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_cleanup_released_body_row_bytes) + cast<int_t<>>(row->cleanup_released_body_row_bytes)));
		report->resident_function_body_logical_frontend_view_published_segment_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_published_segment_count) + cast<int_t<>>(row->published_segment_count)));
		report->resident_function_body_logical_frontend_view_published_segmented_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_published_segmented_count) + cast<int_t<>>(row->published_segmented_count)));
	}
	else {
		report->resident_function_body_logical_frontend_view_blocked_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_blocked_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendViewRow __latency_fn_resident_function_body_logical_frontend_views_view_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> viewId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::view_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[14]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(viewId, php::count(report->resident_function_body_logical_frontend_views))))) {
		ResidentFunctionBodyLogicalFrontendViewRow row = report->resident_function_body_logical_frontend_views[__latency_fn_structure_row_ids_dense_index(viewId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->view_id), cast<int_t<>>(viewId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->view_id), cast<int_t<>>(viewId)))) {
			return row;
		}
	}
	ResidentFunctionBodyLogicalFrontendViewRow empty = ResidentFunctionBodyLogicalFrontendViewRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_logical_frontend_views_max_source_unit_id_from_ready_views(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::max_source_unit_id_from_ready_views", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[15]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))) && (cast<int_t<>>(row->source_unit_id) > maxId)))) {
			maxId = cast<int_t<>>(row->source_unit_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_views_build_view_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& viewIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::build_view_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[16]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(viewIds, slotCount);
	auto __latency_local_0 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))) && __latency_fn_structure_row_ids_has_dense_id(row->source_unit_id, slotCount)))) {
			viewIds.at(__latency_fn_structure_row_ids_dense_index(row->source_unit_id)) = row->view_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_views_append_lookup_slot_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::append_lookup_slot_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[17]);
	report->resident_function_body_logical_frontend_view_lookup_slot_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_view_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendViewRow __latency_fn_resident_function_body_logical_frontend_views_view_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> viewReport, vector_t<int_t<std::uint32_t>>& viewIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::view_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[18]);
	metricsReport->resident_function_body_logical_frontend_view_lookup_probe_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_view_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(viewIds))))) {
		int_t<std::uint32_t> viewId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(viewIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>((cast<int_t<>>(viewId) > static_cast<int_t<> >(0)))) {
			ResidentFunctionBodyLogicalFrontendViewRow row = __latency_fn_resident_function_body_logical_frontend_views_view_by_id(viewReport, cast<int_t<std::uint32_t>>(viewId));
			if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
				metricsReport->resident_function_body_logical_frontend_view_lookup_resolved_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_view_lookup_resolved_count) + static_cast<int_t<> >(1)));
				return row;
			}
		}
	}
	metricsReport->resident_function_body_logical_frontend_view_lookup_fallback_scan_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_view_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	auto __latency_local_0 = viewReport->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
			metricsReport->resident_function_body_logical_frontend_view_lookup_resolved_count = __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_view_lookup_resolved_count) + static_cast<int_t<> >(1)));
			return row;
		}
	}
	ResidentFunctionBodyLogicalFrontendViewRow empty = ResidentFunctionBodyLogicalFrontendViewRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_views_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[19]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int(rowCount), __latency_fn_resident_function_body_logical_frontend_views_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyLogicalFrontendViewRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_views[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_views_append_from_source_unit_repoint_proofs_if_needed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_views::append_from_source_unit_repoint_proofs_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_views.phs", __latency_lines_resident_function_body_logical_frontend_views[20]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_logical_frontend_views_has_ready_repoint_proofs(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startCount = required_cast<int_t<>>(php::count(report->resident_function_body_logical_frontend_views));
	auto __latency_local_0 = report->resident_function_body_source_unit_repoint_proofs;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto proof = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(proof->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(proof->status_id), cast<int_t<>>(__latency_fn_resident_function_body_source_unit_repoint_proofs_status_ready_id()))))) {
			__latency_fn_resident_function_body_logical_frontend_views_append_view(report, __latency_fn_resident_function_body_logical_frontend_views_row_from_repoint_proof(report, proof));
		}
	}
	__latency_fn_resident_function_body_logical_frontend_views_append_memory_estimate(report, (php::count(report->resident_function_body_logical_frontend_views) - startCount));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_max_source_unit_id_from_ready_views(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<int_t<std::uint32_t>> viewIds = {};
	__latency_fn_resident_function_body_logical_frontend_views_build_view_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, viewIds);
	__latency_fn_resident_function_body_logical_frontend_views_append_lookup_slot_metrics(report, slotCount);
	auto __latency_local_2 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto view = __latency_local_3.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(view->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(view->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
			__latency_fn_resident_function_body_logical_frontend_views_view_from_lookup(report, report, viewIds, cast<int_t<std::uint32_t>>(ownerRunId), view->source_unit_id);
		}
	}
}

}
