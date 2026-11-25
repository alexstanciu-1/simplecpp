#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentSymbolDefinitionChangeRow.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_owner_and_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_max_symbol_id_from_symbol_changes.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_by_owner_and_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_added_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_deleted_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_no_change_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_change_surface_changed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_body_changed_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_deleted_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_kind_from_symbol_change.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_new_body_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_action_build_new_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::action_build_new_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[17]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[18]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_work_decisions_snapshot_by_owner_and_symbol(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::snapshot_by_owner_and_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[19]);
	auto __latency_local_0 = report->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->symbol_id), cast<int_t<>>(symbolId))))) {
			return snapshot;
		}
	}
	ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_work_decisions_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[20]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_function_body_snapshots))))) {
		ResidentFunctionBodySnapshotRow row = report->resident_function_body_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_work_decisions_max_symbol_id_from_symbol_changes(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::max_symbol_id_from_symbol_changes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[21]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_symbol_definition_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(change->symbol_id) > maxId)))) {
			maxId = cast<int_t<>>(change->symbol_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_build_snapshot_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::build_snapshot_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[22]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(snapshotIds, slotCount);
	auto __latency_local_0 = report->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(snapshot->symbol_id, slotCount)))) {
			snapshotIds.at(__latency_fn_structure_row_ids_dense_index(snapshot->symbol_id)) = snapshot->snapshot_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
void __latency_fn_resident_function_body_work_decisions_append_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::append_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[23]);
	report->resident_function_body_work_lookup_slot_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_function_body_work_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_work_decisions_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[24]);
	metricsReport->resident_function_body_work_lookup_probe_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_work_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(symbolId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(symbolId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
			return empty;
		}
		ResidentFunctionBodySnapshotRow snapshot = __latency_fn_resident_function_body_work_decisions_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->symbol_id), cast<int_t<>>(symbolId))))) {
			return snapshot;
		}
	}
	metricsReport->resident_function_body_work_lookup_fallback_scan_count = __latency_fn_resident_function_body_work_decisions_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_work_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_function_body_work_decisions_snapshot_by_owner_and_symbol(snapshotReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(symbolId));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_change_kind_from_symbol_change(ResidentSymbolDefinitionChangeRow change) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_kind_from_symbol_change", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[25]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_added_id())))) {
		return __latency_fn_resident_function_body_work_decisions_change_new_body_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_no_change_id())))) {
		return __latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_surface_changed_id())))) {
		return __latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(change->change_kind_id), cast<int_t<>>(__latency_fn_resident_definition_granularity_definition_change_deleted_id())))) {
		return __latency_fn_resident_function_body_work_decisions_change_deleted_body_id();
	}
	return __latency_fn_resident_function_body_work_decisions_change_body_changed_id();
}

}
