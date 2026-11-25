#include <scpp/lang/php.hpp>
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/BackendLoweringWorkerInput.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowSpan.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/OperationReadiness.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/StorageLifetimeRequestRow.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_first_request_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_next_request_span.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_request_span_is_empty.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_span_request_at.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_for_backend_lowering.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_backend_request.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_int.hpp"
#include "__callable/__latency_fn_lowering_plan_semantic_hash_mix_lowering_plan.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_sidecar_row_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_worker_input_from_rows.hpp"
#include "__callable/__latency_fn_lowering_plan_entry_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_contract_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_lowering_plan_operation_from_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_semantic_hash_for_backend_lowering(shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::semantic_hash_for_backend_lowering", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[68]);
	int_t<> hash = required_cast<int_t<>>(static_cast<int_t<> >(23));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->request_count));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->ready_count));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->blocked_count));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->binary_operand_count));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->local_operand_count));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->call_argument_count));
	hash = __latency_fn_lowering_plan_semantic_hash_mix_int(hash, cast<int_t<>>(requests->control_flow_operand_count));
	BackendRequestRowSpan span = __latency_fn_backend_preflight_requests_first_request_span(requests);
	while (static_cast<bool>((!__latency_fn_backend_preflight_requests_request_span_is_empty(span)))) {
		int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((offset < cast<int_t<>>(span->count)))) {
			BackendRequestAuthorizationRow request = __latency_fn_backend_preflight_requests_span_request_at(requests, span, offset);
			hash = __latency_fn_lowering_plan_semantic_hash_mix_backend_request(hash, request);
			offset = (offset + static_cast<int_t<> >(1));
		}
		span = __latency_fn_backend_preflight_requests_next_request_span(requests, span);
	}
	hash = __latency_fn_lowering_plan_semantic_hash_mix_lowering_plan(hash, plan);
	return __latency_fn_structure_row_ids_uint32_from_int(hash);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_lowering_plan_sidecar_row_count(shared_p<BackendRequestAuthorizationArtifact> requests, shared_p<LoweringPlan> plan) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::sidecar_row_count", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[69]);
	int_t<> total = required_cast<int_t<>>((((((((cast<int_t<>>(requests->binary_operand_count) + cast<int_t<>>(requests->local_operand_count)) + cast<int_t<>>(requests->call_argument_count)) + cast<int_t<>>(requests->control_flow_operand_count)) + cast<int_t<>>(plan->binary_operand_count)) + cast<int_t<>>(plan->local_operand_count)) + cast<int_t<>>(plan->call_argument_count)) + cast<int_t<>>(plan->control_flow_operand_count)));
	return __latency_fn_structure_row_ids_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
shared_p<BackendLoweringWorkerInput> __latency_fn_lowering_plan_worker_input_from_rows(int_t<std::uint32_t> ownerRunId, ProjectSymbolIndexRow entrySymbol, AnalysisEntryContextRow entry, ProjectCallableContractRow contract, OperationReadiness operation, StorageLifetimeRequestRow storage) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::worker_input_from_rows", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[70]);
	shared_p<BackendLoweringWorkerInput> input = create<BackendLoweringWorkerInput>();
	input->owner_run_id = cast<int_t<>>(ownerRunId);
	input->source_unit_id = cast<int_t<>>(entrySymbol->source_unit_id);
	input->symbol_id = cast<int_t<>>(entrySymbol->symbol_id);
	input->entry_context_id = cast<int_t<>>(entry->context_id);
	input->entry_declaration_node_id = cast<int_t<>>(entry->declaration_node_id);
	input->entry_graph_symbol_node_id = cast<int_t<>>(entry->graph_symbol_node_id);
	input->entry_first_reference_id = cast<int_t<>>(entry->first_reference_id);
	input->entry_reference_count = cast<int_t<>>(entry->reference_count);
	input->entry_first_callable_contract_id = cast<int_t<>>(entry->first_callable_contract_id);
	input->entry_callable_contract_count = cast<int_t<>>(entry->callable_contract_count);
	input->entry_first_dependency_edge_id = cast<int_t<>>(entry->first_dependency_edge_id);
	input->entry_dependency_edge_count = cast<int_t<>>(entry->dependency_edge_count);
	input->entry_status_id = cast<int_t<>>(entry->status_id);
	input->entry_downstream_table_status_id = cast<int_t<>>(entry->downstream_table_status_id);
	input->entry_flags = cast<int_t<>>(entry->flags);
	input->contract_id = cast<int_t<>>(contract->contract_id);
	input->contract_reference_id = cast<int_t<>>(contract->reference_id);
	input->contract_from_symbol_id = cast<int_t<>>(contract->from_symbol_id);
	input->contract_target_symbol_id = cast<int_t<>>(contract->target_symbol_id);
	input->contract_target_source_unit_id = cast<int_t<>>(contract->target_source_unit_id);
	input->contract_argument_count_status_id = cast<int_t<>>(contract->argument_count_status_id);
	input->contract_return_type_status_id = cast<int_t<>>(contract->return_type_status_id);
	input->contract_backend_lowering_status_id = cast<int_t<>>(contract->backend_lowering_status_id);
	input->contract_status_id = cast<int_t<>>(contract->status_id);
	input->contract_blocked_reason_id = cast<int_t<>>(contract->blocked_reason_id);
	input->contract_actual_arg_count = cast<int_t<>>(contract->actual_arg_count);
	input->contract_expected_arg_count = cast<int_t<>>(contract->expected_arg_count);
	input->contract_return_type_ref_id = cast<int_t<>>(contract->return_type_ref_id);
	input->contract_backend_adapter_id = cast<int_t<>>(contract->backend_adapter_id);
	input->operation_contract_id = cast<int_t<>>(operation->contract_id);
	input->operation_lowering_adapter_id = cast<int_t<>>(operation->lowering_adapter_id);
	input->operation_source_row_id = cast<int_t<>>(operation->source_row_id);
	input->operation_kind_id = cast<int_t<>>(operation->operation_kind_id);
	input->operation_capability_id = cast<int_t<>>(operation->capability_id);
	input->operation_consumer_feature_id = cast<int_t<>>(operation->consumer_feature_id);
	input->operation_provider_type_ref_id = cast<int_t<>>(operation->provider_type_ref_id);
	input->operation_status_id = cast<int_t<>>(operation->status_id);
	input->operation_blocked_reason_id = cast<int_t<>>(operation->blocked_reason_id);
	input->operation_result_type_ref_id = cast<int_t<>>(operation->result_type_ref_id);
	input->storage_request_id = cast<int_t<>>(storage->request_id);
	input->storage_consumer_kind_id = cast<int_t<>>(storage->consumer_kind_id);
	input->storage_capability_id = cast<int_t<>>(storage->capability_id);
	input->storage_consumer_feature_id = cast<int_t<>>(storage->consumer_feature_id);
	input->storage_context_id = cast<int_t<>>(storage->storage_context_id);
	input->storage_type_ref_id = cast<int_t<>>(storage->type_ref_id);
	input->storage_source_row_id = cast<int_t<>>(storage->source_row_id);
	input->storage_policy_id = cast<int_t<>>(storage->storage_policy_id);
	input->storage_copy_policy_id = cast<int_t<>>(storage->copy_policy_id);
	input->storage_cleanup_policy_id = cast<int_t<>>(storage->cleanup_policy_id);
	input->storage_lifetime_policy_id = cast<int_t<>>(storage->lifetime_policy_id);
	input->storage_readiness_status_id = cast<int_t<>>(storage->readiness_status_id);
	return input;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
