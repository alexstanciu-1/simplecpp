#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentSourceUnitSnapshotRow.hpp"
#include "__types/ResidentSymbolDefinitionSnapshotRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_partition_readiness_append_report_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_symbol_body_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_symbol_body_owner_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_append_lowering_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_report_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_lowering_plan_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_lowering_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_first_source_snapshot_for_owner.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_lowering_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_partition_readiness_symbol_snapshot_for_owner_position.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_append_backend_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_report_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_backend_partition_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_backend_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_partition_readiness_symbol_snapshot_for_owner_position.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_append_object_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_report_row.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_backend_partition_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id.hpp"
#include "__callable/__latency_fn_partition_readiness_blocked_reason_object_execution_deferred_id.hpp"
#include "__callable/__latency_fn_partition_readiness_owner_kind_object_id.hpp"
#include "__callable/__latency_fn_partition_readiness_row.hpp"
#include "__callable/__latency_fn_partition_readiness_status_blocked_id.hpp"
#include "__callable/__latency_fn_partition_readiness_symbol_snapshot_for_owner_position.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_append_memory_estimate.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_partition_readiness_append_backend_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_from_compiler_project_run_row.hpp"
#include "__callable/__latency_fn_partition_readiness_append_lowering_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_memory_estimate.hpp"
#include "__callable/__latency_fn_partition_readiness_append_object_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_source_owner_rows.hpp"
#include "__callable/__latency_fn_partition_readiness_append_symbol_body_owner_rows.hpp"
namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_symbol_body_owner_rows(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_symbol_body_owner_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[45]);
	int_t<> added = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_symbol_definition_snapshots;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto snapshot = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(snapshot->owner_run_id), cast<int_t<>>(projectRunRow->run_id)))) {
			__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_symbol_body_owner_row(__latency_fn_structure_row_ids_none_id(), snapshot));
			added = (added + static_cast<int_t<> >(1));
		}
	}
	if (static_cast<bool>((php::identical(added, static_cast<int_t<> >(0)) && (cast<int_t<>>(projectRunRow->symbol_count) > static_cast<int_t<> >(0))))) {
		ResidentSymbolDefinitionSnapshotRow snapshot = ResidentSymbolDefinitionSnapshotRow{};
		snapshot->owner_run_id = projectRunRow->run_id;
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_symbol_body_owner_row(__latency_fn_structure_row_ids_none_id(), snapshot));
	}
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_lowering_owner_rows(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_lowering_owner_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[46]);
	int_t<> planCount = required_cast<int_t<>>(cast<int_t<>>(projectRunRow->lowering_plan_count));
	if (static_cast<bool>((planCount <= static_cast<int_t<> >(0)))) {
		if (static_cast<bool>((php::identical(cast<int_t<>>(projectRunRow->source_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(projectRunRow->symbol_count), static_cast<int_t<> >(0))))) {
			return;
		}
		ResidentSymbolDefinitionSnapshotRow symbol = __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(report, projectRunRow->run_id, static_cast<int_t<> >(0));
		ResidentSourceUnitSnapshotRow source = __latency_fn_partition_readiness_first_source_snapshot_for_owner(report, projectRunRow->run_id);
		int_t<std::uint32_t> sourceUnitId = required_cast<int_t<std::uint32_t>>(symbol->source_unit_id);
		if (static_cast<bool>(php::identical(cast<int_t<>>(sourceUnitId), static_cast<int_t<> >(0)))) {
			sourceUnitId = source->source_unit_id;
		}
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_none_id(), projectRunRow->run_id, __latency_fn_partition_readiness_owner_kind_lowering_id(), cast<int_t<std::uint32_t>>(sourceUnitId), symbol->symbol_id, projectRunRow->run_id, __latency_fn_partition_readiness_status_blocked_id(), __latency_fn_partition_readiness_blocked_reason_missing_lowering_owner_id()));
		return;
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < planCount))) {
		ResidentSymbolDefinitionSnapshotRow symbol = __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(report, projectRunRow->run_id, index);
		int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
		int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
		if (static_cast<bool>(((cast<int_t<>>(projectRunRow->lowering_blocked_request_count) > static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(projectRunRow->lowering_step_count), static_cast<int_t<> >(0))))) {
			statusId = __latency_fn_partition_readiness_status_blocked_id();
			blockedReasonId = __latency_fn_partition_readiness_blocked_reason_lowering_plan_blocked_id();
		}
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_none_id(), projectRunRow->run_id, __latency_fn_partition_readiness_owner_kind_lowering_id(), symbol->source_unit_id, symbol->symbol_id, __latency_fn_structure_row_ids_uint32_from_int((index + static_cast<int_t<> >(1))), cast<int_t<std::uint16_t>>(statusId), cast<int_t<std::uint16_t>>(blockedReasonId)));
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_backend_owner_rows(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_backend_owner_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[47]);
	int_t<> partitionCount = required_cast<int_t<>>(cast<int_t<>>(projectRunRow->backend_partition_count));
	if (static_cast<bool>((partitionCount <= static_cast<int_t<> >(0)))) {
		if (static_cast<bool>((php::identical(cast<int_t<>>(projectRunRow->source_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(projectRunRow->symbol_count), static_cast<int_t<> >(0))))) {
			return;
		}
		ResidentSymbolDefinitionSnapshotRow symbol = __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(report, projectRunRow->run_id, static_cast<int_t<> >(0));
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_none_id(), projectRunRow->run_id, __latency_fn_partition_readiness_owner_kind_backend_id(), symbol->source_unit_id, symbol->symbol_id, projectRunRow->run_id, __latency_fn_partition_readiness_status_blocked_id(), __latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id()));
		return;
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < partitionCount))) {
		ResidentSymbolDefinitionSnapshotRow symbol = __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(report, projectRunRow->run_id, index);
		int_t<std::uint16_t> statusId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_status_ready_id());
		int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_none_id());
		if (static_cast<bool>(php::condition_truthy((index >= cast<int_t<>>(projectRunRow->backend_partition_ready_count))))) {
			statusId = __latency_fn_partition_readiness_status_blocked_id();
			blockedReasonId = __latency_fn_partition_readiness_blocked_reason_backend_partition_blocked_id();
		}
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_none_id(), projectRunRow->run_id, __latency_fn_partition_readiness_owner_kind_backend_id(), symbol->source_unit_id, symbol->symbol_id, __latency_fn_structure_row_ids_uint32_from_int((index + static_cast<int_t<> >(1))), cast<int_t<std::uint16_t>>(statusId), cast<int_t<std::uint16_t>>(blockedReasonId)));
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_object_owner_rows(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_object_owner_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[48]);
	int_t<> partitionCount = required_cast<int_t<>>(cast<int_t<>>(projectRunRow->backend_partition_count));
	if (static_cast<bool>((partitionCount <= static_cast<int_t<> >(0)))) {
		if (static_cast<bool>((php::identical(cast<int_t<>>(projectRunRow->source_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(projectRunRow->symbol_count), static_cast<int_t<> >(0))))) {
			return;
		}
		ResidentSymbolDefinitionSnapshotRow symbol = __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(report, projectRunRow->run_id, static_cast<int_t<> >(0));
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_none_id(), projectRunRow->run_id, __latency_fn_partition_readiness_owner_kind_object_id(), symbol->source_unit_id, symbol->symbol_id, projectRunRow->run_id, __latency_fn_partition_readiness_status_blocked_id(), __latency_fn_partition_readiness_blocked_reason_missing_backend_owner_id()));
		return;
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < partitionCount))) {
		ResidentSymbolDefinitionSnapshotRow symbol = __latency_fn_partition_readiness_symbol_snapshot_for_owner_position(report, projectRunRow->run_id, index);
		int_t<std::uint16_t> blockedReasonId = required_cast<int_t<std::uint16_t>>(__latency_fn_partition_readiness_blocked_reason_object_execution_deferred_id());
		if (static_cast<bool>(php::condition_truthy((index >= cast<int_t<>>(projectRunRow->backend_partition_ready_count))))) {
			blockedReasonId = __latency_fn_partition_readiness_blocked_reason_backend_partition_blocked_id();
		}
		__latency_fn_partition_readiness_append_report_row(report, __latency_fn_partition_readiness_row(__latency_fn_structure_row_ids_none_id(), projectRunRow->run_id, __latency_fn_partition_readiness_owner_kind_object_id(), symbol->source_unit_id, symbol->symbol_id, __latency_fn_structure_row_ids_uint32_from_int((index + static_cast<int_t<> >(1))), __latency_fn_partition_readiness_status_blocked_id(), cast<int_t<std::uint16_t>>(blockedReasonId)));
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[49]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(rowCount), __latency_fn_structure_row_ids_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(PartitionReadinessRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_partition_readiness[]; }
namespace scpp {
void __latency_fn_partition_readiness_append_from_compiler_project_run_row(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow) {
	SCPP_CALL_DEPTH_GUARD("partition_readiness::append_from_compiler_project_run_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/partition_readiness.phs", __latency_lines_partition_readiness[50]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(projectRunRow->run_id), static_cast<int_t<> >(0)))) {
		return;
	}
	if (static_cast<bool>((((php::identical(cast<int_t<>>(projectRunRow->source_count), static_cast<int_t<> >(0)) && php::identical(cast<int_t<>>(projectRunRow->symbol_count), static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(projectRunRow->lowering_plan_count), static_cast<int_t<> >(0))) && php::identical(cast<int_t<>>(projectRunRow->backend_partition_count), static_cast<int_t<> >(0))))) {
		return;
	}
	int_t<> before = required_cast<int_t<>>(php::count(report->partition_readiness_rows));
	__latency_fn_partition_readiness_append_source_owner_rows(report, projectRunRow);
	__latency_fn_partition_readiness_append_symbol_body_owner_rows(report, projectRunRow);
	__latency_fn_partition_readiness_append_lowering_owner_rows(report, projectRunRow);
	__latency_fn_partition_readiness_append_backend_owner_rows(report, projectRunRow);
	__latency_fn_partition_readiness_append_object_owner_rows(report, projectRunRow);
	__latency_fn_partition_readiness_append_memory_estimate(report, (php::count(report->partition_readiness_rows) - before));
}

}
