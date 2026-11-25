#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentTokenListPublishRow.hpp"
#include "__types/ResidentTokenListSnapshotRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_token_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_token_lists_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_token_lists_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_token_lists_snapshot_from_publish_lookup.hpp"
#include "__callable/__latency_fn_resident_token_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_token_lists_retained_generation_bytes.hpp"
#include "__callable/__latency_fn_resident_token_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_segmented_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_token_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_for_source_change.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_token_lists_retained_generation_bytes.hpp"
#include "__callable/__latency_fn_resident_token_lists_should_publish_for_source_change.hpp"
#include "__callable/__latency_fn_resident_token_lists_snapshot_from_publish_lookup.hpp"
#include "__callable/__latency_fn_resident_token_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_cleanup_status_ok_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_list_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_publish_status_ok_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_for_source_change.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_token_lists_append_publish_rows_for_owner_changes.hpp"
#include "__callable/__latency_fn_resident_token_lists_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_token_lists_max_source_unit_id_from_publishable_changes.hpp"
namespace scpp { extern const int __latency_lines_resident_token_lists[]; }
namespace scpp {
void __latency_fn_resident_token_lists_append_publish_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_token_lists::append_publish_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_token_lists.phs", __latency_lines_resident_token_lists[10]);
	report->resident_token_list_publish_lookup_slot_count = __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(report->resident_token_list_publish_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_token_lists[]; }
namespace scpp {
ResidentTokenListSnapshotRow __latency_fn_resident_token_lists_snapshot_from_publish_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_token_lists::snapshot_from_publish_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_token_lists.phs", __latency_lines_resident_token_lists[11]);
	metricsReport->resident_token_list_publish_lookup_probe_count = __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(metricsReport->resident_token_list_publish_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentTokenListSnapshotRow empty = ResidentTokenListSnapshotRow{};
			return empty;
		}
		ResidentTokenListSnapshotRow snapshot = __latency_fn_resident_token_lists_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	metricsReport->resident_token_list_publish_lookup_fallback_scan_count = __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(metricsReport->resident_token_list_publish_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_token_lists_snapshot_by_owner_and_source(snapshotReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(sourceUnitId));
}

}

namespace scpp { extern const int __latency_lines_resident_token_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_token_lists_retained_generation_bytes(ResidentTokenListSnapshotRow snapshot) {
	SCPP_CALL_DEPTH_GUARD("resident_token_lists::retained_generation_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_token_lists.phs", __latency_lines_resident_token_lists[12]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->snapshot_id), static_cast<int_t<> >(0)))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->storage_kind_id), cast<int_t<>>(__latency_fn_row_segment_policy_storage_segmented_id())))) {
		return snapshot->reserved_segment_bytes;
	}
	return __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(snapshot->row_count) * static_cast<int_t<> >(sizeof(TokenRow))));
}

}

