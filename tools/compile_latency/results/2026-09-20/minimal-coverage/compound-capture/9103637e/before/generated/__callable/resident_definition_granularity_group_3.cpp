#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ResidentSourceUnitSymbolStateRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_kind_function_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_definition_role_function_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_definition_role_from_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_hash_from_symbol.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_project_symbol_index_body_shape_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_export_shape_key.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_status_current_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_definition_role_from_symbol.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_hash_from_symbol.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_row.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_snapshot.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_snapshot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_snapshots.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_row.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_copy_reused_symbol_snapshot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_status_current_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_copy_reused_symbol_snapshot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reused_previous_symbol_snapshot_for_current.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_by_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_snapshot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_definition_snapshots_with_reuse.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_symbol_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reused_previous_symbol_snapshot_for_current.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_by_owner_and_symbol.hpp"
namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_symbol_definition_role_from_symbol(ProjectSymbolIndexRow symbol) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_definition_role_from_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[37]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(symbol->symbol_kind_id), cast<int_t<>>(__latency_fn_project_symbol_index_symbol_kind_function_id())))) {
		return __latency_fn_resident_definition_granularity_definition_role_function_id();
	}
	return __latency_fn_resident_definition_granularity_definition_role_function_id();
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_definition_granularity_symbol_identity_hash_from_symbol(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow symbol) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_identity_hash_from_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[38]);
	string_t identity = required_cast<string_t>((string_t("resident_symbol_identity:v2:") + cast<string_t>(__latency_fn_project_symbol_index_source_unit_key(index, symbol)) + string_t(":") + cast<string_t>(__latency_fn_project_symbol_index_symbol_key(index, symbol)) + string_t(":") + cast<string_t>(cast<int_t<>>(symbol->symbol_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(symbol->scope_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(symbol->parameter_count))));
	return __latency_fn_source_buffers_content_hash32(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_symbol_snapshot_from_row(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow symbol, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_snapshot_from_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[39]);
	ResidentSymbolDefinitionSnapshotRow row = ResidentSymbolDefinitionSnapshotRow{};
	string_t surfaceKey = required_cast<string_t>(__latency_fn_project_symbol_index_export_shape_key(index, symbol));
	string_t bodyKey = required_cast<string_t>(__latency_fn_project_symbol_index_body_shape_key(index, symbol));
	row->owner_run_id = ownerRunId;
	row->symbol_id = symbol->symbol_id;
	row->source_unit_id = symbol->source_unit_id;
	row->source_row_id = symbol->source_row_id;
	row->identity_hash = __latency_fn_resident_definition_granularity_symbol_identity_hash_from_symbol(index, symbol);
	row->symbol_kind_id = symbol->symbol_kind_id;
	row->definition_role_id = __latency_fn_resident_definition_granularity_symbol_definition_role_from_symbol(symbol);
	row->public_surface_hash = __latency_fn_source_buffers_content_hash32(surfaceKey);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(bodyKey, string_t(""))))) {
		row->body_hash = __latency_fn_source_buffers_content_hash32(bodyKey);
	}
	row->value_hash = __latency_fn_structure_row_ids_none_id();
	row->status_id = __latency_fn_resident_definition_granularity_status_current_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_definition_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionSnapshotRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_definition_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[40]);
	row->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_symbol_definition_snapshots));
	(void) report->resident_symbol_definition_snapshots.append(row);
	report->resident_symbol_definition_snapshot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_symbol_definition_snapshots));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_definition_snapshots(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_definition_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[41]);
	auto __latency_local_0 = index->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		ResidentSymbolDefinitionSnapshotRow row = __latency_fn_resident_definition_granularity_symbol_snapshot_from_row(index, symbol, cast<int_t<std::uint32_t>>(ownerRunId));
		__latency_fn_resident_definition_granularity_append_symbol_definition_snapshot(report, row);
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_copy_reused_symbol_snapshot(ResidentSymbolDefinitionSnapshotRow previous, ProjectSymbolIndexRow currentSymbol, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::copy_reused_symbol_snapshot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[42]);
	ResidentSymbolDefinitionSnapshotRow row = previous;
	row->owner_run_id = ownerRunId;
	row->symbol_id = currentSymbol->symbol_id;
	row->source_unit_id = currentSymbol->source_unit_id;
	row->source_row_id = currentSymbol->source_row_id;
	row->status_id = __latency_fn_resident_definition_granularity_status_current_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_reused_previous_symbol_snapshot_for_current(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> previousReport, ProjectSymbolIndexRow symbol, ResidentSourceUnitSymbolStateRow currentState, vector_t<int_t<std::uint32_t>>& previousSnapshotIds, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::reused_previous_symbol_snapshot_for_current", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[43]);
	ResidentSymbolDefinitionSnapshotRow empty = ResidentSymbolDefinitionSnapshotRow{};
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(currentState->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_symbol_states_state_kind_reused_previous_id()))))) {
		return empty;
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(currentState->previous_state_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(currentState->first_symbol_id), static_cast<int_t<> >(0))))) {
		return empty;
	}
	ResidentSourceUnitSymbolStateRow previousState = __latency_fn_resident_source_unit_symbol_states_state_by_id(previousReport, currentState->previous_state_id);
	if (static_cast<bool>(((php::identical(cast<int_t<>>(previousState->state_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(previousState->first_symbol_id), static_cast<int_t<> >(0))) || php::not_identical(cast<int_t<>>(previousState->symbol_count), cast<int_t<>>(currentState->symbol_count))))) {
		return empty;
	}
	int_t<> offset = required_cast<int_t<>>((cast<int_t<>>(symbol->symbol_id) - cast<int_t<>>(currentState->first_symbol_id)));
	if (static_cast<bool>(((offset < static_cast<int_t<> >(0)) || (offset >= cast<int_t<>>(currentState->symbol_count))))) {
		return empty;
	}
	int_t<std::uint32_t> previousSymbolId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(previousState->first_symbol_id) + offset)));
	ResidentSymbolDefinitionSnapshotRow previousSnapshot = __latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup(metricsReport, previousReport, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(previousSymbolId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(previousSnapshot->snapshot_id), static_cast<int_t<> >(0)))) {
		return empty;
	}
	return __latency_fn_resident_definition_granularity_copy_reused_symbol_snapshot(previousSnapshot, symbol, cast<int_t<std::uint32_t>>(ownerRunId));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_definition_snapshots_with_reuse(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previousReport, shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_definition_snapshots_with_reuse", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[44]);
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	vector_t<int_t<std::uint32_t>> currentStateIds = {};
	if (static_cast<bool>((slotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_source_unit_symbol_states_build_state_lookup_ids(report, ownerRunId, slotCount, currentStateIds);
		__latency_fn_resident_source_unit_symbol_states_append_lookup_metrics(report, slotCount);
	}
	int_t<> previousSymbolSlotCount = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_max_symbol_id_for_owner(previousReport, cast<int_t<std::uint32_t>>(ownerRunId)));
	vector_t<int_t<std::uint32_t>> previousSnapshotIds = {};
	if (static_cast<bool>((previousSymbolSlotCount > static_cast<int_t<> >(0)))) {
		__latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids(previousReport, cast<int_t<std::uint32_t>>(ownerRunId), previousSymbolSlotCount, previousSnapshotIds);
		__latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics(report, previousSymbolSlotCount);
	}
	auto __latency_local_0 = index->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto symbol = __latency_local_1.value_copy();
		ResidentSourceUnitSymbolStateRow currentState = __latency_fn_resident_source_unit_symbol_states_state_from_lookup(report, report, currentStateIds, ownerRunId, symbol->source_unit_id);
		ResidentSymbolDefinitionSnapshotRow reused = __latency_fn_resident_definition_granularity_reused_previous_symbol_snapshot_for_current(report, previousReport, symbol, currentState, previousSnapshotIds, cast<int_t<std::uint32_t>>(ownerRunId));
		if (static_cast<bool>((cast<int_t<>>(reused->snapshot_id) > static_cast<int_t<> >(0)))) {
			__latency_fn_resident_definition_granularity_append_symbol_definition_snapshot(report, reused);
		}
		else {
			__latency_fn_resident_definition_granularity_append_symbol_definition_snapshot(report, __latency_fn_resident_definition_granularity_symbol_snapshot_from_row(index, symbol, cast<int_t<std::uint32_t>>(ownerRunId)));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_symbol_snapshot_by_owner_and_symbol(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_snapshot_by_owner_and_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[45]);
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->symbol_id), cast<int_t<>>(symbolId))))) {
			return snapshot;
		}
	}
	ResidentSymbolDefinitionSnapshotRow empty = ResidentSymbolDefinitionSnapshotRow{};
	return empty;
}

}
