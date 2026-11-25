#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_deleted_source_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_deleted_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_cold_changes_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_deleted_source_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_changes_from_previous_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_blocked_dirty_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_ready_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_content_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_from_lookup.hpp"
namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_reserve_none_id_slots(vector_t<int_t<std::uint32_t>>& slotIds, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::reserve_none_id_slots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[29]);
	php::vector_reserve(slotIds, slotCount);
	while (static_cast<bool>((php::count(slotIds) < slotCount))) {
		{
		auto __latency_local_0 = __latency_fn_structure_row_ids_none_id();
		(void) slotIds.push_back(__latency_local_0);
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_source_snapshot_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_source_snapshot_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[30]);
	report->resident_source_snapshot_lookup_slot_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_source_snapshot_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::build_source_snapshot_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[31]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(snapshotIds, slotCount);
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(snapshot->source_unit_id, slotCount)))) {
			snapshotIds.at(__latency_fn_structure_row_ids_dense_index(snapshot->source_unit_id)) = snapshot->snapshot_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSourceUnitSnapshotRow __latency_fn_resident_definition_granularity_source_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::source_snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[32]);
	metricsReport->resident_source_snapshot_lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_snapshot_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentSourceUnitSnapshotRow empty = ResidentSourceUnitSnapshotRow{};
			return empty;
		}
		ResidentSourceUnitSnapshotRow snapshot = __latency_fn_resident_definition_granularity_source_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	metricsReport->resident_source_snapshot_lookup_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(metricsReport->resident_source_snapshot_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source(snapshotReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(sourceUnitId));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_source_change(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitSnapshotRow current, ResidentSourceUnitSnapshotRow previous, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyStatusId, int_t<std::uint16_t> reuseStatusId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[33]);
	ResidentSourceUnitChangeRow row = ResidentSourceUnitChangeRow{};
	row->change_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_source_unit_changes));
	row->owner_run_id = current->owner_run_id;
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = current->snapshot_id;
	row->source_unit_id = current->source_unit_id;
	row->previous_content_hash = previous->content_hash;
	row->current_content_hash = current->content_hash;
	row->previous_source_length = previous->source_length;
	row->current_source_length = current->source_length;
	row->change_kind_id = changeKindId;
	row->dirty_status_id = dirtyStatusId;
	row->reuse_status_id = reuseStatusId;
	(void) report->resident_source_unit_changes.append(row);
	report->resident_source_unit_change_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_source_unit_changes));
	if (static_cast<bool>(php::identical(cast<int_t<>>(reuseStatusId), cast<int_t<>>(__latency_fn_resident_definition_granularity_reuse_ready_id())))) {
		report->resident_source_unit_reuse_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_source_unit_reuse_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_deleted_source_change(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitSnapshotRow previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_deleted_source_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[34]);
	ResidentSourceUnitChangeRow row = ResidentSourceUnitChangeRow{};
	row->change_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_source_unit_changes));
	row->owner_run_id = ownerRunId;
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = __latency_fn_structure_row_ids_none_id();
	row->source_unit_id = previous->source_unit_id;
	row->previous_content_hash = previous->content_hash;
	row->current_content_hash = __latency_fn_structure_row_ids_none_id();
	row->previous_source_length = previous->source_length;
	row->current_source_length = __latency_fn_structure_row_ids_none_id();
	row->change_kind_id = __latency_fn_resident_definition_granularity_source_change_deleted_id();
	row->dirty_status_id = __latency_fn_resident_definition_granularity_dirty_source_unit_id();
	row->reuse_status_id = __latency_fn_resident_definition_granularity_reuse_none_id();
	(void) report->resident_source_unit_changes.append(row);
	report->resident_source_unit_change_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_source_unit_changes));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_source_cold_changes_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_source_cold_changes_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[35]);
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto current = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(current->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitSnapshotRow previous = ResidentSourceUnitSnapshotRow{};
			__latency_fn_resident_definition_granularity_append_source_change(report, current, previous, __latency_fn_resident_definition_granularity_source_change_new_id(), __latency_fn_resident_definition_granularity_dirty_new_id(), __latency_fn_resident_definition_granularity_reuse_none_id());
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_source_changes_from_previous_for_owner(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_source_changes_from_previous_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[36]);
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	int_t<> previousSlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner(previousReport, cast<int_t<std::uint32_t>>(ownerRunId)));
	if (static_cast<bool>((previousSlotCount > slotCount))) {
		slotCount = previousSlotCount;
	}
	vector_t<int_t<std::uint32_t>> currentSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, currentSnapshotIds);
	__latency_fn_resident_definition_granularity_build_source_snapshot_lookup_ids(previousReport, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousSnapshotIds);
	__latency_fn_resident_definition_granularity_append_source_snapshot_lookup_metrics(report, (slotCount * static_cast<int_t<> >(2)));
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto current = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(current->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitSnapshotRow previous = __latency_fn_resident_definition_granularity_source_snapshot_from_lookup(report, previousReport, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), current->source_unit_id);
			if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
				__latency_fn_resident_definition_granularity_append_source_change(report, current, previous, __latency_fn_resident_definition_granularity_source_change_new_id(), __latency_fn_resident_definition_granularity_dirty_new_id(), __latency_fn_resident_definition_granularity_reuse_none_id());
			}
			else {
				if (static_cast<bool>(((php::identical(cast<int_t<>>(previous->content_hash), cast<int_t<>>(current->content_hash)) && php::identical(cast<int_t<>>(previous->source_length), cast<int_t<>>(current->source_length))) && php::identical(cast<int_t<>>(previous->line_count), cast<int_t<>>(current->line_count))))) {
					__latency_fn_resident_definition_granularity_append_source_change(report, current, previous, __latency_fn_resident_definition_granularity_source_change_no_change_id(), __latency_fn_resident_definition_granularity_dirty_none_id(), __latency_fn_resident_definition_granularity_reuse_ready_id());
				}
				else {
					__latency_fn_resident_definition_granularity_append_source_change(report, current, previous, __latency_fn_resident_definition_granularity_source_change_content_changed_id(), __latency_fn_resident_definition_granularity_dirty_source_unit_id(), __latency_fn_resident_definition_granularity_reuse_blocked_dirty_id());
				}
			}
		}
	}
	auto __latency_local_2 = previousReport->resident_source_unit_snapshots;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto previous = __latency_local_3.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(previous->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSourceUnitSnapshotRow current = __latency_fn_resident_definition_granularity_source_snapshot_from_lookup(report, report, currentSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), previous->source_unit_id);
			if (static_cast<bool>(php::identical(cast<int_t<>>(current->snapshot_id), static_cast<int_t<> >(0)))) {
				__latency_fn_resident_definition_granularity_append_deleted_source_change(report, previous, cast<int_t<std::uint32_t>>(ownerRunId));
			}
		}
	}
}

}
