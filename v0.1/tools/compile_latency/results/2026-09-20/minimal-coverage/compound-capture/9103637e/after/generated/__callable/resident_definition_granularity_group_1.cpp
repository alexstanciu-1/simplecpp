#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_surface_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_dirty_definition_value_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reuse_blocked_dirty_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_from_row.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_status_current_id.hpp"
#include "__callable/__latency_fn_source_buffers_content_hash32.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_append_source_unit_snapshots.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_from_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_source_snapshot_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_max_source_unit_id_for_owner.hpp"
namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_dirty_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::dirty_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_dirty_source_unit_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::dirty_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[17]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_dirty_definition_surface_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::dirty_definition_surface_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[18]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_dirty_definition_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::dirty_definition_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[19]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_dirty_definition_value_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::dirty_definition_value_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[20]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_reuse_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::reuse_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[21]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_reuse_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::reuse_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[22]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_definition_granularity_reuse_blocked_dirty_id() {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::reuse_blocked_dirty_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[23]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSourceUnitSnapshotRow __latency_fn_resident_definition_granularity_source_snapshot_from_row(shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::source_snapshot_from_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[24]);
	ResidentSourceUnitSnapshotRow row = ResidentSourceUnitSnapshotRow{};
	string_t sourceText = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(sourceUnits, sourceUnit->source_unit_id));
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnit->source_unit_id;
	row->source_unit_key_id = sourceUnit->source_unit_key_id;
	row->content_hash = __latency_fn_source_buffers_content_hash32(sourceText);
	row->source_length = sourceUnit->source_length;
	row->line_count = sourceUnit->line_count;
	row->status_id = __latency_fn_resident_definition_granularity_status_current_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
void __latency_fn_resident_definition_granularity_append_source_unit_snapshots(shared_p<CompilerProjectRunReport>& report, shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::append_source_unit_snapshots", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[25]);
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		ResidentSourceUnitSnapshotRow row = __latency_fn_resident_definition_granularity_source_snapshot_from_row(sourceUnits, sourceUnit, cast<int_t<std::uint32_t>>(ownerRunId));
		row->snapshot_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_source_unit_snapshots));
		(void) report->resident_source_unit_snapshots.append(row);
	}
	report->resident_source_unit_snapshot_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->resident_source_unit_snapshots));
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSourceUnitSnapshotRow __latency_fn_resident_definition_granularity_source_snapshot_by_owner_and_source(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::source_snapshot_by_owner_and_source", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[26]);
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(snapshot->source_unit_id), cast<int_t<>>(sourceUnitId))))) {
			return snapshot;
		}
	}
	ResidentSourceUnitSnapshotRow empty = ResidentSourceUnitSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
ResidentSourceUnitSnapshotRow __latency_fn_resident_definition_granularity_source_snapshot_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> snapshotId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::source_snapshot_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[27]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(snapshotId, php::count(report->resident_source_unit_snapshots))))) {
		ResidentSourceUnitSnapshotRow row = report->resident_source_unit_snapshots[__latency_fn_structure_row_ids_dense_index(snapshotId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->snapshot_id), cast<int_t<>>(snapshotId)))) {
			return row;
		}
	}
	ResidentSourceUnitSnapshotRow empty = ResidentSourceUnitSnapshotRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_definition_granularity[]; }
namespace scpp {
int_t<> __latency_fn_resident_definition_granularity_max_source_unit_id_for_owner(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_definition_granularity::max_source_unit_id_for_owner", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_definition_granularity.phs", __latency_lines_resident_definition_granularity[28]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_source_unit_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(ownerRunId)) && (cast<int_t<>>(snapshot->source_unit_id) > maxId)))) {
			maxId = cast<int_t<>>(snapshot->source_unit_id);
		}
	}
	return maxId;
}

}