AnalysisEntryContextRow __latency_fn_lowering_plan_entry_from_worker_input(shared_p<BackendLoweringWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::entry_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[71]);
	AnalysisEntryContextRow row = AnalysisEntryContextRow{};
	row->context_id = __latency_fn_structure_row_ids_uint32_from_int(input->entry_context_id);
	row->source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_unit_id);
	row->symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->symbol_id);
	row->declaration_node_id = __latency_fn_structure_row_ids_uint32_from_int(input->entry_declaration_node_id);
	row->graph_symbol_node_id = __latency_fn_structure_row_ids_uint32_from_int(input->entry_graph_symbol_node_id);
	row->first_reference_id = __latency_fn_structure_row_ids_uint32_from_int(input->entry_first_reference_id);
	row->reference_count = __latency_fn_structure_row_ids_uint32_from_int(input->entry_reference_count);
	row->first_callable_contract_id = __latency_fn_structure_row_ids_uint32_from_int(input->entry_first_callable_contract_id);
	row->callable_contract_count = __latency_fn_structure_row_ids_uint32_from_int(input->entry_callable_contract_count);
	row->first_dependency_edge_id = __latency_fn_structure_row_ids_uint32_from_int(input->entry_first_dependency_edge_id);
	row->dependency_edge_count = __latency_fn_structure_row_ids_uint32_from_int(input->entry_dependency_edge_count);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->entry_status_id);
	row->downstream_table_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->entry_downstream_table_status_id);
	row->flags = __latency_fn_structure_row_ids_uint16_from_int(input->entry_flags);
	return row;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
ProjectCallableContractRow __latency_fn_lowering_plan_contract_from_worker_input(shared_p<BackendLoweringWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::contract_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[72]);
	ProjectCallableContractRow row = ProjectCallableContractRow{};
	row->contract_id = __latency_fn_structure_row_ids_uint32_from_int(input->contract_id);
	row->reference_id = __latency_fn_structure_row_ids_uint32_from_int(input->contract_reference_id);
	row->from_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->contract_from_symbol_id);
	row->target_symbol_id = __latency_fn_structure_row_ids_uint32_from_int(input->contract_target_symbol_id);
	row->target_source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(input->contract_target_source_unit_id);
	row->argument_count_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->contract_argument_count_status_id);
	row->return_type_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->contract_return_type_status_id);
	row->backend_lowering_status_id = __latency_fn_structure_row_ids_uint16_from_int(input->contract_backend_lowering_status_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->contract_status_id);
	row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->contract_blocked_reason_id);
	row->actual_arg_count = __latency_fn_structure_row_ids_uint32_from_int(input->contract_actual_arg_count);
	row->expected_arg_count = __latency_fn_structure_row_ids_uint32_from_int(input->contract_expected_arg_count);
	row->return_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->contract_return_type_ref_id);
	row->backend_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->contract_backend_adapter_id);
	return row;
}

}

namespace scpp { extern const int __latency_lines_lowering_plan[]; }
namespace scpp {
OperationReadiness __latency_fn_lowering_plan_operation_from_worker_input(shared_p<BackendLoweringWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("lowering_plan::operation_from_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/backend/lowering_plan.phs", __latency_lines_lowering_plan[73]);
	OperationReadiness row = OperationReadiness{};
	row->contract_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_contract_id);
	row->lowering_adapter_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_lowering_adapter_id);
	row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(input->operation_source_row_id);
	row->operation_kind_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_kind_id);
	row->capability_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_capability_id);
	row->consumer_feature_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_consumer_feature_id);
	row->provider_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->operation_provider_type_ref_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_status_id);
	row->blocked_reason_id = __latency_fn_structure_row_ids_uint16_from_int(input->operation_blocked_reason_id);
	row->result_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(input->operation_result_type_ref_id);
	return row;
}

}
