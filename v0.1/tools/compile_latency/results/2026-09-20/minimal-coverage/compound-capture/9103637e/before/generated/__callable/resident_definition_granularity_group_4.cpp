#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_symbol_id_for_owner.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot_count.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_advance_lookup_slot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_matches.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_advance_lookup_slot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_symbol_identity_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_by_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_by_owner_and_symbol.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_symbol_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[46]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_symbol_definition_snapshots))))) {
		ResidentSymbolDefinitionSnapshotRow row = report->resident_symbol_definition_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	ResidentSymbolDefinitionSnapshotRow empty = ResidentSymbolDefinitionSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<> __latency_fn_resident_definition_granularity_max_symbol_id_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::max_symbol_id_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[47]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->symbol_id) > maxId)))) {
			maxId = cast<int_t<>>(snapshot->symbol_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_symbol_snapshot_lookup_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_symbol_snapshot_lookup_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[48]);
	report->resident_symbol_snapshot_lookup_slot_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->resident_symbol_snapshot_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<> __latency_fn_resident_definition_granularity_symbol_identity_lookup_slot_count(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_identity_lookup_slot_count", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[49]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->identity_hash) > static_cast<int_t<> >(0))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	if (static_cast<bool>((count <= static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	return ((count * static_cast<int_t<> >(2)) + static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<> __latency_fn_resident_definition_granularity_symbol_identity_lookup_slot(int_t<std::uint32_t> identityHash, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_identity_lookup_slot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[50]);
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	return (cast<int_t<>>(identityHash) % slotCount);
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<> __latency_fn_resident_definition_granularity_advance_lookup_slot(int_t<> slot, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::advance_lookup_slot", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[51]);
	int_t<> next = required_cast<int_t<>>((slot + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy((next >= slotCount)))) {
		return static_cast<int_t<> >(0);
	}
	return next;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
bool_t __latency_fn_resident_definition_granularity_symbol_identity_matches(ResidentSymbolDefinitionSnapshotRow snapshot, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow target) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_identity_matches", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[52]);
	return (((((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->identity_hash) > static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(snapshot->identity_hash), cast<int_t<>>(target->identity_hash))) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(target->source_unit_id))) && php::identical(cast<int_t<>>(snapshot->symbol_kind_id), cast<int_t<>>(target->symbol_kind_id))) && php::identical(cast<int_t<>>(snapshot->definition_role_id), cast<int_t<>>(target->definition_role_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_build_symbol_identity_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::build_symbol_identity_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[53]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(snapshotIds, slotCount);
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->identity_hash) > static_cast<int_t<> >(0))))) {
			int_t<> slot = required_cast<int_t<>>(__latency_fn_resident_definition_granularity_symbol_identity_lookup_slot(snapshot->identity_hash, slotCount));
			int_t<> probe = required_cast<int_t<>>(static_cast<int_t<> >(0));
			while (static_cast<bool>((probe < slotCount))) {
				int_t<std::uint32_t> existingSnapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(slot)));
				if (static_cast<bool>(php::identical(cast<int_t<>>(existingSnapshotId), static_cast<int_t<> >(0)))) {
					snapshotIds.at(slot) = snapshot->snapshot_id;
					break;
				}
				slot = __latency_fn_resident_definition_granularity_advance_lookup_slot(slot, slotCount);
				probe = (probe + static_cast<int_t<> >(1));
			}
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_build_symbol_snapshot_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& snapshotIds) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::build_symbol_snapshot_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[54]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(snapshotIds, slotCount);
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && __latency_fn_structure_row_ids_has_dense_id(snapshot->symbol_id, slotCount)))) {
			snapshotIds.at(__latency_fn_structure_row_ids_dense_index(snapshot->symbol_id)) = snapshot->snapshot_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_resident_definition_granularity_symbol_snapshot_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> snapshotReport, vector_t<int_t<std::uint32_t>>& snapshotIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::symbol_snapshot_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[55]);
	metricsReport->resident_symbol_snapshot_lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(metricsReport->resident_symbol_snapshot_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(symbolId, php::count(snapshotIds))))) {
		int_t<std::uint32_t> snapshotId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(snapshotIds.at(__latency_fn_structure_row_ids_dense_index(symbolId))));
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshotId), static_cast<int_t<> >(0)))) {
			ResidentSymbolDefinitionSnapshotRow empty = ResidentSymbolDefinitionSnapshotRow{};
			return empty;
		}
		ResidentSymbolDefinitionSnapshotRow snapshot = __latency_fn_resident_definition_granularity_symbol_snapshot_by_id(snapshotReport, cast<int_t<std::uint32_t>>(snapshotId));
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->symbol_id), cast<int_t<>>(symbolId))))) {
			return snapshot;
		}
	}
	metricsReport->resident_symbol_snapshot_lookup_fallback_scan_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(metricsReport->resident_symbol_snapshot_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	return __latency_fn_resident_definition_granularity_symbol_snapshot_by_owner_and_symbol(snapshotReport, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(symbolId));
}

}
