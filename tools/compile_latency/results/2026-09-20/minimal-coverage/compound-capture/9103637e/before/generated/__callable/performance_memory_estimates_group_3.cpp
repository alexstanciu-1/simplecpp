#include <scpp/lang/php.hpp>
#include "__types/ArtifactWriteRecord.hpp"
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendEmissionBlockRow.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/BackendProjectLinkExecutionRow.hpp"
#include "__types/BackendSinkBoundaryRow.hpp"
#include "__types/CompilerProfileEventRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/LoweringBlockedRequestRow.hpp"
#include "__types/LoweringStep.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ResidentFrontendNodeListSnapshotRow.hpp"
#include "__types/ResidentTokenListSnapshotRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_partition_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_lowering_work_ref_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_materialized_lowering_step_count.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_lowering_plan_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_lowering_work_ref_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_materialized_lowering_step_count.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_table_owner_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_lowering_sidecar_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_emission_decision_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_emission_sidecar_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_llvm_sink_preflight_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_materialized_llvm_text_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_resident_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_source_unit_owner_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_profile_event_rows_for_compiler_project_run.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_artifact_profile_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_profile_event_rows_for_compiler_project_run.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_from_compiler_project_run_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_artifact_profile_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_emission_decision_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_emission_sidecar_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_partition_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_request_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_request_sidecar_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_artifact_profile_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_backend_emission_decision_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_backend_emission_sidecar_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_backend_partition_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_backend_request_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_backend_request_sidecar_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_frontend_node_segment_slack_bytes_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_frontend_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_llvm_sink_preflight_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_lowering_plan_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_lowering_sidecar_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_materialized_llvm_text_bytes_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_project_symbol_sidecar_cache_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_source_text_and_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_symbol_reference_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_token_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_token_segment_slack_bytes_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_type_capability_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_frontend_node_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_frontend_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_llvm_sink_preflight_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_lowering_plan_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_lowering_sidecar_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_materialized_llvm_text_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_materialized_lowering_step_count.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_artifact_boundary_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_derived_text_boundary_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_lookup_cache_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_segment_capacity_slack_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_source_text_and_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_profile_event_rows_for_compiler_project_run.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_project_symbol_sidecar_cache_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_resident_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_source_text_and_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_symbol_reference_contract_dependency_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_token_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_token_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_type_capability_rows_bytes.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_backend_partition_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::backend_partition_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[42]);
	return ((cast<int_t<>>(row->backend_partition_count) * static_cast<int_t<> >(sizeof(BackendPartitionExecutionRow))) + (cast<int_t<>>(row->backend_partition_link_count) * static_cast<int_t<> >(sizeof(BackendProjectLinkExecutionRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_lowering_work_ref_row_bytes() {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::lowering_work_ref_row_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[43]);
	return static_cast<int_t<> >(4);
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_materialized_lowering_step_count(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::materialized_lowering_step_count", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[44]);
	int_t<> count = required_cast<int_t<>>((cast<int_t<>>(row->lowering_step_count) - cast<int_t<>>(row->lowering_work_ref_count)));
	if (static_cast<bool>((count < static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	return count;
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_lowering_plan_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::lowering_plan_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[45]);
	return (((cast<int_t<>>(row->lowering_plan_count) * __latency_fn_performance_memory_estimates_table_owner_row_bytes()) + (cast<int_t<>>(row->lowering_work_ref_count) * __latency_fn_performance_memory_estimates_lowering_work_ref_row_bytes())) + (__latency_fn_performance_memory_estimates_materialized_lowering_step_count(row) * static_cast<int_t<> >(sizeof(LoweringStep))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_lowering_sidecar_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::lowering_sidecar_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[46]);
	return (((((cast<int_t<>>(row->lowering_blocked_request_count) * static_cast<int_t<> >(sizeof(LoweringBlockedRequestRow))) + (cast<int_t<>>(row->lowering_binary_operand_count) * static_cast<int_t<> >(sizeof(BackendBinaryOperandRow)))) + (cast<int_t<>>(row->lowering_local_operand_count) * static_cast<int_t<> >(sizeof(BackendLocalOperandRow)))) + (cast<int_t<>>(row->lowering_call_argument_count) * static_cast<int_t<> >(sizeof(BackendCallArgumentRow)))) + (cast<int_t<>>(row->lowering_control_flow_operand_count) * static_cast<int_t<> >(sizeof(BackendControlFlowOperandRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_backend_emission_decision_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::backend_emission_decision_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[47]);
	return (((cast<int_t<>>(row->backend_emission_decision_count) * static_cast<int_t<> >(sizeof(BackendEmissionDecisionRow))) + (cast<int_t<>>(row->backend_emission_value_row_count) * static_cast<int_t<> >(sizeof(BackendEmissionValueRow)))) + (cast<int_t<>>(row->backend_emission_block_count) * static_cast<int_t<> >(sizeof(BackendEmissionBlockRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_backend_emission_sidecar_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::backend_emission_sidecar_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[48]);
	return ((((cast<int_t<>>(row->backend_emission_binary_operand_count) * static_cast<int_t<> >(sizeof(BackendBinaryOperandRow))) + (cast<int_t<>>(row->backend_emission_local_operand_count) * static_cast<int_t<> >(sizeof(BackendLocalOperandRow)))) + (cast<int_t<>>(row->backend_emission_call_argument_count) * static_cast<int_t<> >(sizeof(BackendCallArgumentRow)))) + (cast<int_t<>>(row->backend_emission_control_flow_operand_count) * static_cast<int_t<> >(sizeof(BackendControlFlowOperandRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_llvm_sink_preflight_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::llvm_sink_preflight_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[49]);
	return ((cast<int_t<>>(row->llvm_sink_boundary_count) * static_cast<int_t<> >(sizeof(BackendSinkBoundaryRow))) + (cast<int_t<>>(row->llvm_preflight_row_count) * static_cast<int_t<> >(sizeof(FunctionBodyTextEmissionPreflightRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_materialized_llvm_text_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::materialized_llvm_text_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[50]);
	return cast<int_t<>>(row->llvm_text_byte_count);
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_resident_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::resident_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[51]);
	return (((((cast<int_t<>>(row->source_count) * __latency_fn_performance_memory_estimates_source_unit_owner_row_bytes()) + (cast<int_t<>>(row->symbol_count) * static_cast<int_t<> >(sizeof(ProjectSymbolIndexRow)))) + (cast<int_t<>>(row->dependency_edge_count) * static_cast<int_t<> >(sizeof(ProjectDependencyGraphEdgeRow)))) + (cast<int_t<>>(row->token_list_snapshot_count) * static_cast<int_t<> >(sizeof(ResidentTokenListSnapshotRow)))) + (cast<int_t<>>(row->frontend_node_list_snapshot_count) * static_cast<int_t<> >(sizeof(ResidentFrontendNodeListSnapshotRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_profile_event_rows_for_compiler_project_run(shared_p<CompilerProjectRunReport> report, CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::profile_event_rows_for_compiler_project_run", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[52]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->profile_events;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto profileEvent = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(profileEvent->owner_run_id), cast<int_t<>>(row->run_id)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return count;
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_artifact_profile_rows_bytes(shared_p<CompilerProjectRunReport> report, CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::artifact_profile_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[53]);
	return ((cast<int_t<>>(row->artifact_write_count) * static_cast<int_t<> >(sizeof(ArtifactWriteRecord))) + (__latency_fn_performance_memory_estimates_profile_event_rows_for_compiler_project_run(report, row) * static_cast<int_t<> >(sizeof(CompilerProfileEventRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
void __latency_fn_performance_memory_estimates_append_row(shared_p<CompilerProjectRunReport>& report, PerformanceMemoryEstimateRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[54]);
	(void) report->performance_memory_estimates.append(row);
	report->performance_memory_estimate_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->performance_memory_estimates));
	report->performance_memory_estimated_hot_bytes = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->performance_memory_estimated_hot_bytes) + cast<int_t<>>(row->estimated_hot_bytes)));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
void __latency_fn_performance_memory_estimates_append_from_compiler_project_run_row(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::append_from_compiler_project_run_row", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[55]);
	int_t<> sourceRows = required_cast<int_t<>>(cast<int_t<>>(row->source_count));
	int_t<> symbolReferenceRows = required_cast<int_t<>>((((cast<int_t<>>(row->symbol_count) + cast<int_t<>>(row->reference_count)) + cast<int_t<>>(row->callable_contract_count)) + cast<int_t<>>(row->dependency_edge_count)));
	int_t<> symbolSidecarCacheRows = required_cast<int_t<>>((cast<int_t<>>(row->project_symbol_sidecar_string_count) + cast<int_t<>>(row->project_symbol_sidecar_lookup_entry_count)));
	int_t<> typeCapabilityRows = required_cast<int_t<>>((cast<int_t<>>(row->type_ref_count) + cast<int_t<>>(row->capability_readiness_count)));
	int_t<> backendRequestRows = required_cast<int_t<>>(cast<int_t<>>(row->backend_request_count));
	int_t<> backendRequestSidecarRows = required_cast<int_t<>>((((cast<int_t<>>(row->backend_request_binary_operand_count) + cast<int_t<>>(row->backend_request_local_operand_count)) + cast<int_t<>>(row->backend_request_call_argument_count)) + cast<int_t<>>(row->backend_request_control_flow_operand_count)));
	int_t<> backendPartitionRows = required_cast<int_t<>>((cast<int_t<>>(row->backend_partition_count) + cast<int_t<>>(row->backend_partition_link_count)));
	int_t<> loweringRows = required_cast<int_t<>>(((cast<int_t<>>(row->lowering_plan_count) + cast<int_t<>>(row->lowering_work_ref_count)) + __latency_fn_performance_memory_estimates_materialized_lowering_step_count(row)));
	int_t<> loweringSidecarRows = required_cast<int_t<>>(((((cast<int_t<>>(row->lowering_blocked_request_count) + cast<int_t<>>(row->lowering_binary_operand_count)) + cast<int_t<>>(row->lowering_local_operand_count)) + cast<int_t<>>(row->lowering_call_argument_count)) + cast<int_t<>>(row->lowering_control_flow_operand_count)));
	int_t<> backendEmissionRows = required_cast<int_t<>>(((cast<int_t<>>(row->backend_emission_decision_count) + cast<int_t<>>(row->backend_emission_value_row_count)) + cast<int_t<>>(row->backend_emission_block_count)));
	int_t<> backendEmissionSidecarRows = required_cast<int_t<>>((((cast<int_t<>>(row->backend_emission_binary_operand_count) + cast<int_t<>>(row->backend_emission_local_operand_count)) + cast<int_t<>>(row->backend_emission_call_argument_count)) + cast<int_t<>>(row->backend_emission_control_flow_operand_count)));
	int_t<> llvmSinkRows = required_cast<int_t<>>((cast<int_t<>>(row->llvm_sink_boundary_count) + cast<int_t<>>(row->llvm_preflight_row_count)));
	int_t<> residentRows = required_cast<int_t<>>(((((cast<int_t<>>(row->source_count) + cast<int_t<>>(row->symbol_count)) + cast<int_t<>>(row->dependency_edge_count)) + cast<int_t<>>(row->token_list_snapshot_count)) + cast<int_t<>>(row->frontend_node_list_snapshot_count)));
	int_t<> artifactProfileRows = required_cast<int_t<>>((cast<int_t<>>(row->artifact_write_count) + __latency_fn_performance_memory_estimates_profile_event_rows_for_compiler_project_run(report, row)));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_source_text_and_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(sourceRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_source_text_and_rows_bytes(row, sourceRows)), __latency_fn_performance_memory_estimates_policy_source_text_and_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_token_rows_id(), row->token_count, __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_token_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	int_t<> tokenSegmentSlackBytes = required_cast<int_t<>>(__latency_fn_performance_memory_estimates_token_segment_slack_bytes(row));
	if (static_cast<bool>((tokenSegmentSlackBytes > static_cast<int_t<> >(0)))) {
		__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_token_segment_slack_bytes_id(), row->token_segment_count, __latency_fn_structure_row_ids_uint32_from_int(tokenSegmentSlackBytes), __latency_fn_performance_memory_estimates_policy_segment_capacity_slack_id()));
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_frontend_rows_id(), row->frontend_node_count, __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_frontend_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	int_t<> frontendNodeSegmentSlackBytes = required_cast<int_t<>>(__latency_fn_performance_memory_estimates_frontend_node_segment_slack_bytes(row));
	if (static_cast<bool>((frontendNodeSegmentSlackBytes > static_cast<int_t<> >(0)))) {
		__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_frontend_node_segment_slack_bytes_id(), row->frontend_node_segment_count, __latency_fn_structure_row_ids_uint32_from_int(frontendNodeSegmentSlackBytes), __latency_fn_performance_memory_estimates_policy_segment_capacity_slack_id()));
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_symbol_reference_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(symbolReferenceRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_symbol_reference_contract_dependency_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_project_symbol_sidecar_cache_id(), __latency_fn_structure_row_ids_uint32_from_int(symbolSidecarCacheRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_project_symbol_sidecar_cache_bytes(row)), __latency_fn_performance_memory_estimates_policy_lookup_cache_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_type_capability_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(typeCapabilityRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_type_capability_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_backend_request_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(backendRequestRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_backend_request_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_backend_request_sidecar_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(backendRequestSidecarRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_backend_request_sidecar_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_backend_partition_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(backendPartitionRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_backend_partition_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_lowering_plan_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(loweringRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_lowering_plan_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_lowering_sidecar_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(loweringSidecarRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_lowering_sidecar_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_backend_emission_decision_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(backendEmissionRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_backend_emission_decision_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_backend_emission_sidecar_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(backendEmissionSidecarRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_backend_emission_sidecar_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_llvm_sink_preflight_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(llvmSinkRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_llvm_sink_preflight_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_materialized_llvm_text_bytes_id(), row->llvm_text_blob_count, __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_materialized_llvm_text_bytes(row)), __latency_fn_performance_memory_estimates_policy_derived_text_boundary_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(residentRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_resident_rows_bytes(row)), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_artifact_profile_rows_id(), __latency_fn_structure_row_ids_uint32_from_int(artifactProfileRows), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_performance_memory_estimates_artifact_profile_rows_bytes(report, row)), __latency_fn_performance_memory_estimates_policy_artifact_boundary_id()));
}

}