namespace scpp { extern const int __latency_lines_resident_token_lists[]; }
namespace scpp {
void __latency_fn_resident_token_lists_append_publish_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> publishedRowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_token_lists::append_publish_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_token_lists.phs", __latency_lines_resident_token_lists[13]);
	if (static_cast<bool>((publishedRowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_token_lists_uint32_from_int(publishedRowCount), __latency_fn_resident_token_lists_uint32_from_int((publishedRowCount * static_cast<int_t<> >(sizeof(ResidentTokenListPublishRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_token_lists[]; }
namespace scpp {
void __latency_fn_resident_token_lists_append_publish_for_source_change(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, ResidentSourceUnitChangeRow change, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, vector_t<int_t<std::uint32_t>>& currentSnapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_token_lists::append_publish_for_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_token_lists.phs", __latency_lines_resident_token_lists[14]);
	if (static_cast<bool>((!__latency_fn_resident_token_lists_should_publish_for_source_change(change)))) {
		return;
	}
	ResidentTokenListSnapshotRow previous = __latency_fn_resident_token_lists_snapshot_from_publish_lookup(report, previousReport, previousSnapshotIds, change->owner_run_id, change->source_unit_id);
	ResidentTokenListSnapshotRow current = __latency_fn_resident_token_lists_snapshot_from_publish_lookup(report, report, currentSnapshotIds, change->owner_run_id, change->source_unit_id);
	if (static_cast<bool>((php::identical(cast<int_t<>>(current->snapshot_id), static_cast<int_t<> >(0)) && php::not_identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_source_change_deleted_id()))))) {
		return;
	}
	int_t<std::uint32_t> previousListId = required_cast<int_t<std::uint32_t>>(previous->list_id);
	int_t<std::uint32_t> previousGenerationId = required_cast<int_t<std::uint32_t>>(previous->generation_id);
	int_t<std::uint32_t> publishedListId = required_cast<int_t<std::uint32_t>>(__latency_fn_row_segment_policy_initial_list_id());
	int_t<std::uint32_t> publishedGenerationId = required_cast<int_t<std::uint32_t>>(__latency_fn_row_segment_policy_initial_generation_id());
	if (static_cast<bool>((cast<int_t<>>(previousListId) > static_cast<int_t<> >(0)))) {
		publishedListId = __latency_fn_row_segment_policy_next_list_id(previousListId);
	}
	if (static_cast<bool>((cast<int_t<>>(previousGenerationId) > static_cast<int_t<> >(0)))) {
		publishedGenerationId = __latency_fn_row_segment_policy_next_generation_id(previousGenerationId);
	}
	int_t<std::uint32_t> retainedBytes = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_token_lists_retained_generation_bytes(previous));
	ResidentTokenListPublishRow row = ResidentTokenListPublishRow{};
	row->publish_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_token_list_publishes));
	row->owner_run_id = change->owner_run_id;
	row->source_unit_id = change->source_unit_id;
	row->reason_change_id = change->change_id;
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = current->snapshot_id;
	row->previous_list_id = previousListId;
	row->published_list_id = publishedListId;
	row->previous_generation_id = previousGenerationId;
	row->published_generation_id = publishedGenerationId;
	row->previous_row_count = previous->row_count;
	row->published_row_count = current->row_count;
	row->previous_segment_count = previous->segment_count;
	row->published_segment_count = current->segment_count;
	row->retained_old_generation_bytes = retainedBytes;
	row->cleanup_released_bytes = retainedBytes;
	row->publish_status_id = __latency_fn_row_segment_policy_publish_status_ok_id();
	row->cleanup_status_id = __latency_fn_row_segment_policy_cleanup_status_ok_id();
	(void) report->resident_token_list_publishes.append(row);
	report->resident_token_list_publish_count = __latency_fn_resident_token_lists_uint32_from_int(php::count(report->resident_token_list_publishes));
	report->resident_token_list_cleanup_count = __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(report->resident_token_list_cleanup_count) + static_cast<int_t<> >(1)));
	report->resident_token_list_retained_old_generation_bytes = __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(report->resident_token_list_retained_old_generation_bytes) + cast<int_t<>>(row->retained_old_generation_bytes)));
	report->resident_token_list_cleanup_released_bytes = __latency_fn_resident_token_lists_uint32_from_int((cast<int_t<>>(report->resident_token_list_cleanup_released_bytes) + cast<int_t<>>(row->cleanup_released_bytes)));
	__latency_fn_resident_token_lists_append_publish_memory_estimate(report, static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_token_lists[]; }
namespace scpp {
void __latency_fn_resident_token_lists_append_publish_rows_for_owner_changes(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_token_lists::append_publish_rows_for_owner_changes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_token_lists.phs", __latency_lines_resident_token_lists[15]);
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_token_lists_max_source_unit_id_from_publishable_changes(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> currentSnapshotIds = {};
	__latency_fn_resident_token_lists_build_snapshot_lookup_ids(previousReport, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousSnapshotIds);
	__latency_fn_resident_token_lists_build_snapshot_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, currentSnapshotIds);
	__latency_fn_resident_token_lists_append_publish_lookup_metrics(report, (slotCount * static_cast<int_t<> >(2)));
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			__latency_fn_resident_token_lists_append_publish_for_source_change(report, previousReport, change, previousSnapshotIds, currentSnapshotIds);
		}
	}
}

}
