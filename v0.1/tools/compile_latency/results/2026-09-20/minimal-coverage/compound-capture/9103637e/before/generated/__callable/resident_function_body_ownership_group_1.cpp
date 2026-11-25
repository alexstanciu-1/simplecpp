#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/ProjectFrontendModel.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentFunctionBodySnapshotRow.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_probe.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_model_by_source_unit_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_model_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_frontend_state_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_probe.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_probe.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_by_owner_and_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_max_symbol_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_build_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_increment_lookup_probe.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_by_owner_and_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_ownership_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan(shared_p<CompilerProjectRunReport>& report) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::increment_lookup_fallback_scan", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[11]);
	report->resident_function_body_ownership_lookup_fallback_scan_count = __latency_fn_resident_function_body_ownership_uint32_from_int((cast<int_t<>>(report->resident_function_body_ownership_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
shared_p<FrontendModel> __latency_fn_resident_function_body_ownership_model_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<ProjectFrontendModel> project, vector_t<int_t<std::uint32_t>>& modelIndexIds, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::model_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[12]);
	__latency_fn_resident_function_body_ownership_increment_lookup_probe(metricsReport);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(modelIndexIds))))) {
		int_t<std::uint32_t> modelIndexId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(modelIndexIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>((cast<int_t<>>(modelIndexId) > static_cast<int_t<> >(0)))) {
			int_t<> modelIndex = required_cast<int_t<>>((cast<int_t<>>(modelIndexId) - static_cast<int_t<> >(1)));
			if (static_cast<bool>(((modelIndex >= static_cast<int_t<> >(0)) && (modelIndex < php::count(project->models))))) {
				return project->models[modelIndex];
			}
		}
	}
	__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan(metricsReport);
	return __latency_fn_resident_function_body_ownership_model_by_source_unit_id(project, cast<int_t<std::uint32_t>>(sourceUnitId));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_function_body_ownership_frontend_state_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> report, vector_t<int_t<std::uint32_t>>& stateIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::frontend_state_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[13]);
	__latency_fn_resident_function_body_ownership_increment_lookup_probe(metricsReport);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(stateIds))))) {
		int_t<std::uint32_t> stateId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(stateIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(stateId), static_cast<int_t<> >(0)))) {
			ResidentSourceUnitFrontendStateRow empty = ResidentSourceUnitFrontendStateRow{};
			return empty;
		}
		ResidentSourceUnitFrontendStateRow state = __latency_fn_resident_source_unit_frontend_states_state_by_id(report, stateId);
		if (static_cast<bool>((php::identical(cast<int_t<>>(state->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(state->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return state;
		}
	}
	__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan(metricsReport);
	return __latency_fn_resident_source_unit_frontend_states_previous_state_by_owner_and_source(report, ownerRunId, sourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_resident_function_body_ownership_frontend_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> report, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::frontend_snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[14]);
	__latency_fn_resident_function_body_ownership_increment_lookup_probe(metricsReport);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentFrontendNodeListSnapshotRow empty = ResidentFrontendNodeListSnapshotRow{};
			return empty;
		}
		ResidentFrontendNodeListSnapshotRow snapshot = __latency_fn_resident_frontend_node_lists_snapshot_by_id(report, snapshotId);
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan(metricsReport);
	return __latency_fn_resident_frontend_node_lists_snapshot_by_owner_and_source(report, ownerRunId, sourceUnitId);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_snapshot_by_owner_and_symbol(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::snapshot_by_owner_and_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[15]);
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

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[16]);
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

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_ownership_max_symbol_id_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::max_symbol_id_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[17]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->symbol_id) > maxId)))) {
			maxId = cast<int_t<>>(snapshot->symbol_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
void __latency_fn_resident_function_body_ownership_build_snapshot_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::build_snapshot_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[18]);
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

namespace scpp { extern const int __latency_lines_resident_function_body_ownership[]; }
namespace scpp {
ResidentFunctionBodySnapshotRow __latency_fn_resident_function_body_ownership_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_ownership::snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_ownership.phs", __latency_lines_resident_function_body_ownership[19]);
	__latency_fn_resident_function_body_ownership_increment_lookup_probe(metricsReport);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(symbolId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(symbolId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentFunctionBodySnapshotRow empty = ResidentFunctionBodySnapshotRow{};
			return empty;
		}
		ResidentFunctionBodySnapshotRow snapshot = __latency_fn_resident_function_body_ownership_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->symbol_id), cast<int_t<>>(symbolId))))) {
			return snapshot;
		}
	}
	__latency_fn_resident_function_body_ownership_increment_lookup_fallback_scan(metricsReport);
	return __latency_fn_resident_function_body_ownership_snapshot_by_owner_and_symbol(snapshotReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(symbolId));
}

}
