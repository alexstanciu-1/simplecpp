#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentSourceUnitChangeRow.hpp"
#include "__types/ResidentSourceUnitEarlySkipArtifact.hpp"
#include "__types/ResidentSourceUnitEarlySkipDecisionRow.hpp"
#include "__types/ResidentTokenListSnapshotRow.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_source_change_by_owner_and_source.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_source_change_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_token_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_max_source_unit_id_from_early_skip.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_build_source_change_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_build_token_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_build_frontend_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_append_lookup_metrics.hpp"
#include "__callable/__latency_fn_source_unit_frontend_work_decisions_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentSourceUnitChangeRow __latency_fn_source_unit_frontend_work_decisions_source_change_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::source_change_by_owner_and_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[13]);
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(change->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return change;
		}
	}
	ResidentSourceUnitChangeRow empty = ResidentSourceUnitChangeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentSourceUnitChangeRow __latency_fn_source_unit_frontend_work_decisions_source_change_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> changeId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::source_change_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[14]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(changeId, php::count(report->resident_source_unit_changes))))) {
		ResidentSourceUnitChangeRow row = report->resident_source_unit_changes[__latency_fn_structure_row_ids_dense_index(changeId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_id), cast<int_t<>>(changeId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->change_id), cast<int_t<>>(changeId)))) {
			return row;
		}
	}
	ResidentSourceUnitChangeRow empty = ResidentSourceUnitChangeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentTokenListSnapshotRow __latency_fn_source_unit_frontend_work_decisions_token_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::token_snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[15]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_token_list_snapshots))))) {
		ResidentTokenListSnapshotRow row = report->resident_token_list_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_token_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	ResidentTokenListSnapshotRow empty = ResidentTokenListSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
ResidentFrontendNodeListSnapshotRow __latency_fn_source_unit_frontend_work_decisions_frontend_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::frontend_snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[16]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_frontend_node_list_snapshots))))) {
		ResidentFrontendNodeListSnapshotRow row = report->resident_frontend_node_list_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_frontend_node_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	ResidentFrontendNodeListSnapshotRow empty = ResidentFrontendNodeListSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
int_t<> __latency_fn_source_unit_frontend_work_decisions_max_source_unit_id_from_early_skip(ResidentSourceUnitEarlySkipArtifact artifact, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::max_source_unit_id_from_early_skip", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[17]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->decisions;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto decision = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(decision->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(decision->source_unit_id) > maxId)))) {
			maxId = cast<int_t<>>(decision->source_unit_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_build_source_change_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& changeIds) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::build_source_change_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[18]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(changeIds, slotCount);
	auto __latency_local_0 = report->resident_source_unit_changes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto change = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(change->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(change->source_unit_id, slotCount)))) {
			changeIds.at(__latency_fn_structure_row_ids_dense_index(change->source_unit_id)) = change->change_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_build_token_snapshot_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::build_token_snapshot_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[19]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(snapshotIds, slotCount);
	auto __latency_local_0 = report->resident_token_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(snapshot->source_unit_id, slotCount)))) {
			snapshotIds.at(__latency_fn_structure_row_ids_dense_index(snapshot->source_unit_id)) = snapshot->snapshot_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_build_frontend_snapshot_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::build_frontend_snapshot_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[20]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(snapshotIds, slotCount);
	auto __latency_local_0 = report->resident_frontend_node_list_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(snapshot->source_unit_id, slotCount)))) {
			snapshotIds.at(__latency_fn_structure_row_ids_dense_index(snapshot->source_unit_id)) = snapshot->snapshot_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_work_decisions[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_work_decisions_append_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_work_decisions::append_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_work_decisions.phs", __latency_lines_source_unit_frontend_work_decisions[21]);
	report->resident_source_unit_frontend_work_lookup_slot_count = __latency_fn_source_unit_frontend_work_decisions_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_work_lookup_slot_count) + slotCount));
}

}
