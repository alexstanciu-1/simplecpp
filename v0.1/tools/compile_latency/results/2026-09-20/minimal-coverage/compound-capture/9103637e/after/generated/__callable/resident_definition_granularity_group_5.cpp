#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_advance_lookup_slot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_matches.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_identity_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_previous_symbol_snapshot_for_current.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_identity_lookup.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_current_symbol_snapshot_for_previous.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_identity_lookup.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_deleted_symbol_definition_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_surface_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_cold_changes_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_added_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_deleted_symbol_definition_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_changes_from_previous_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_change.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_symbol_identity_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_current_symbol_snapshot_for_previous.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_added_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_value_changed_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_role_constant_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_body_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_surface_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_value_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_new_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_symbol_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_previous_symbol_snapshot_for_current.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_blocked_dirty_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_ready_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot_count.hpp"
namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_symbol_snapshot_from_identity_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow target) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_snapshot_from_identity_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[56]);
	ResidentSymbolDefinitionSnapshotRow empty = ResidentSymbolDefinitionSnapshotRow{};
	if (static_cast<bool>((php::identical(cast<int_t<>>(target->identity_hash), static_cast<int_t<> >(0)) || php::identical(php::count(snapshotIds), static_cast<int_t<> >(0))))) {
		return empty;
	}
	metricsReport->resident_symbol_snapshot_lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(metricsReport->resident_symbol_snapshot_lookup_probe_count) + static_cast<int_t<> >(1)));
	int_t<> slotCount = required_cast<int_t<>>(php::count(snapshotIds));
	int_t<> slot = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot(target->identity_hash, slotCount));
	int_t<> probe = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((probe < slotCount))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(slot)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			return empty;
		}
		ResidentSymbolDefinitionSnapshotRow snapshot = __latency_fn_resident_definition_granularity_symbol_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_definition_granularity_symbol_identity_matches(snapshot, cast<int_t<std::uint32_t>>(ownerRunId), target)))) {
			return snapshot;
		}
		slot = __latency_fn_resident_definition_granularity_advance_lookup_slot(slot, slotCount);
		probe = (probe + static_cast<int_t<> >(1));
	}
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_previous_symbol_snapshot_for_current(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> previousReport, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, vector_t<int_t<std::uint32_t>>& previousIdentitySnapshotIds, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow current) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::previous_symbol_snapshot_for_current", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[57]);
	if (static_cast<bool>((cast<int_t<>>(current->identity_hash) > static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_definition_granularity_symbol_snapshot_from_identity_lookup(metricsReport, previousReport, previousIdentitySnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), current);
	}
	return __latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup(metricsReport, previousReport, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), current->symbol_id);
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_current_symbol_snapshot_for_previous(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> currentReport, vector_t<int_t<std::uint32_t>>& currentSnapshotIds, vector_t<int_t<std::uint32_t>>& currentIdentitySnapshotIds, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow previous) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::current_symbol_snapshot_for_previous", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[58]);
	if (static_cast<bool>((cast<int_t<>>(previous->identity_hash) > static_cast<int_t<> >(0)))) {
		return __latency_fn_resident_definition_granularity_symbol_snapshot_from_identity_lookup(metricsReport, currentReport, currentIdentitySnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), previous);
	}
	return __latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup(metricsReport, currentReport, currentSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), previous->symbol_id);
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_definition_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionSnapshotRow current, ResidentSymbolDefinitionSnapshotRow previous, int_t<std::uint16_t> changeKindId, int_t<std::uint16_t> dirtyScopeId, int_t<std::uint16_t> reuseScopeId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_definition_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[59]);
	ResidentSymbolDefinitionChangeRow row = ResidentSymbolDefinitionChangeRow{};
	row->change_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_symbol_definition_changes));
	row->owner_run_id = current->owner_run_id;
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = current->snapshot_id;
	row->symbol_id = current->symbol_id;
	row->source_unit_id = current->source_unit_id;
	row->symbol_kind_id = current->symbol_kind_id;
	row->definition_role_id = current->definition_role_id;
	row->change_kind_id = changeKindId;
	row->dirty_scope_id = dirtyScopeId;
	row->reuse_scope_id = reuseScopeId;
	(void) report->resident_symbol_definition_changes.append(row);
	report->resident_symbol_definition_change_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_symbol_definition_changes));
	if (static_cast<bool>(php::identical(cast<int_t<>>(reuseScopeId), cast<int_t<>>(__latency_fn_resident_definition_granularity_reuse_ready_id())))) {
		report->resident_symbol_definition_reuse_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_symbol_definition_reuse_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_surface_changed_id())))) {
		report->resident_symbol_definition_surface_change_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_symbol_definition_surface_change_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_body_changed_id())))) {
		report->resident_symbol_definition_body_change_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_symbol_definition_body_change_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(changeKindId), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_value_changed_id())))) {
		report->resident_symbol_definition_value_change_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_symbol_definition_value_change_count) + static_cast<int_t<> >(1)));
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_deleted_symbol_definition_change(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionSnapshotRow previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_deleted_symbol_definition_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[60]);
	ResidentSymbolDefinitionChangeRow row = ResidentSymbolDefinitionChangeRow{};
	row->change_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_symbol_definition_changes));
	row->owner_run_id = ownerRunId;
	row->previous_snapshot_id = previous->snapshot_id;
	row->current_snapshot_id = __latency_fn_structure_row_ids_none_id();
	row->symbol_id = previous->symbol_id;
	row->source_unit_id = previous->source_unit_id;
	row->symbol_kind_id = previous->symbol_kind_id;
	row->definition_role_id = previous->definition_role_id;
	row->change_kind_id = __latency_fn_resident_definition_granularity_definition_change_deleted_id();
	row->dirty_scope_id = __latency_fn_resident_definition_granularity_dirty_definition_surface_id();
	row->reuse_scope_id = __latency_fn_resident_definition_granularity_reuse_none_id();
	(void) report->resident_symbol_definition_changes.append(row);
	report->resident_symbol_definition_change_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_symbol_definition_changes));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_cold_changes_for_owner(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_cold_changes_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[61]);
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto current = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(current->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSymbolDefinitionSnapshotRow previous = ResidentSymbolDefinitionSnapshotRow{};
			__latency_fn_resident_definition_granularity_append_symbol_definition_change(report, current, previous, __latency_fn_resident_definition_granularity_definition_change_added_id(), __latency_fn_resident_definition_granularity_dirty_new_id(), __latency_fn_resident_definition_granularity_reuse_none_id());
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_changes_from_previous_for_owner(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_changes_from_previous_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[62]);
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_symbol_id_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	int_t<> previousSlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_symbol_id_for_owner(previousReport, cast<int_t<std::uint32_t>>(ownerRunId)));
	if (static_cast<bool>((previousSlotCount > slotCount))) {
		slotCount = previousSlotCount;
	}
	int_t<> identitySlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot_count(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	int_t<> previousIdentitySlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot_count(previousReport, cast<int_t<std::uint32_t>>(ownerRunId)));
	if (static_cast<bool>((previousIdentitySlotCount > identitySlotCount))) {
		identitySlotCount = previousIdentitySlotCount;
	}
	vector_t<int_t<std::uint32_t>> currentSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	vector_t<int_t<std::uint32_t>> currentIdentitySnapshotIds = {};
	vector_t<int_t<std::uint32_t>> previousIdentitySnapshotIds = {};
	__latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, currentSnapshotIds);
	__latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids(previousReport, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, previousSnapshotIds);
	__latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics(report, (slotCount * static_cast<int_t<> >(2)));
	if (static_cast<bool>((identitySlotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_definition_granularity_build_symbol_identity_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), identitySlotCount, currentIdentitySnapshotIds);
		__latency_fn_resident_definition_granularity_build_symbol_identity_lookup_ids(previousReport, cast<int_t<std::uint32_t>>(ownerRunId), identitySlotCount, previousIdentitySnapshotIds);
		__latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics(report, (identitySlotCount * static_cast<int_t<> >(2)));
	}
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto current = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(current->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSymbolDefinitionSnapshotRow previous = __latency_fn_resident_definition_granularity_previous_symbol_snapshot_for_current(report, previousReport, previousSnapshotIds, previousIdentitySnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), current);
			if (static_cast<bool>(php::identical(cast<int_t<>>(previous->snapshot_id), static_cast<int_t<> >(0)))) {
				__latency_fn_resident_definition_granularity_append_symbol_definition_change(report, current, previous, __latency_fn_resident_definition_granularity_definition_change_added_id(), __latency_fn_resident_definition_granularity_dirty_new_id(), __latency_fn_resident_definition_granularity_reuse_none_id());
			}
			else {
				if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(previous->public_surface_hash), cast<int_t<>>(current->public_surface_hash))))) {
					__latency_fn_resident_definition_granularity_append_symbol_definition_change(report, current, previous, __latency_fn_resident_definition_granularity_definition_change_surface_changed_id(), __latency_fn_resident_definition_granularity_dirty_definition_surface_id(), __latency_fn_resident_definition_granularity_reuse_blocked_dirty_id());
				}
				else {
					if (static_cast<bool>((php::not_identical(cast<int_t<>>(previous->value_hash), cast<int_t<>>(current->value_hash)) && php::identical(cast<int_t<>>(current->definition_role_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_role_constant_id()))))) {
						__latency_fn_resident_definition_granularity_append_symbol_definition_change(report, current, previous, __latency_fn_resident_definition_granularity_definition_change_value_changed_id(), __latency_fn_resident_definition_granularity_dirty_definition_value_id(), __latency_fn_resident_definition_granularity_reuse_blocked_dirty_id());
					}
					else {
						if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(previous->body_hash), cast<int_t<>>(current->body_hash))))) {
							__latency_fn_resident_definition_granularity_append_symbol_definition_change(report, current, previous, __latency_fn_resident_definition_granularity_definition_change_body_changed_id(), __latency_fn_resident_definition_granularity_dirty_definition_body_id(), __latency_fn_resident_definition_granularity_reuse_blocked_dirty_id());
						}
						else {
							__latency_fn_resident_definition_granularity_append_symbol_definition_change(report, current, previous, __latency_fn_resident_definition_granularity_definition_change_no_change_id(), __latency_fn_resident_definition_granularity_dirty_none_id(), __latency_fn_resident_definition_granularity_reuse_ready_id());
						}
					}
				}
			}
		}
	}
	auto __latency_local_2 = previousReport->resident_symbol_definition_snapshots;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto previous = __latency_local_3.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(previous->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			ResidentSymbolDefinitionSnapshotRow current = __latency_fn_resident_definition_granularity_current_symbol_snapshot_for_previous(report, report, currentSnapshotIds, currentIdentitySnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), previous);
			if (static_cast<bool>(php::identical(cast<int_t<>>(current->snapshot_id), static_cast<int_t<> >(0)))) {
				__latency_fn_resident_definition_granularity_append_deleted_symbol_definition_change(report, previous, cast<int_t<std::uint32_t>>(ownerRunId));
			}
		}
	}
}

}
