#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__callable/__latency_fn_partition_readiness_add_artifact_counts.hpp"
#include "__callable/__latency_fn_partition_readiness_append_artifact_row.hpp"
#include "__callable/__latency_fn_partition_readiness_complete_publication_fields.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_add_report_counts.hpp"
#include "__callable/__latency_fn_partition_readiness_append_report_row.hpp"
#include "__callable/__latency_fn_partition_readiness_complete_publication_fields.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_add_artifact_counts.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_backend_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_lowering_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_object_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_add_report_counts.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_backend_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_lowering_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_object_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_source_unit_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_symbol_body_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_first_source_snapshot_for_owner.hpp"
#include "__callable/__latency_fn_partition_readiness_symbol_snapshot_for_owner_position.hpp"
#include "__callable/__latency_fn_partition_readiness_append_report_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_source_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_source_owner_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_artifact_row(shared_p<PartitionReadinessArtifact>& artifact, PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_artifact_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[38]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_id), static_cast<int_t<> >(0)))) {
		row->row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->rows));
	}
	row = __latency_fn_partition_readiness_complete_publication_fields(row);
	(void) artifact->rows.append(row);
	artifact->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->rows));
	__latency_fn_partition_readiness_add_artifact_counts(artifact, row);
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_report_row(shared_p<CompilerProjectRunReport>& report, PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_report_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[39]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->row_id), static_cast<int_t<> >(0)))) {
		row->row_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->partition_readiness_rows));
	}
	row = __latency_fn_partition_readiness_complete_publication_fields(row);
	(void) report->partition_readiness_rows.append(row);
	report->partition_readiness_row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->partition_readiness_rows));
	__latency_fn_partition_readiness_add_report_counts(report, row);
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_add_artifact_counts(shared_p<PartitionReadinessArtifact>& artifact, PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::add_artifact_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[40]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		artifact->ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		artifact->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_source_unit_id())))) {
		artifact->source_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->source_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_symbol_body_id())))) {
		artifact->symbol_body_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->symbol_body_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_lowering_id())))) {
		artifact->lowering_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->lowering_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_backend_id())))) {
		artifact->backend_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->backend_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_object_id())))) {
		artifact->object_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->object_owner_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_blocked_id())))) {
			artifact->object_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(artifact->object_blocked_count) + static_cast<int_t<> >(1)));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_add_report_counts(shared_p<CompilerProjectRunReport>& report, PartitionReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::add_report_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[41]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
		report->partition_readiness_ready_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_ready_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->partition_readiness_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_blocked_count) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_source_unit_id())))) {
		report->partition_readiness_source_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_source_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_symbol_body_id())))) {
		report->partition_readiness_symbol_body_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_symbol_body_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_lowering_id())))) {
		report->partition_readiness_lowering_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_lowering_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_backend_id())))) {
		report->partition_readiness_backend_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_backend_owner_count) + static_cast<int_t<> >(1)));
		return;
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_kind_id), cast<int_t<>>(__latency_fn_partition_readiness_owner_kind_object_id())))) {
		report->partition_readiness_object_owner_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_object_owner_count) + static_cast<int_t<> >(1)));
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_blocked_id())))) {
			report->partition_readiness_object_blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->partition_readiness_object_blocked_count) + static_cast<int_t<> >(1)));
		}
	}
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
ResidentSourceUnitSnapshotRow __latency_fn_partition_readiness_first_source_snapshot_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::first_source_snapshot_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[42]);
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return snapshot;
		}
	}
	ResidentSourceUnitSnapshotRow empty = ResidentSourceUnitSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
ResidentSymbolDefinitionSnapshotRow __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> position) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::symbol_snapshot_for_owner_position", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[43]);
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	ResidentSymbolDefinitionSnapshotRow first = ResidentSymbolDefinitionSnapshotRow{};
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			if (static_cast<bool>(php::identical(cast<int_t<>>(first->snapshot_id), static_cast<int_t<> >(0)))) {
				first = snapshot;
			}
			if (static_cast<bool>(php::identical(index, position))) {
				return snapshot;
			}
			index = (index + static_cast<int_t<> >(1));
		}
	}
	return first;
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_source_owner_rows(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_source_owner_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[44]);
	int_t<> added = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(projectRunRow->run_id)))) {
			__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_source_owner_row(__latency_fn_structure_row_ids_none_id(), snapshot));
			added = (added + static_cast<int_t<> >(1));
		}
	}
	if (static_cast<bool>((php::identical(added, static_cast<int_t<> >(0)) && (cast<int_t<>>(projectRunRow->source_count) > static_cast<int_t<> >(0))))) {
		ResidentSourceUnitSnapshotRow snapshot = ResidentSourceUnitSnapshotRow{};
		snapshot->owner_run_id = projectRunRow->run_id;
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_source_owner_row(__latency_fn_structure_row_ids_none_id(), snapshot));
	}
}

}
